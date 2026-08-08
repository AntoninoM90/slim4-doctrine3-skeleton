<?php

declare(strict_types=1);

namespace App\Application\Exception;

use Fig\Http\Message\StatusCodeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Exception\HttpException;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class HttpValidationException extends HttpException
{
    public function __construct(
        ServerRequestInterface $request,
        private readonly ConstraintViolationListInterface $violations
    ) {
        parent::__construct(
            $request,
            'The request is invalid.',
            StatusCodeInterface::STATUS_UNPROCESSABLE_ENTITY
        );
    }

    public function getViolations(): ConstraintViolationListInterface
    {
        return $this->violations;
    }
}
