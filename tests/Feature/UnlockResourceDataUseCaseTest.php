<?php

use Domain\Application\UseCase\UnlockResourceDataUseCase;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UnlockResourceDataRequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;


it('UnlockResourceDataUseCase throws on invalid request type', function (): void {
    $uc = new UnlockResourceDataUseCase();
    $invalid = new class implements \Small\CleanApplication\Contract\RequestInterface {};
    $uc->execute($invalid);
})->throws(\Domain\Application\Exception\ApplicationBadRequest::class);

it('UnlockResourceDataUseCase releases resource with provided ticket (success path)', function (): void {
    // Spy resource that records the ticket id it was asked to release
    $spy = new class {
        public ?string $releasedTicketId = null;
        public function releaseResource($ticket) {
            // ticket is \Small\SwoolePatterns\Resource\Bean\Ticket
            if ($ticket !== null && method_exists($ticket, 'getTicketId')) {
                $this->releasedTicketId = $ticket->getTicketId();
            } else {
                $this->releasedTicketId = null;
            }
            return $this; // mimic fluent API
        }
    };

    // Build a valid request DTO
    $request = new class('printer', 'sel-1', 't-123') implements UnlockResourceDataRequestInterface {
        public function __construct(public string $resourceName, public ?string $selector, public ?string $ticket) {}
    };

    $resource = new ResourceStub(true, 't-123');
    \Infrastructure\Kernel::$resourceFactory->resource = $resource;

    $uc = new UnlockResourceDataUseCase();
    $uc->execute($request);

    expect($resource->incomingTicketId)->toBe('t-123');

});
