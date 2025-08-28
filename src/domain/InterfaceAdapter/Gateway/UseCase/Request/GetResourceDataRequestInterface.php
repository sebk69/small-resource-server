<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\InterfaceAdapter\Gateway\UseCase\Request;

use Small\CleanApplication\Contract\RequestInterface;

interface GetResourceDataRequestInterface extends RequestInterface
{

    public string $resourceName { get; }
    public string|null $selector { get; }
    public bool $shouldLock { get; }
    public string|null $ticket { get; }

}
