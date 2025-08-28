<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\Entity;

use Domain\Application\Entity\Trait\HasIdentifier;
use Small\Forms\Form\Field\Type\StringType;
use Small\Forms\ValidationRule\ValidateJson;
use Small\Forms\ValidationRule\ValidateNotEmpty;
use Small\Forms\ValidationRule\ValidateString;

/**
 * @codeCoverageIgnore
 */
final class ResourceData
{

    use HasIdentifier;

    #[StringType]
    #[ValidateString]
    #[ValidateNotEmpty]
    public ?string $idResource = null;
    #[StringType]
    #[ValidateString]
    public ?string $selector = null;
    #[StringType]
    #[ValidateString]
    #[ValidateJson]
    public string $data = 'null';

}
