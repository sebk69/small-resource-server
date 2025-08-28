<?php
/*
 * This file is a part of Small Swoole Resource Server
 * Copyright 2025 - Sébastien Kus
 * Under MIT Licence
 */

namespace Infrastructure\Actions;


use Infrastructure\Http\Exception\UnauthorizedException;
use Infrastructure\Kernel;
use Swoole\Http\Request;
use Swoole\Http\Response;

abstract class AbstractAction
{

    public const AUTH_READ  = 'READ';
    public const AUTH_LOCK  = 'LOCK';
    public const AUTH_WRITE = 'WRITE';

    /** @var array<string> */
    protected array $authorizations = [];
    protected private(set) string|null $ticket;

    public function auth(?string $apiKey): void
    {
        $this->authorizations = [];
        if ($apiKey === null) {
            throw new UnauthorizedException('Missing API key');
        }

        if (empty(Kernel::$env->get('RESOURCE_READ', ''))) {
            throw new UnauthorizedException('Missing RESOURCE_READ');
        }
        if (empty(Kernel::$env->get('RESOURCE_READ_LOCK', ''))) {
            throw new UnauthorizedException('Missing RESOURCE_READ_LOCK');
        }
        if (empty(Kernel::$env->get('RESOURCE_WRITE', ''))) {
            throw new UnauthorizedException('Missing RESOURCE_WRITE');
        }

        if ($apiKey === Kernel::$env->get('RESOURCE_READ', '')) {
            $this->authorizations = [self::AUTH_READ];
        }
        if ($apiKey === Kernel::$env->get('RESOURCE_READ_LOCK', '')) {
            $this->authorizations = [self::AUTH_READ, self::AUTH_LOCK];
        }

        if ($apiKey === Kernel::$env->get('RESOURCE_WRITE', '')) {
            $this->authorizations = [self::AUTH_READ, self::AUTH_LOCK, self::AUTH_WRITE];
        }

        if (empty($this->authorizations)) {
            throw new UnauthorizedException('Unauthorized');
        }
    }

    public function initRequest(Request $request): self
    {

        $this->auth($request->header['x-api-key'] ?? null);
        $this->ticket = $request->header['x-ticket'] ?? null;

        return $this;

    }

    protected function canRead(): bool  { return in_array(self::AUTH_READ, $this->authorizations, true); }
    protected function canLock(): bool  { return in_array(self::AUTH_LOCK, $this->authorizations, true); }
    protected function canWrite(): bool { return in_array(self::AUTH_WRITE, $this->authorizations, true); }

}
