<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Actions;

use Domain\Application\Exception\ApplicationNotFoundException;
use Domain\Application\UseCase\GetResourceDataUseCase;
use Domain\InterfaceAdapter\Gateway\UseCase\Request\GetResourceDataRequestInterface;
use Domain\InterfaceAdapter\Gateway\UseCase\Response\GetResourceDataResponseInterface;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Infrastructure\Http\Exception\UnauthorizedException;
use Small\CleanApplication\Facade;
use Small\Forms\Form\FormBuilder;
use Swoole\Http\Request;
use Swoole\Http\Response;

final class ResourceGetAction extends AbstractAction
{

    public function handle(Request $req, Response $res, string $resourceName, string $selector): void
    {
        $this->auth($req->header['x-api-key'] ?? null);
        if (!$this->canRead()) throw new UnauthorizedException('READ required');

        $body = json_decode($req->rawContent() ?: '{}', true) ?: [];
        $wantLock = (bool)($body['lock'] ?? false);
        if ($wantLock && !$this->canLock()) {
            throw new UnauthorizedException('LOCK required');
        }

        try {
            /** @var GetResourceDataResponseInterface $response */
            $response = Facade::execute(
                GetResourceDataUseCase::class,
                new class($resourceName, $selector, !(($req->get['lock'] ?? '1') == '0'), $this->ticket)
                    implements GetResourceDataRequestInterface
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
                        public bool $shouldLock {
                            get {
                                return $this->shouldLock;
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
        } catch (ApplicationNotFoundException $e) {
            throw new NotFoundHttpException($e->getMessage());
        }

        $res->setHeader('x-ticket', $response->ticket);

        if ($response->resourceData !== null) {
            $res->status(200);
            $res->header('Content-Type', 'application/json');
            $res->end(json_encode(
                FormBuilder::createFromAttributes($response->resourceData)
                    ->hydrate($response->resourceData)
                    ->toArray()
                , JSON_UNESCAPED_UNICODE));
        } else {
            $res->status(202);
            $res->header('Content-Type', 'application/json');
            $res->end(json_encode(['unavailable' => true], JSON_UNESCAPED_UNICODE));
        }

    }

}
