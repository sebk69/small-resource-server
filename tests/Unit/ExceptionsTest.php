<?php

use Infrastructure\Http\Exception\HttpException;
use Infrastructure\Http\Exception\UnauthorizedException;
use Infrastructure\Http\Exception\NotFoundHttpException;
use Infrastructure\Http\Exception\ConflictHttpException;

use Domain\Application\Exception\ApplicationException;
use Domain\Application\Exception\ApplicationBadRequest;
use Domain\Application\Exception\ApplicationConflictException;
use Domain\Application\Exception\ApplicationNotFoundException;
use Domain\Application\Exception\ApplicationValidationFailException;

test('HTTP exceptions hierarchy', function (): void {
    expect(is_subclass_of(UnauthorizedException::class, HttpException::class))->toBeTrue();
    expect(is_subclass_of(NotFoundHttpException::class, HttpException::class))->toBeTrue();
    expect(is_subclass_of(ConflictHttpException::class, HttpException::class))->toBeTrue();
});

test('Application exceptions hierarchy', function (): void {
    expect(is_subclass_of(ApplicationBadRequest::class, ApplicationException::class))->toBeTrue();
    expect(is_subclass_of(ApplicationConflictException::class, ApplicationException::class))->toBeTrue();
    expect(is_subclass_of(ApplicationNotFoundException::class, ApplicationException::class))->toBeTrue();
    expect(is_subclass_of(ApplicationValidationFailException::class, ApplicationException::class))->toBeTrue();
});
