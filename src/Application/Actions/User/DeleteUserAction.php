<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Domain\User\UserNotFoundException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Delete(
    path: '/user/{id}',
    tags: ['Users'],
    summary: 'Delete a user',
    operationId: 'deleteUser',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'The user id',
            schema: new OA\Schema(type: 'integer', format: 'int64'),
        ),
    ],
    responses: [
        new OA\Response(response: 204, description: 'User deleted'),
        new OA\Response(
            response: 404,
            description: 'User not found',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: [
                    'statusCode' => 404,
                    'error' => [
                        'type' => 'RESOURCE_NOT_FOUND',
                        'description' => 'The user you requested does not exist.',
                    ],
                ],
            ),
        ),
        new OA\Response(response: 500, ref: '#/components/responses/InternalServerError'),
    ],
)]
class DeleteUserAction extends UserAction
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

        $this->userRepository->deleteUser($user);

        $this->logger->info("The user with ID `{$userId}` was deleted.");

        return $this->respondWithEmpty();
    }
}
