<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\Application\Exception;

use Small\Collection\Collection\StringCollection;

class ApplicationValidationFailException extends ApplicationException
{

    public StringCollection|null $validationMessages = null {
        get {
            if ($this->validationMessages === null) {
                return new StringCollection();
            }
            return $this->validationMessages;
        }
        set {
            $this->validationMessages = $value;
        }
    }

}