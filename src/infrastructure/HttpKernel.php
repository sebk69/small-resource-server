<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure;

use Infrastructure\Http\Exception\ConflictHttpException;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Infrastructure\Http\Exception\UnauthorizedException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Swoole\Http\Server;
use Infrastructure\Http\Router;

/**
 * @codeCoverageIgnore
 */
final class HttpKernel extends Kernel
{

    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
    }

    public function run(string $host='0.0.0.0', int $port=9501): self
    {

        parent::boot();

        $server = new Server($host, $port);
        $server->set(['user' => 'www-data', 'group' => 'www-data']);

        $server->on('request', function (Request $req, Response $res): void {
            try {
                $this->router->dispatch($req, $res);
            } catch (\Throwable $e) {
                $code = 500;
                $msg = $e->getMessage();
                if ($e instanceof NotFoundHttpException) $code = 404;
                if ($e instanceof UnauthorizedException) $code = 401;
                if ($e instanceof ConflictHttpException) $code = 409;

                $res->status($code);
                $res->header('Content-Type', 'application/json');
                $res->end(json_encode(['error' => $msg], JSON_UNESCAPED_UNICODE));
            }
        });

        $server->start();

        return $this;

    }

}
