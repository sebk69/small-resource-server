<?php

use Domain\Application\UseCase\CreateResourceUseCase;
use Domain\Application\Exception\ApplicationBadRequest;
use Small\CleanApplication\Contract\RequestInterface;
use Tests\Fakes\FakeResourceManager;

it('CreateResourceUseCase throws on invalid request type', function (): void {
    $uc = new CreateResourceUseCase(new \Tests\Feature\FakeManagers\FakeResourceManager());
    $invalid = new class implements RequestInterface {};
    $uc->execute($invalid);
})->throws(ApplicationBadRequest::class);

// --- success persist ---
it('CreateResourceUseCase persists and returns the created resource', function (): void {
    // Fake manager that records persistence and pretends the name is not taken
    $manager = new class implements \Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface {
        public ?\Domain\Application\Entity\Resource $persisted = null;
        public function findByName(string $name): \Domain\Application\Entity\Resource { return new \Domain\Application\Entity\Resource(); }
        public function existsByName(string $name): bool { return false; } // available
        public function applicationPersist(\Domain\Application\Entity\Resource $resource): \Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface {
            $this->persisted = $resource;
            return $this;
        }
    };

    $uc = new CreateResourceUseCase($manager);

    // Valid request DTO implementing the interface (PHP 8.4 property hooks)
    $request = new class('printer', 120) implements \Domain\InterfaceAdapter\Gateway\UseCase\Request\CreateResourceRequestInterface {
        public function __construct(private string $name_, private int $timeout_) {}
        public string $name { get { return $this->name_; } }
        public int $timeout { get { return $this->timeout_; } }
    };

    $response = $uc->execute($request);

    // Assertions
    expect($response->resource)->toBeInstanceOf(\Domain\Application\Entity\Resource::class);
    // The entity properties are public in your entities, so we can assert them directly
    expect($response->resource->name)->toBe('printer');
    expect($response->resource->timeout)->toBe(120);

    // Ensure persistence happened with the same entity
    expect($manager->persisted)->not()->toBeNull();
    expect($manager->persisted)->toBe($response->resource);
});