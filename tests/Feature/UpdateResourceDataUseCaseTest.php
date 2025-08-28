<?php
/*
 * Tests for UpdateResourceDataUseCase
 */

use Domain\Application\UseCase\LockResourceDataUseCase;
use Domain\Application\UseCase\UnlockResourceDataUseCase;
use Domain\Application\UseCase\UpdateResourceDataUseCase;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UpdateResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;
use Tests\Feature\FakeManagers\FakeResourceManager;
use Tests\Feature\FakeManagers\FakeResourceDataManager;

// Keep the user's invalid-type test
it('UpdateResourceDataUseCase throws on invalid request type', function (): void {
    $uc = new UpdateResourceDataUseCase(new FakeResourceManager(), new FakeResourceDataManager(), new \Domain\Application\UseCase\LockResourceDataUseCase(), new \Domain\Application\UseCase\UnlockResourceDataUseCase());
    $invalid = new class implements RequestInterface {};
    $uc->execute($invalid);
})->throws(ApplicationBadRequest::class);

// --- test helpers (local fakes) -------------------------------------------

/**
 * Request DTO implementing UpdateResourceDataRequestInterface.
 */
function makeUpdateRequest(string $name, string $selector, string $json): UpdateResourceDataRequestInterface {

    /** @var LockResourceDataResponseInterface $lockResponse */
    $lockResponse = \Small\CleanApplication\Facade::execute(
        LockResourceDataUseCase::class,
        new class($name, $selector) implements \Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface {
            public function __construct(
                public string $resourceName,
                public string $selector,
                public null|string $ticket = null
            ) {}
        }
    );

    return new class($name, $selector, $json, $lockResponse->ticket) implements UpdateResourceDataRequestInterface {
        public function __construct(public string $resourceName, public string $selector, public string $json, public string $ticket) {}
    };
}

// --- success: create new (no existing row) --------------------------------

it('updates resource data successfully when no previous data exists', function (): void {
    $rm = new FakeResourceManager();
    // Spy Data Manager that records the last persisted ResourceData entity
    $rdm = new class extends FakeResourceDataManager {
        public ?\Domain\Application\Entity\ResourceData $last = null;
        public function applicationPersist(\Domain\Application\Entity\ResourceData $data): self {
            $this->last = $data;
            return parent::applicationPersist($data);
        }
    };

    // Fake manager that records persistence and pretends the name is not taken
    $manager = new class implements \Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface {
        public ?\Domain\Application\Entity\Resource $persisted = null;
        public function findByName(string $name): \Domain\Application\Entity\Resource { return new \Domain\Application\Entity\Resource(); }
        public function existsByName(string $name): bool { return false; } // available
        public function applicationPersist(\Domain\Application\Entity\Resource $resource): \Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface {
            $this->persisted = $resource;
            return $this;
        }
    };

    $ucCR = new \Domain\Application\UseCase\CreateResourceUseCase($manager);

    // Valid request DTO implementing the interface (PHP 8.4 property hooks)
    $request = new class('printer', 120) implements \Domain\InterfaceAdapter\Gateway\UseCase\Request\CreateResourceRequestInterface {
        public function __construct(private string $name_, private int $timeout_) {}
        public string $name { get { return $this->name_; } }
        public int $timeout { get { return $this->timeout_; } }
    };

    $ucCR->execute($request);

    $lock = new LockResourceDataUseCase();
    $unlock = new UnlockResourceDataUseCase();

    $uc = new UpdateResourceDataUseCase($rm, $rdm, $lock, $unlock);

    $req = makeUpdateRequest('printer', 'A4', json_encode(['status' => 'ready'], JSON_THROW_ON_ERROR));

    $resp = $uc->execute($req);
    expect($resp)->toBeInstanceOf(ResponseInterface::class)
        ->and($rdm->last)->not()->toBeNull()
        ->and($rdm->last->idResource)->toBe('printer')
        ->and($rdm->last->selector)->toBe('A4')
        ->and($rdm->last->data)->toBe('{"status":"ready"}');
});

// --- success: update existing row -----------------------------------------

it('updates resource data when an entry already exists (overwrite)', function (): void {
    $rm = new FakeResourceManager();
    // Prepare Data Manager preloaded with an existing entry, and spy persistence
    $rdm = new class extends FakeResourceDataManager {
        public ?\Domain\Application\Entity\ResourceData $last = null;
        public function __construct() {
            // preload existing
            $existing = new \Domain\Application\Entity\ResourceData();
            $existing->generateId();
            $existing->idResource = 'printer';
            $existing->selector = 'A4';
            $existing->data = '{"status":"old"}';
            $this->applicationPersist($existing);
        }
        public function applicationPersist(\Domain\Application\Entity\ResourceData $data): self {
            $this->last = $data;
            return parent::applicationPersist($data);
        }
    };

    $lock = new LockResourceDataUseCase();
    $unlock = new UnlockResourceDataUseCase();

    $uc = new UpdateResourceDataUseCase($rm, $rdm, $lock, $unlock);
    $req = makeUpdateRequest('printer', 'A4', json_encode(['status' => 'new'], JSON_THROW_ON_ERROR), );

    $resp = $uc->execute($req);
    expect($resp)->toBeInstanceOf(ResponseInterface::class)
        ->and($rdm->last)->not()->toBeNull()
        ->and($rdm->last->idResource)->toBe('printer')
        ->and($rdm->last->selector)->toBe('A4')
        ->and($rdm->last->data)->toBe('{"status":"new"}');

});