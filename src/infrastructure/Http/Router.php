<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Http;

use Infrastructure\Actions\ResourceLockAction;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Infrastructure\Actions\ResourceCreateAction;
use Infrastructure\Actions\ResourceGetAction;
use Infrastructure\Actions\ResourceUnlockAction;
use Infrastructure\Actions\ResourceUpdateAction;

final class Router
{
    public function dispatch(Request $req, Response $res): void
    {
        $path = $req->server['request_uri'] ?? '/';
        $method = strtoupper($req->server['request_method'] ?? 'GET');

        if ($path == '/health') {
            $res->status(200);
            $res->header('Content-Type', 'application/json');
            $res->end(json_encode(['heath' => 'ok'], JSON_UNESCAPED_UNICODE));
            return;
        }

        if ($method === 'POST' && $path === '/resource') {
            new ResourceCreateAction()->initRequest($req)->handle($req, $res); return;
        }

        if (preg_match('#^/resource/([^/]+)/([^/]+)/unlock$#', $path, $m)) {
            if ($method === 'PUT') {
                new ResourceUnlockAction()->initRequest($req)->handle($req, $res, $m[1], $m[2]);
                return;
            }
        }

        if (preg_match('#^/resource/([^/]+)/([^/]+)/lock$#', $path, $m)) {
            if ($method === 'PUT') {
                new ResourceLockAction()->initRequest($req)->handle($req, $res, $m[1], $m[2]);
                return;
            }
        }

        if (preg_match('#^/resource/([^/]+)/([^/]+)$#', $path, $m)) {
            if ($method === 'GET')  {
                new ResourceGetAction()->initRequest($req)->handle($req, $res, $m[1], $m[2]);
                return;
            }
            if ($method === 'PUT')  {
                new ResourceUpdateAction()->initRequest($req)->handle($req, $res, $m[1], $m[2]);
                return;
            }
        }

        throw new NotFoundHttpException('Route not found');
    }
}
