<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Request\User\ChangePasswordRequest;
use App\Domain\User\UserNotFoundException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Patch(
    path: '/user/{id}/password',
    tags: ['Users'],
    summary: 'Change a user password',
    operationId: 'changeUserPassword',
    parameters: [
        new OA\Parameter(
            name: 'id',
            in: 'path',
            required: true,
            description: 'The user id',
            schema: new OA\Schema(type: 'integer', format: 'int64'),
        ),
    ],
    requestBody: new OA\RequestBody(
        description: 'The new password',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            required: ['newPassword'],
            properties: [
                new OA\Property(
                    property: 'newPassword',
                    type: 'string',
                    format: 'password',
                    description: 'Minimum 8 characters',
                ),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'The user whose password was changed',
            content: new OA\JsonContent(ref: '#/components/schemas/User'),
        ),
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
        new OA\Response(
            response: 422,
            description: 'Validation error: the request payload is invalid',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: [
                    'statusCode' => 422,
                    'error' => [
                        'type' => 'VALIDATION_ERROR',
                        'description' => 'The request is invalid.',
                        'details' => [
                            'newPassword' => ['This value is too short. It should have 8 characters or more.'],
                        ],
                    ],
                ],
            ),
        ),
        new OA\Response(response: 500, ref: '#/components/responses/InternalServerError'),
    ],
)]
class ChangePasswordAction extends UserAction
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

        $request = ChangePasswordRequest::fromBody((array) $this->getFormData());

        $this->validateObject($request);

        assert($request->newPassword !== null);

        $user->setPassword(password_hash($request->newPassword, PASSWORD_BCRYPT));

        $this->userRepository->updateUser($user);

        $this->logger->info("The password of the user with ID `{$userId}` was changed.");

        return $this->respondWithData($user);
    }
}
