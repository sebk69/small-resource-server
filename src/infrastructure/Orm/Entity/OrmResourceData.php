<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Orm\Entity;

use Infrastructure\Orm\Manager\ResourceManager;
use Small\Forms\Form\Field\Type\StringType;
use Small\Forms\Form\Field\Type\IntType;
use Small\Forms\Form\Field\Type\SubFormType;
use Small\Forms\ValidationRule\ValidateNotEmpty;
use Small\Forms\ValidationRule\ValidateNumberCharsLessThan;
use Small\SwooleEntityManager\Entity\AbstractEntity;
use Small\SwooleEntityManager\Entity\Attribute\Field;
use Small\SwooleEntityManager\Entity\Attribute\OrmEntity;
use Small\SwooleEntityManager\Entity\Attribute\PrimaryKey;
use Small\SwooleEntityManager\Entity\Attribute\ToOne;
use Small\SwooleEntityManager\Entity\Enum\FieldValueType;

/**
 * @codeCoverageIgnore
 */
#[OrmEntity]
final class OrmResourceData extends AbstractEntity
{

    #[PrimaryKey]
    #[StringType]
    #[ValidateNotEmpty]
    #[ValidateNumberCharsLessThan(255)]
    public ?string $id = null;
    #[Field(FieldValueType::string)]
    #[StringType]
    #[ValidateNotEmpty]
    #[ValidateNumberCharsLessThan(255)]
    public ?string $idResource = null;
    #[Field(FieldValueType::string)]
    #[StringType]
    #[ValidateNumberCharsLessThan(255)]
    public ?string $selector = null;
    #[Field(FieldValueType::string)]
    #[StringType]
    #[ValidateNotEmpty]
    public ?string $data = null;

    #[ToOne(ResourceManager::class, ['idResource' => 'id'])]
    #[SubFormType(OrmResource::class)]
    public ?OrmResource $resource = null;

}
