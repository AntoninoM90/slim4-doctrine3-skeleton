<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Domain\User\User;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Get(
    path: '/users',
    tags: ['Users'],
    summary: 'List of users with pagination',
    operationId: 'listUsers',
    parameters: [
        new OA\Parameter(
            name: 'page',
            in: 'query',
            required: false,
            description: 'Page number (default: 1)',
            schema: new OA\Schema(type: 'integer', minimum: 1),
        ),
        new OA\Parameter(
            name: 'perPage',
            in: 'query',
            required: false,
            description: 'Items per page (default: 10, maximum: 100)',
            schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100),
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'List of users with pagination',
            content: new OA\JsonContent(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        items: new OA\Items(ref: '#/components/schemas/User'),
                    ),
                    new OA\Property(property: 'pagination', ref: '#/components/schemas/Pagination'),
                ],
            ),
        ),
        new OA\Response(response: 500, ref: '#/components/responses/InternalServerError'),
    ],
)]
class ListUsersAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        /** @var array<string, mixed> $queryParams */
        $queryParams = $this->request->getQueryParams();

        $page = $this->positiveInt($queryParams, 'page', 1);
        $perPage = $this->positiveInt($queryParams, 'perPage', 10, 100);

        $result = $this->userRepository->findUsersWithPagination($perPage, ($page - 1) * $perPage);

        $this->logger->info("The paginated list of users was viewed.");

        return $this->respondWithData([
            'items' => $result['users'],
            'pagination' => [
                'total' => $result['total'],
                'page' => $page,
                'perPage' => $perPage,
                'totalPages' => (int) ceil($result['total'] / $perPage),
            ],
        ]);
    }
}
