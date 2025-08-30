<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Tests\Feature\FakeManagers;

use Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface;
use Domain\Application\Entity\Resource;
use Domain\Application\Entity\ResourceData;

class FakeResourceManager implements ResourceManagerInterface {
    public function findByName(string $name): Resource {
        $r = new Resource();
        $r->generateId();
        $r->name = $name;
        return $r;
    }
    public function applicationPersist(Resource $resource): self { return $this; }

    public function existsByName(string $name): bool
    {
        return false;
    }
}
