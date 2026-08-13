<?php

declare(strict_types=1);

namespace App\Application\Documentation;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.0.0',
    title: 'Slim 4 Doctrine 3 Skeleton API',
    description: 'REST API documentation for the Slim 4 Doctrine 3 skeleton application.',
)]
#[OA\Server(
    url: 'http://localhost:8080',
    description: 'Development server',
)]
#[OA\Tag(
    name: 'Users',
    description: 'User endpoints',
)]
#[OA\Tag(
    name: 'System',
    description: 'System endpoints',
)]
#[OA\Schema(
    schema: 'User',
    type: 'object',
    description: 'A user account',
    properties: [
        new OA\Property(property: 'id', type: 'integer', format: 'int64'),
        new OA\Property(property: 'username', type: 'string'),
        new OA\Property(property: 'emailAddress', type: 'string'),
        new OA\Property(property: 'firstName', type: 'string'),
        new OA\Property(property: 'lastName', type: 'string'),
    ],
)]
#[OA\Schema(
    schema: 'Error',
    type: 'object',
    description: 'The error of a failed request.',
    properties: [
        new OA\Property(
            property: 'type',
            type: 'string',
            description: 'A machine readable error type, e.g. VALIDATION_ERROR',
        ),
        new OA\Property(
            property: 'description',
            type: 'string',
            description: 'A human readable description of the error',
        ),
        new OA\Property(
            property: 'details',
            type: 'object',
            description: 'Validation errors grouped by field name',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
)]
#[OA\Schema(
    schema: 'ErrorResponse',
    type: 'object',
    description: 'A failed request payload.',
    properties: [
        new OA\Property(property: 'statusCode', type: 'integer', format: 'int32'),
        new OA\Property(property: 'error', ref: '#/components/schemas/Error'),
    ],
)]
#[OA\Schema(
    schema: 'HealthCheck',
    type: 'object',
    description: 'The health check result.',
    properties: [
        new OA\Property(property: 'status', type: 'string', enum: ['ok', 'unhealthy']),
        new OA\Property(
            property: 'checks',
            type: 'object',
            properties: [
                new OA\Property(property: 'database', type: 'string', enum: ['ok', 'error']),
                new OA\Property(property: 'cache', type: 'string', enum: ['ok', 'error']),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'Pagination',
    type: 'object',
    description: 'Pagination metadata',
    properties: [
        new OA\Property(property: 'total', type: 'integer', format: 'int64'),
        new OA\Property(property: 'page', type: 'integer'),
        new OA\Property(property: 'perPage', type: 'integer'),
        new OA\Property(property: 'totalPages', type: 'integer'),
    ],
)]
#[OA\Response(
    response: 'InternalServerError',
    description: 'An unexpected error occurred.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/ErrorResponse',
        example: [
            'statusCode' => 500,
            'error' => [
                'type' => 'SERVER_ERROR',
                'description' => 'An internal error has occurred while processing your request.',
            ],
        ],
    ),
)]
class OpenApiDocumentation
{
}
