<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\InterfaceAdapter\Gateway\UseCase\Request;

use Small\CleanApplication\Contract\RequestInterface;

interface CreateResourceRequestInterface extends RequestInterface
{

    public string $name {
        get;
    }

    public int $timeout {
        get;
    }

}
