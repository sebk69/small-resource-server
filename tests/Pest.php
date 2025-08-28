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