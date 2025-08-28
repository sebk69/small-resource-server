<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Orm\Manager;

use Domain\InterfaceAdapter\Gateway\Manager\Exception\NotFoundException;
use Infrastructure\Orm\Entity\OrmResource;
use Infrastructure\Orm\Entity\OrmResourceData;
use Domain\Application\Entity\ResourceData;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface;
use Small\Forms\Form\FormBuilder;
use Small\SwooleEntityManager\EntityManager\AbstractRelationnalManager;
use Small\SwooleEntityManager\EntityManager\Attribute\Connection;
use Small\SwooleEntityManager\EntityManager\Attribute\Entity;
use Small\SwooleEntityManager\EntityManager\Exception\EmptyResultException;
use Small\SwooleEntityManager\QueryBuilder\RelationalQueryBuilder\Enum\ConditionOperatorType;

/**
 * @codeCoverageIgnore
 */
#[Connection('resource_data')]
#[Entity(OrmResourceData::class)]
final class ResourceDataManager extends AbstractRelationnalManager implements ResourceDataManagerInterface
{

    public function findByNameAndSelector(string $resourceName, string $selector): ResourceData
    {

        $query = $this->createQueryBuilder('resourceData')
            ->innerJoin('resourceData', 'resource')->endJoin();
        $query->where()
            ->firstCondition(
                $query->getFieldForCondition('name', 'resource'),
                ConditionOperatorType::equal,
                ':resourceName'
            )->andCondition(
                $query->getFieldForCondition('selector', 'resourceData'),
                ConditionOperatorType::equal,
                ':selector'
            );

        $query->setParameter('resourceName', $resourceName);
        $query->setParameter('selector', $selector);

        /** @var OrmResourceData $orm */
        $orm = $this->getResult($query)->first();

        if ($orm === null) {
            throw new NotFoundException('Resource data not found');
        }

        \Small\Forms\Form\FormBuilder::createFromAttributes($domain = new ResourceData())
            ->fillFromObject($orm)
            ->hydrate($domain);

        return $domain;

    }

    public function applicationPersist(ResourceData $data): self
    {

        try {
            /** @var OrmResourceData $orm */
            $orm = $this->findOneBy(['id' => $data->getId()]);
        } catch (EmptyResultException) {
            $orm = $this->newEntity();
        }

        FormBuilder::createFromAttributes($orm)
            ->fillFromObject($data)
            ->hydrate($orm);

        $orm->persist();

        return $this;

    }

}
