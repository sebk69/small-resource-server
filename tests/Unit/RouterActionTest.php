<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

use Infrastructure\Http\Router;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Infrastructure\Actions\ResourceGetAction;
use Infrastructure\Actions\ResourceUnlockAction;

class FakeCreateResourceUseCase implements \Small\CleanApplication\Contract\UseCaseInterface {

    public static bool $called = false;

    public function execute(\Small\CleanApplication\Contract\RequestInterface $request): \Small\CleanApplication\Contract\ResponseInterface
    {
        self::$called = true;
        return new class implements \Small\CleanApplication\Contract\ResponseInterface {};
    }

}

class FakeResourceGetUseCase implements \Small\CleanApplication\Contract\UseCaseInterface {

    public static bool $called = false;

    public function execute(\Small\CleanApplication\Contract\RequestInterface $request): \Small\CleanApplication\Contract\ResponseInterface
    {
        self::$called = true;
        return new class implements \Small\CleanApplication\Contract\ResponseInterface {};
    }

}

class FakeResourceUnlockUseCase implements \Small\CleanApplication\Contract\UseCaseInterface {

    public static bool $called = false;

    public function execute(\Small\CleanApplication\Contract\RequestInterface $request): \Small\CleanApplication\Contract\ResponseInterface
    {
        self::$called = true;
        return new class implements \Small\CleanApplication\Contract\ResponseInterface {};
    }

}

class FakeUpdateResourceUseCase implements \Small\CleanApplication\Contract\UseCaseInterface {

    public static bool $called = false;

    public function execute(\Small\CleanApplication\Contract\RequestInterface $request): \Small\CleanApplication\Contract\ResponseInterface
    {
        self::$called = true;
        return new class implements \Small\CleanApplication\Contract\ResponseInterface {};
    }

}

test('POST /resource dispatches to ResourceCreateAction', function (): void {

    $router = new Router();
    $req = makeReq('POST', '/resource', '{"name":"printer"}');
    $res = new Swoole\Http\Response();

    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\CreateResourceUseCase::class, FakeCreateResourceUseCase::class);
    $router->dispatch($req, $res);
    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\CreateResourceUseCase::class, null);

    expect(FakeCreateResourceUseCase::$called)->toBeTrue();
});

test('GET /resource/{name}/{selector} dispatches to ResourceGetAction', function (): void {

    $router = new Router();
    $req = makeReq('GET', '/resource/printer/A4');
    $res = new Swoole\Http\Response();

    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\GetResourceDataUseCase::class, FakeResourceGetUseCase::class);
    $router->dispatch($req, $res);
    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\GetResourceDataUseCase::class, null);

    expect(FakeResourceGetUseCase::$called)->toBeTrue();
});

test('GET /resource/{name}/{selector}/unlock dispatches to ResourceUnlockAction', function (): void {

    $router = new Router();
    $req = makeReq('PUT', '/resource/printer/A4/unlock');
    $res = new Swoole\Http\Response();

    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\UnlockResourceDataUseCase::class, FakeResourceUnlockUseCase::class);
    $router->dispatch($req, $res);
    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\UnlockResourceDataUseCase::class, null);

    expect(FakeResourceUnlockUseCase::$called)->toBeTrue();
});

test('PUT /resource/{name}/{selector} dispatches to ResourceUpdateAction', function (): void {

    $router = new Router();
    $req = makeReq('PUT', '/resource/printer/A4', '{"status":"ready"}', 'test-ticket');
    $res = new Swoole\Http\Response();

    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\UpdateResourceDataUseCase::class, FakeUpdateResourceUseCase::class);
    $router->dispatch($req, $res);
    \Small\CleanApplication\Facade::mock(\Domain\Application\UseCase\UpdateResourceDataUseCase::class, null);

    expect(FakeUpdateResourceUseCase::$called)->toBeTrue();
});

test('unknown route throws NotFoundHttpException', function (): void {
    $router = new Router();
    $req = makeReq('GET', '/nope');
    $res = new Swoole\Http\Response();

    expect(fn() => $router->dispatch($req, $res))->toThrow(NotFoundHttpException::class);
});