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
class OpenApiDocumentation
{
}
