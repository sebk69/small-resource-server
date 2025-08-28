<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\UseCase;

use Domain\Application\Exception\ApplicationBadRequest;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UnlockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Infrastructure\Kernel;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;
use Small\CleanApplication\Contract\UseCaseInterface;
use Small\SwoolePatterns\Resource\Bean\Ticket;
use Small\SwoolePatterns\Resource\Enum\GetResourceBehaviour;
use Small\SwoolePatterns\Resource\Exception\ResourceNotFreeException;

class UnlockResourceDataUseCase implements UseCaseInterface
{
    public function execute(RequestInterface $request): ResponseInterface
    {

        if (!$request instanceof UnlockResourceDataRequestInterface) {
            throw new ApplicationBadRequest(
                'Request must be an instance of ' . LockResourceDataRequestInterface::class
            );
        }

        $resource = Kernel::$resourceFactory->get($request->resourceName . ':' . $request->selector);

        $ticket = null;
        if ($request->ticket !== null) {
            $ticket = new Ticket($request->ticket);
        }

        $resource->releaseResource($ticket);

        return new class() implements ResponseInterface {};

    }


}