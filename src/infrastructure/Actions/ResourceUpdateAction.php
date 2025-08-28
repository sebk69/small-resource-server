<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Actions;

use Domain\Application\Exception\ApplicationNotFoundException;
use Domain\Application\UseCase\UpdateResourceDataUseCase;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\UpdateResourceDataRequestInterface;
use Infrastructure\Http\Exception\HttpException;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Infrastructure\Http\Exception\UnauthorizedException;
use Small\CleanApplication\Facade;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class ResourceUpdateAction extends AbstractAction
{

    public function handle(Request $req, Response $res, string $resourceName, string $selector): void
    {

        $this->auth($req->header['x-api-key'] ?? null);
        if (!$this->canWrite()) throw new UnauthorizedException('WRITE required');

        $json = $req->rawContent() ?: '{}';

        if (empty($this->ticket)) {
            throw new HttpException('ticket is mandatory');
        }

        try {
            Facade::execute(

                UpdateResourceDataUseCase::class,

                new class($resourceName, $selector, $this->ticket, $json) implements UpdateResourceDataRequestInterface {

                    public function __construct(
                        public string $resourceName { get {return $this->resourceName; }},
                        public string|null $selector { get {return $this->selector; }},
                        public string $ticket { get {return $this->ticket; }},
                        public string $json { get {return $this->json; }},
                    ) {}

                }

            );
        } catch (ApplicationNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $res->status(204);
        $res->end();

    }

}
