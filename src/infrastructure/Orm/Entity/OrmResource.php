<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Orm\Entity;

use Small\Forms\Form\Field\Type\StringType;
use Small\Forms\Form\Field\Type\IntType;
use Small\Forms\ValidationRule\ValidateNotEmpty;
use Small\Forms\ValidationRule\ValidateNotNull;
use Small\Forms\ValidationRule\ValidateNumberCharsLessThan;
use Small\SwooleEntityManager\Entity\AbstractEntity;
use Small\SwooleEntityManager\Entity\Attribute\Field;
use Small\SwooleEntityManager\Entity\Attribute\OrmEntity;
use Small\SwooleEntityManager\Entity\Attribute\PrimaryKey;
use Small\SwooleEntityManager\Entity\Enum\FieldValueType;

/**
 * @codeCoverageIgnore
 */
#[OrmEntity]
final class OrmResource extends AbstractEntity
{

    #[PrimaryKey]
    #[StringType]
    #[ValidateNotEmpty]
    #[ValidateNumberCharsLessThan(255)]
    public ?string $id = null;
    #[Field(FieldValueType::string)]
    #[StringType]
    #[ValidateNotNull]
    #[ValidateNumberCharsLessThan(255)]
    public ?string $name = null;
    #[Field(FieldValueType::int)]
    #[IntType]
    #[ValidateNotEmpty]
    public ?int $timeout = null;

}
