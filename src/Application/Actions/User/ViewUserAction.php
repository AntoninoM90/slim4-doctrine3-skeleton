<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Domain\User\UserNotFoundException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Get(
    path: '/user/{id}',
    tags: ['Users'],
    summary: 'Show a single user',
    operationId: 'viewUser',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'The user id',
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'A single user',
            content: new OA\JsonContent(ref: '#/components/schemas/User')
        ),
        new OA\Response(response: 404, description: 'User not found'),
    ],
)]
class ViewUserAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $userId = (int) $this->resolveArg('id');

        $user = $this->userRepository->findUserOfId($userId);

        if (!$user) {
            throw new UserNotFoundException();
        }

        $this->logger->info("The user with ID `{$userId}` was viewed.");

        return $this->respondWithData($user);
    }
}
