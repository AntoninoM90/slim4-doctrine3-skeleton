<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Request\User\CreateUserRequest;
use App\Domain\User\UserAlreadyExistsException;
use Fig\Http\Message\StatusCodeInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Post(
    path: '/user',
    tags: ['Users'],
    summary: 'Create a new user',
    operationId: 'createUser',
    requestBody: new OA\RequestBody(
        description: 'User data',
        required: true,
        content: new OA\JsonContent(
            required: ['username', 'password', 'emailAddress', 'firstName', 'lastName'],
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'Minimum 3 characters'),
                new OA\Property(
                    property: 'password',
                    type: 'string',
                    format: 'password',
                    description: 'Minimum 8 characters',
                ),
                new OA\Property(property: 'emailAddress', type: 'string', format: 'email'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 201,
            description: 'The created user',
            content: new OA\JsonContent(ref: '#/components/schemas/User'),
        ),
        new OA\Response(
            response: 409,
            description: 'Conflict: the username or email address was taken by a concurrent request',
            content: new OA\JsonContent(
                ref: '#/components/schemas/ErrorResponse',
                example: [
                    'statusCode' => 409,
                    'error' => [
                        'type' => 'RESOURCE_CONFLICT',
                        'description' => 'A user with this username or email address was created by another request.',
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
                            'username' => ['This username is already in use.'],
                            'password' => ['This value is too short. It should have 8 characters or more.'],
                        ],
                    ],
                ],
            ),
        ),
        new OA\Response(response: 500, ref: '#/components/responses/InternalServerError'),
    ],
)]
class CreateUserAction extends UserAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $request = $this->validateRequest(CreateUserRequest::class);

        $user = $request->toUser();

        try {
            $this->userRepository->createUser($user);
        } catch (UserAlreadyExistsException $e) {
            return $this->respondWithError(
                ActionError::RESOURCE_CONFLICT,
                $e->getMessage(),
                StatusCodeInterface::STATUS_CONFLICT
            );
        }

        $this->logger->info("The user with username `{$user->getUsername()}` was created.");

        return $this->respondWithData($user, StatusCodeInterface::STATUS_CREATED);
    }
}
