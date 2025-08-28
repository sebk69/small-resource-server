<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Tests\Feature\FakeManagers;

use Domain\Application\Entity\ResourceData;
use Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface;

class FakeResourceDataManager implements ResourceDataManagerInterface {
    public function findByNameAndSelector(string $resourceName, string $selector): ResourceData {
        $rd = new ResourceData();
        $rd->generateId();
        $rd->idResource = $resourceName;
        $rd->selector = $selector;
        $rd->data = '{}';
        return $rd;
    }
    public function applicationPersist(ResourceData $data): self { return $this; }
}
