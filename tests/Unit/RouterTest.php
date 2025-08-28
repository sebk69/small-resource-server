<?php

use Infrastructure\Http\Router;

test('Router throws NotFound on unknown route', function (): void {
    requireSwoole();
    $router = new Router();
    $req = new Swoole\Http\Request();
    $res = new Swoole\Http\Response(1);
    $req->server = ['request_uri' => '/unknown', 'request_method' => 'GET'];
    expect(fn() => $router->dispatch($req, $res))->toThrow(\Infrastructure\Http\Exception\NotFoundHttpException::class);
})->skip(!extension_loaded('swoole'), 'Swoole not loaded');
