<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Domain\User\User;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Get(
    path: '/users',
    tags: ['Users'],
    summary: 'List all users',
    operationId: 'listUsers',
    responses: [
        new OA\Response(
            response: 200,
            description: 'List of users',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/User')
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
        $users = $this->userRepository->findAllUsers();

        $this->logger->info("The list of users was viewed.");

        return $this->respondWithData($users);
    }
}
