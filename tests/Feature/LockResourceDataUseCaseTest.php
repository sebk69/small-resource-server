<?php

use Domain\Application\UseCase\LockResourceDataUseCase;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Small\CleanApplication\Contract\RequestInterface;

it('LockResourceDataUseCase throws on invalid request type', function (): void {
    $uc = new LockResourceDataUseCase();
    $invalid = new class implements RequestInterface {};
    $uc->execute($invalid);
})->throws(ApplicationBadRequest::class);

// Helper to build request DTO
function makeRequest(?string $ticket = null): LockResourceDataRequestInterface {
    return new class('printer', 'sel-1', $ticket) implements LockResourceDataRequestInterface {
        public function __construct(
            public string $resourceName,
            public ?string $selector,
            public ?string $ticket
        ) {}

    };
}

it('LockResourceDataUseCase returns lockedSuccess=true and a ticket when lock is acquired immediately', function (): void {
    $uc = new LockResourceDataUseCase();

    $resp = $uc->execute(makeRequest(null));

    expect($resp->lockedSuccess)->toBeTrue()
        ->and($resp->ticket)->toBeString()
        ->and(strlen($resp->ticket))->toBe(32);
});

it('LockResourceDataUseCase returns lockedSuccess=false and a ticket when lock cannot be acquired yet', function (): void {
    // Arrange: Kernel factory returns a Resource that DOES wait (lock held elsewhere)
    $uc = new LockResourceDataUseCase();

    $resp = $uc->execute(makeRequest(null));

    expect($resp->lockedSuccess)->toBeFalse()
        ->and($resp->ticket)->toBeString()
        ->and(strlen($resp->ticket))->toBe(32);

});