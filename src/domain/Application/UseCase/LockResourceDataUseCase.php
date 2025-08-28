<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\UseCase;

use Domain\Application\Exception\ApplicationBadRequest;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Infrastructure\Kernel;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;
use Small\CleanApplication\Contract\UseCaseInterface;
use Small\SwoolePatterns\Resource\Bean\Ticket;
use Small\SwoolePatterns\Resource\Enum\GetResourceBehaviour;

class LockResourceDataUseCase implements UseCaseInterface
{
    public function execute(RequestInterface $request): ResponseInterface
    {

        if (!$request instanceof LockResourceDataRequestInterface) {
            throw new ApplicationBadRequest(
                'Request must be an instance of ' . LockResourceDataRequestInterface::class
            );
        }

        $resource = Kernel::$resourceFactory->get($request->resourceName . ':' . $request->selector);

        $ticket = null;
        if ($request->ticket !== null) {
            $ticket = new Ticket($request->ticket);
        }

        $ticket = $resource->acquireResource(GetResourceBehaviour::getTicket, $ticket);

        return new class(!$ticket->isWaiting(), $ticket->getTicketId())
            implements LockResourceDataResponseInterface
        {
            public function __construct(
                public bool $lockedSuccess,
                public string|null $ticket,
            ) {}
        };

    }


}