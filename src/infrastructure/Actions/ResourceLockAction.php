<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Actions;

use Domain\Application\Exception\ApplicationNotFoundException;
use Domain\Application\UseCase\GetResourceDataUseCase;
use Domain\Application\UseCase\LockResourceDataUseCase;
use Domain\Application\UseCase\UnlockResourceDataUseCase;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\GetResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\LockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UnlockResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\GetResourceDataResponseInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\LockResourceDataResponseInterface;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Infrastructure\Http\Exception\UnauthorizedException;
use Small\CleanApplication\Facade;
use Small\Forms\Form\FormBuilder;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class ResourceLockAction extends AbstractAction
{

    public function handle(Request $req, Response $res, string $resourceName, string $selector): void
    {

        $this->auth($req->header['x-api-key'] ?? null);
        if (!$this->canRead()) throw new UnauthorizedException('READ required');

        /** @var LockResourceDataResponseInterface $response */
        $response = Facade::execute(
            LockResourceDataUseCase::class,
            new class($resourceName, $selector, $this->ticket)
                implements LockResourceDataRequestInterface
            {
                public function __construct(
                    public string $resourceName {
                        get {
                            return $this->resourceName;
                        }
                    },
                    public string $selector {
                        get {
                            return $this->selector;
                        }
                    },
                    public string|null $ticket {
                        get {
                            return $this->ticket;
                        }
                    },
                ) {}

            }
        );


        $res->status(200);
        $res->header('Content-Type', 'application/json');
        $res->header('x-ticket', $response->ticket);
        $res->end(json_encode(['locked' => $response->lockedSuccess], JSON_UNESCAPED_UNICODE));

    }

}
