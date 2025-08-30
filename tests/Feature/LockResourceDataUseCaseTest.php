<?php

use Domain\Application\UseCase\LockResourceDataUseCase;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface;
use Infrastructure\Kernel;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;

// ---- fakes / stubs --------------------------------------------------------

/**
 * Build a valid request DTO implementing LockResourceDataRequestInterface.
 */
function makeRequest(?string $ticket = null): LockResourceDataRequestInterface {
    return new class('printer', 'sel-1', $ticket) implements LockResourceDataRequestInterface {
        public function __construct(
            public string $resourceName,
            public ?string $selector,
            public ?string $ticket
        ) {}
    };
}

// ---- tests ----------------------------------------------------------------

it('throws on invalid request type', function () {
    $uc = new LockResourceDataUseCase(new FakeResourceManager(0));
    $invalid = new class implements RequestInterface {};
    $uc->execute($invalid);
})->throws(ApplicationBadRequest::class);

it('timeout=0 uses waitingForFree and succeeds when resource immediately free', function () {
    // Kernel returns a resource that immediately grants the lock (waiting=false)
    $resource = new ResourceStub(true, 't-free');
    Kernel::$resourceFactory->resource = $resource;

    FakeResourceManager::$timeout = 0;
    $uc = new LockResourceDataUseCase(new FakeResourceManager()); // timeout=0

    $resp = $uc->execute(makeRequest(null));

    expect($resp->lockedSuccess)->toBeTrue()
        ->and($resp->ticket)->toBe('t-free');
    // Behaviour chosen should be waitingForFree
    expect(strtolower($resource->lastBehaviour))->toBe('waitingforfree');
});

it('timeout>0 uses getTicket and succeeds when not waiting', function () {
    // Resource returns not waiting on first try
    $resource = new ResourceStub(false, 't-now');
    Kernel::$resourceFactory->resource = $resource;

    FakeResourceManager::$timeout = 2;
    $uc = new LockResourceDataUseCase(new FakeResourceManager(2)); // timeout 2s
    $resp = $uc->execute(makeRequest(null));

    expect($resp->lockedSuccess)->toBeTrue()
        ->and($resp->ticket)->toBe('t-now');
    expect(strtolower($resource->lastBehaviour))->toBe('getticket');
});

it('timeout>0 uses getTicket and returns lockedSuccess=false when still waiting', function () {
    // Resource keeps waiting
    $resource = new ResourceStub(true, 't-wait');
    Kernel::$resourceFactory->resource = $resource;

    FakeResourceManager::$timeout = 2;
    $uc = new LockResourceDataUseCase(new FakeResourceManager(2)); // timeout 2s
    $resp = $uc->execute(makeRequest(null));

    expect($resp->lockedSuccess)->toBeFalse()
        ->and($resp->ticket)->toBe('t-wait');
    expect(strtolower($resource->lastBehaviour))->toBe('getticket');
});

it('passes through an incoming ticket string', function () {
    $resource = new ResourceStub(false, 'ignored');
    Kernel::$resourceFactory->resource = $resource;

    $uc = new LockResourceDataUseCase(new FakeResourceManager(0));
    $reqTicket = 'preexisting-ticket-123';
    $resp = $uc->execute(makeRequest($reqTicket));

    // Resource saw the incoming ticket id
    expect($resource->incomingTicketId)->toBe($reqTicket);
    // Response echoes the same ticket id (since stub returns same Ticket)
    expect($resp->ticket)->toBe($reqTicket);
});
