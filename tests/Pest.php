<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

$kernel = new \Infrastructure\Kernel()->boot('test');
\Infrastructure\Kernel::$resourceFactory = new class() extends \Small\SwoolePatterns\Resource\ResourceFactory {

    public $resource;

    public function __construct()
    {
        parent::__construct([], new \Small\SwoolePatterns\Manager\StoredListManager\TableStoredListManager(1));
    }

    public function get(string $name): \Small\SwoolePatterns\Resource\Bean\Resource
    { return $this->resource; }
};

pest()->extend(Tests\TestCase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/


// Helper to skip tests when swoole is missing
function requireSwoole(): void {
    if (!extension_loaded('swoole')) {
        test()->markTestSkipped('Swoole extension not loaded');
    }
}

class FakeRequest extends \Swoole\Http\Request {

    public string|false $rawContent = false;

    public function setRawContent($raw)
    {
        $this->rawContent = $raw;
    }

    public function rawContent(): string|false
    {

        return $this->rawContent;

    }

}

/**
 * Fake ResourceManager that lets us control the timeout for a resource.
 */
class FakeResourceManager implements \Domain\InterfaceAdapter\Gateway\Manager\ResourceManagerInterface {

    static public int $timeout = 0;

    public function findByName(string $name): \Domain\Application\Entity\Resource {
        $r = new \Domain\Application\Entity\Resource();
        $r->name = $name;
        $r->timeout = self::$timeout;
        return $r;
    }
    public function applicationPersist(\Domain\Application\Entity\Resource $resource): self { return $this; }

    public function existsByName(string $name): bool
    {
        return true;
    }
}

/**
 * A Resource stub that records the behaviour and returns a ticket with a chosen waiting state.
 */
class ResourceStub extends \Small\SwoolePatterns\Resource\Bean\Resource {
    public string $lastBehaviour = '';
    public ?string $incomingTicketId = null;
    public function __construct(private bool $returnWaiting, private string $ticketId) {
        parent::__construct('', new \Small\SwoolePatterns\Manager\StoredListManager\TableStoredListManager(1));
    }
    public function acquireResource($behaviour, $ticket = null): \Small\SwoolePatterns\Resource\Bean\Ticket
    {
        // record behaviour name when enum is present
        if (is_object($behaviour) && property_exists($behaviour, 'name')) {
            $this->lastBehaviour = $behaviour->name;
        } else {
            $this->lastBehaviour = (string)$behaviour;
        }

        if ($ticket !== null && method_exists($ticket, 'getTicketId')) {
            $this->incomingTicketId = $ticket->getTicketId();
        }

        $t = $ticket ?: new \Small\SwoolePatterns\Resource\Bean\Ticket($this->ticketId);
        // set waiting according to constructor flag
        if (method_exists($t, 'setWaiting')) {
            if ($behaviour == \Small\SwoolePatterns\Resource\Enum\GetResourceBehaviour::waitingForFree) {
                $t->setWaiting(false);
            } else {
                $t->setWaiting($this->returnWaiting);
            }
        }
        return $t;
    }

    public function releaseResource(\Small\SwoolePatterns\Resource\Bean\Ticket $ticket): self
    {
        $this->incomingTicketId = $ticket->getTicketId();
        return $this;
    }

}

class FakeResourceDataManager implements \Domain\InterfaceAdapter\Gateway\Manager\ResourceDataManagerInterface {
    public function findByNameAndSelector(string $resourceName, string $selector): \Domain\Application\Entity\ResourceData {
        $rd = new \Domain\Application\Entity\ResourceData();
        $rd->generateId();
        $rd->idResource = $resourceName;
        $rd->selector = $selector;
        $rd->data = json_encode(['ok' => true], JSON_THROW_ON_ERROR);
        return $rd;
    }
    public function applicationPersist(\Domain\Application\Entity\ResourceData $data): self { return $this; }
}

// Helper to build requests
function makeReq(string $method, string $uri, string $body = '', string|null $ticket = null): \Swoole\Http\Request {
    $r = new FakeRequest();
    $r->server = ['request_method' => $method, 'request_uri' => $uri];
    $r->header = ['x-api-key' => 'write'];
    if ($ticket != null) {
        $r->header['x-ticket'] = $ticket;
    }
    $r->get = [];
    $r->post = [];
    $r->rawContent = $body;
    return $r;
}

\Small\CleanApplication\Facade::setParameter('resourceManager', new FakeResourceManager());
\Small\CleanApplication\Facade::setParameter('resourceDataManager', new FakeResourceDataManager());
