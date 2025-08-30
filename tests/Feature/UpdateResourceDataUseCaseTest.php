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

function makeLockRequest(?string $ticket = null): \Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface {
    return new class('printer', 'sel-1', $ticket) implements \Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface {
        public function __construct(
            public string $resourceName,
            public ?string $selector,
            public ?string $ticket
        ) {}
    };
}

// Keep the user's invalid-type test
it('UpdateResourceDataUseCase throws on invalid request type', function (): void {

    $uc = new UpdateResourceDataUseCase(
        new FakeResourceManager(),
        new FakeResourceDataManager(),
        new \Domain\Application\UseCase\LockResourceDataUseCase(new FakeResourceManager()),
        new \Domain\Application\UseCase\UnlockResourceDataUseCase()
    );
    $invalid = new class implements RequestInterface {};
    $uc->execute($invalid);
})->throws(ApplicationBadRequest::class);

// --- test helpers (local fakes) -------------------------------------------

/**
 * Request DTO implementing UpdateResourceDataRequestInterface.
 */
function makeUpdateRequest(string $name, string $selector, $ticket, string $json): UpdateResourceDataRequestInterface {

    return new class($name, $selector, $json, $ticket) implements UpdateResourceDataRequestInterface {
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

    /** @var LockResourceDataResponseInterface $lockResponse */
    FakeResourceManager::$timeout = 0;
    $lockResponse = ($luc = new LockResourceDataUseCase($rs = new FakeResourceManager()))->execute(makeLockRequest('sel-1'));
    $unlock = new FakeResourceUnlockUseCase();
    $uc = new UpdateResourceDataUseCase($rm, $rdm, $luc, $unlock);
    $req = makeUpdateRequest('printer', 'A4', 'sel-1', json_encode(['status' => 'ready'], JSON_THROW_ON_ERROR));

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

    /** @var LockResourceDataResponseInterface $lockResponse */
    FakeResourceManager::$timeout = 0;
    $lockResponse = ($luc = new LockResourceDataUseCase($rs = new FakeResourceManager()))->execute(makeLockRequest('sel-1'));
    $unlock = new FakeResourceUnlockUseCase();
    $uc = new UpdateResourceDataUseCase($rm, $rdm, $luc, $unlock);
    $req = makeUpdateRequest('printer', 'A4', 'sel-1', json_encode(['status' => 'new'], JSON_THROW_ON_ERROR));

    $resp = $uc->execute($req);
    expect($resp)->toBeInstanceOf(ResponseInterface::class)
        ->and($rdm->last)->not()->toBeNull()
        ->and($rdm->last->idResource)->toBe('printer')
        ->and($rdm->last->selector)->toBe('A4')
        ->and($rdm->last->data)->toBe('{"status":"new"}');

});