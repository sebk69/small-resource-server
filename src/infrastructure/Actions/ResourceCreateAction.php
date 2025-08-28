<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Actions;

use Domain\Application\Exception\ApplicationConflictException;
use Domain\Application\UseCase\CreateResourceUseCase;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\CreateResourceRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\CreateResourceResponseInterface;
use Infrastructure\Http\Exception\ConflictHttpException;
use Infrastructure\Http\Exception\UnauthorizedException;
use Infrastructure\Kernel;
use Small\CleanApplication\Facade;
use Small\Forms\Form\FormBuilder;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class ResourceCreateAction extends AbstractAction
{

    public function handle(Request $req, Response $res): void
    {

        if (!$this->canWrite()) throw new UnauthorizedException('WRITE required');

        $body = json_decode($req->rawContent() ?: '{}', true) ?: [];

        try {
            /** @var CreateResourceResponseInterface $response */
            $response = Facade::execute(
                CreateResourceUseCase::class,
                new class($body['name'], (int)($body['timeout'] ?? 0)) implements CreateResourceRequestInterface
                {
                    public function __construct(
                        public string $name,
                        public int $timeout,
                    ) {}
                }
            );
        } catch (ApplicationConflictException $e) {
            throw new ConflictHttpException($e->getMessage());
        }

        if (!Kernel::$test) {
            $res->status(201);
            $res->header('Content-Type', 'application/json');
            $res->end(json_encode(
                FormBuilder::createFromAttributes($response->resource)
                    ->hydrate($response->resource)
                    ->toArray()
                , JSON_UNESCAPED_UNICODE));
        } else {
            $res->status(201);
            $res->header('Content-Type', 'application/json');
        }

    }

}
