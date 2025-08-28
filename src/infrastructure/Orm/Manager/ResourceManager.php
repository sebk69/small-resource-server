<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Orm\Manager;

use Domain\InterfaceAdapter\Gateway\Manager\Exception\NotFoundException;
use Domain\InterfaceAdapter\Gateway\Manager\Exception\OrmException;
use Infrastructure\Orm\Entity\OrmResource;
use Domain\Application\Entity\Resource;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface;
use Small\Forms\Form\FormBuilder;
use Small\SwooleEntityManager\EntityManager\AbstractRelationnalManager;
use Small\SwooleEntityManager\EntityManager\Attribute\Connection;
use Small\SwooleEntityManager\EntityManager\Attribute\Entity;
use Small\SwooleEntityManager\EntityManager\Exception\EmptyResultException;
use Small\SwooleEntityManager\QueryBuilder\RelationalQueryBuilder\Enum\ConditionOperatorType;

/**
 * @codeCoverageIgnore
 */
#[Connection('resource')]
#[Entity(OrmResource::class)]
final class ResourceManager extends AbstractRelationnalManager
    implements ResourceManagerInterface
{

    public function findByName(string $name): Resource
    {

        try {
            $orm = $this->findOneBy(['name' => $name]);
        } catch (EmptyResultException $e) {
            throw new NotFoundException('Resource not found');
        } catch (\Exception $e) {
            throw new OrmException($e->getMessage());
        }

        FormBuilder::createFromAttributes($domain = new Resource())
            ->fillFromObject($orm)
            ->hydrate($domain);

        return $domain;

    }

    public function existsByName(string $name): bool
    {
        try { $this->findByName($name); return true; }
        catch (NotFoundException) { return false; }
    }

    public function applicationPersist(Resource $resource): self
    {

        try {
            /** @var OrmResource $orm */
            $orm = $this->findOneBy(['id' => $resource->getId()]);
        } catch (EmptyResultException) {
            $orm = $this->newEntity();
        }

        FormBuilder::createFromAttributes($orm)
            ->fillFromObject($resource)
            ->hydrate($orm);

        $orm->persist();

        return $this;

    }

}
