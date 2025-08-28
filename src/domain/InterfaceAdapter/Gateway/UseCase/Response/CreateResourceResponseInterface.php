<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\InterfaceAdapter\Gateway\UseCase\Response;

use Domain\Application\Entity\Resource;
use Small\CleanApplication\Contract\ResponseInterface;

interface CreateResourceResponseInterface extends ResponseInterface
{

    public Resource $resource {
        get;
    }

}
