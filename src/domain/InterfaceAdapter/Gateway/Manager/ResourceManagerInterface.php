<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Domain\InterfaceAdapter\Gateway\Manager;

use Domain\Application\Entity\Resource;

interface ResourceManagerInterface
{
    public function findByName(string $name): Resource;          // NotFound => throw
    public function existsByName(string $name): bool;
    public function applicationPersist(Resource $resource): self;
}
