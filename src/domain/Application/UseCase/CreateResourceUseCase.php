<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\UseCase;

use Domain\Application\Entity\Resource;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\Application\Exception\ApplicationConflictException;
use Domain\Application\Exception\ApplicationValidationFailException;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\CreateResourceRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\CreateResourceResponseInterface;
use Small\CleanApplication\Contract\RequestInterface;
use Small\CleanApplication\Contract\UseCaseInterface;
use Small\Collection\Collection\StringCollection;
use Small\Forms\Form\FormBuilder;
use Small\Forms\ValidationRule\Exception\ValidationFailException;

final class CreateResourceUseCase implements UseCaseInterface
{
    public function __construct(
        private ResourceManagerInterface $resourceManager
    ) {}

    public function execute(RequestInterface $request): CreateResourceResponseInterface
    {

        if (!$request instanceof CreateResourceRequestInterface) {
            throw new ApplicationBadRequest(
                'Request must be an instance of ' . CreateResourceRequestInterface::class
            );
        }

        if ($this->resourceManager->existsByName($request->name)) {
            throw new ApplicationConflictException('Resource already exists');
        }

        $entity = new Resource()
            ->generateId();
        $entity->name = $request->name;
        $entity->timeout = $request->timeout;

        $messages = new StringCollection();
        try {
            FormBuilder::createFromAttributes($entity)
                ->hydrate($entity)
                ->validate($messages, true);
        } catch (ValidationFailException $e) {
            $appException = new ApplicationValidationFailException($e->getMessage());
            $appException->validationMessages = $messages;
            throw $appException;
        }

        $this->resourceManager->applicationPersist($entity);

        return new class($entity) implements CreateResourceResponseInterface {
            public function __construct(public Resource $resource {get {return $this->resource;}}) {}
        };

    }
}
