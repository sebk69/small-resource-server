<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\UseCase;

use Domain\Application\Entity\ResourceData;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\Application\Exception\ApplicationConflictException;
use Domain\Application\Exception\ApplicationNotFoundException;
use Domain\Application\Exception\ApplicationValidationFailException;
use Domain\InterfaceAdapter\Gateway\Manager\Exception\NotFoundException;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UnlockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UpdateResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Infrastructure\Orm\Manager\ResourceManager;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\ResponseInterface;
use Small\CleanApplication\Contract\UseCaseInterface;
use Small\Collection\Collection\StringCollection;
use Small\Forms\Form\FormBuilder;
use Small\Forms\ValidationRule\Exception\ValidationFailException;

final class UpdateResourceDataUseCase implements UseCaseInterface
{
    public function __construct(
        private ResourceManagerInterface $resourceManager,
        private ResourceDataManagerInterface $resourceDataManager,
        private LockResourceDataUseCase $lockResourceDataUseCase,
        private UnlockResourceDataUseCase $unlockResourceDataUseCase,
    ) {}

    public function execute(RequestInterface $request): ResponseInterface
    {

        if (!$request instanceof UpdateResourceDataRequestInterface) {
            throw new ApplicationBadRequest(
                'Request must be an instance of ' . UpdateResourceDataRequestInterface::class
            );
        }

        /** @var LockResourceDataResponseInterface $response */
        $response = $this->lockResourceDataUseCase->execute(
            new class($request->resourceName, $request->selector, $request->ticket)
                implements LockResourceDataRequestInterface
            {

                public function __construct(
                    public string $resourceName,
                    public string $selector,
                    public string $ticket,
                ) {}

            }
        );

        if ($response->lockedSuccess && $request->ticket == $response->ticket) {

            try {
                $resourceData = $this->resourceDataManager->findByNameAndSelector(
                    $request->resourceName,
                    $request->selector
                );
            } catch (NotFoundException) {

                $resource = $this->resourceManager->findByName($request->resourceName);

                $resourceData = new ResourceData()->generateId();
                $resourceData->idResource = $resource->getId();
                $resourceData->selector = $request->selector;

            }

            $resourceData->data = $request->json;

            $messages = new StringCollection();
            try {
                FormBuilder::createFromAttributes($resourceData)
                    ->hydrate($resourceData)
                    ->validate($messages, true);
            } catch (ValidationFailException $e) {
                $appException = new ApplicationValidationFailException($e->getMessage());
                $appException->validationMessages = $messages;
                throw $appException;
            }

            $this->resourceDataManager->applicationPersist($resourceData);

            $this->unlockResourceDataUseCase->execute(
                new class($request->resourceName, $request->selector, $request->ticket)
                    implements UnlockResourceDataRequestInterface
                {

                    public function __construct(
                        public string $resourceName,
                        public string $selector,
                        public string $ticket,
                    ) {}

                }
            );

            return new class implements ResponseInterface {};

        }

        $appException = new ApplicationValidationFailException('You have no lock rights');
        $appException->validationMessages = new StringCollection(['Lock failed']);
        throw $appException;

    }

}
