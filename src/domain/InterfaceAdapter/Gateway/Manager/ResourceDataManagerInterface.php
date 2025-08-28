<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\InterfaceAdapter\Gateway\Manager;

use Domain\Application\Entity\ResourceData;

interface ResourceDataManagerInterface
{
    public function findByNameAndSelector(string $resourceName, string $selector): ResourceData; // NotFound => throw
    public function applicationPersist(ResourceData $data): self;
}
