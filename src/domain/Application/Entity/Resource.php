<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\Entity;

use Domain\Application\Entity\Trait\HasIdentifier;
use Small\Forms\Form\Field\Type\IntType;
use Small\Forms\Form\Field\Type\StringType;
use Small\Forms\ValidationRule\ValidateGreater;
use Small\Forms\ValidationRule\ValidateInt;
use Small\Forms\ValidationRule\ValidateNotEmpty;
use Small\Forms\ValidationRule\ValidateString;

/**
 * @codeCoverageIgnore
 */
class Resource
{

    const int fallbackTimeout = 0;

    use HasIdentifier;

    #[StringType]
    #[ValidateString]
    #[ValidateNotEmpty]
    public ?string $name = null;
    #[IntType]
    #[ValidateInt]
    #[ValidateGreater(0)]
    public int|null $timeout {
        get {
            if ($this->timeout === null) {
                return self::fallbackTimeout;
            }

            return $this->timeout;
        }
        set {
            if (empty($value)) {
                $this->timeout = self::fallbackTimeout;
            } else {
                $this->timeout = $value;
            }
        }
    } // en secondes

}
