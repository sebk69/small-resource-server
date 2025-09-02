<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\UseCase;

use Domain\Application\Entity\ResourceData;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\Application\Exception\ApplicationNotFoundException;
use Domain\InterfaceAdapter\Gateway\Manager\Exception\NotFoundException;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\CreateResourceRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\GetResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\GetResourceDataResponseInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;
use Small\CleanApplication\Contract\UseCaseInterface;
use Small\CleanApplication\Facade;

final class GetResourceDataUseCase implements UseCaseInterface
{
    public function __construct(
        private ResourceDataManagerInterface $resourceDataManager,
        private LockResourceDataUseCase $lockResourceDataUseCase,
    ) {}

    public function execute(RequestInterface $request): ResponseInterface
    {

        if (!$request instanceof GetResourceDataRequestInterface) {
            throw new ApplicationBadRequest(
                'Request must be an instance of ' . GetResourceDataRequestInterface::class
            );
        }

        $ticket = null;
        if ($request->shouldLock) {
            /** @var LockResourceDataResponseInterface $response */
            $response = $this->lockResourceDataUseCase->execute(
                new class(
                    $request->resourceName,
                    $request->selector,
                    $request->ticket,
                ) implements LockResourceDataRequestInterface {

                    public function __construct(
                        string $resourceName {
                            get {
                                return $this->resourceName;
                            }
                        },
                        string $selector {
                            get {
                                return $this->selector;
                            }
                        },
                        string|null $ticket {
                            get {
                                return $this->ticket;
                            }
                        },
                    ) {}

                }
            );

            $ticket = $response->ticket;

            if (!$response->lockedSuccess) {
                return new class($ticket) implements GetResourceDataResponseInterface {

                    public function __construct(
                        public string $ticket {get {return $this->ticket;}},
                    ) {}

                    public null $resourceData {get {return null;}}

                };
            }

        }

        try {
            $ressourceData = $this->resourceDataManager->findByNameAndSelector($request->resourceName, $request->selector);
        } catch (NotFoundException) {
            return new class($ticket) implements GetResourceDataResponseInterface {

                public function __construct(
                    public string $ticket {get {return $this->ticket;}},
                ) {}

                public null $resourceData {get {return null;}}

            };
        }

        return new class($ressourceData, $ticket) implements GetResourceDataResponseInterface {

            public function __construct(
                public ResourceData $resourceData,
                public string|null $ticket,
            ) {}

        };

    }

}
