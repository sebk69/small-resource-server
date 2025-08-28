<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\InterfaceAdapter\Gateway\UseCase\Response;

use Domain\Application\Entity\ResourceData;
use Small\CleanApplication\Contract\ResponseInterface;

interface LockResourceDataResponseInterface extends ResponseInterface
{

    public bool $lockedSuccess { get; }
    public string|null $ticket { get; }

}
