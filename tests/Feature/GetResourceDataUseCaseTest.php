<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

use Domain\Application\Entity\ResourceData;
use Domain\Application\UseCase\GetResourceDataUseCase;
use Domain\Application\UseCase\LockResourceDataUseCase;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\GetResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Small\CleanApplication\Contract\RequestInterface;

class FakeLockUseCase extends LockResourceDataUseCase {
    public function __construct() {}
    public function execute(RequestInterface $request): \Small\CleanApplication\Contract\ResponseInterface {
        // return a response that signals locked success and provides a ticket
        return new class(true, 'ticket-123') implements LockResourceDataResponseInterface {
            public function __construct(public bool $lockedSuccess, public ?string $ticket) {}
        };
    }
}

class FakeLockUseCaseFail extends LockResourceDataUseCase {
    public function __construct() {}
    public function execute(RequestInterface $request): \Small\CleanApplication\Contract\ResponseInterface {
        return new class(false, 'ticket-xyz') implements LockResourceDataResponseInterface {
            public function __construct(public bool $lockedSuccess, public ?string $ticket) {}
        };
    }
}

class RequestDTO implements GetResourceDataRequestInterface {
    public function __construct(
        public string $resourceName { get { return $this->resourceName; } },
        public string $selector     { get { return $this->selector; } },
        public bool $shouldLock     { get { return $this->shouldLock; } },
        public ?string $ticket      { get { return $this->ticket; } },
    ) {}
}

test('GetResourceDataUseCase returns data without lock', function (): void {
    $uc = new GetResourceDataUseCase(new FakeResourceDataManager(), new FakeLockUseCase());
    $resp = $uc->execute(new RequestDTO('resA', 'sel1', false, null));

    expect($resp->resourceData)->not()->toBeNull()
        ->and($resp->resourceData->idResource)->toBe('resA')
        ->and($resp->resourceData->selector)->toBe('sel1')
        ->and($resp->ticket)->toBeNull();
});

test('GetResourceDataUseCase locks then returns data when lock succeeds', function (): void {
    $uc = new GetResourceDataUseCase(new FakeResourceDataManager(), new FakeLockUseCase());
    $resp = $uc->execute(new RequestDTO('resB', 'sel2', true, null));

    expect($resp->resourceData)->not()->toBeNull()
        ->and($resp->ticket)->toBe('ticket-123');
});

test('GetResourceDataUseCase returns only ticket when lock fails', function (): void {
    $uc = new GetResourceDataUseCase(new FakeResourceDataManager(), new FakeLockUseCaseFail());
    $resp = $uc->execute(new RequestDTO('resC', 'sel3', true, null));

    // In this branch, resourceData is null-like and only ticket is set
    expect($resp->ticket)->toBe('ticket-xyz');
    // Allow both null or property-like null object; check via isset
    expect(isset($resp->resourceData))->toBeFalse();
});

test('GetResourceDataUseCase rejects invalid request type', function (): void {
    $uc = new GetResourceDataUseCase(new FakeResourceDataManager(), new FakeLockUseCase());
    $invalid = new class implements RequestInterface {};
    expect(fn() => $uc->execute($invalid))->toThrow(ApplicationBadRequest::class);
});
