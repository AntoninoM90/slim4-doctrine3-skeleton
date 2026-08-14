<?php

declare(strict_types=1);

namespace App\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Request\User\UpdateUserRequest;
use App\Domain\User\UserAlreadyExistsException;
use App\Domain\User\UserNotFoundException;
use Fig\Http\Message\StatusCodeInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;

#[OA\Patch(
    path: '/user/{id}',
    tags: ['Users'],
    summary: 'Update a user',
    operationId: 'updateUser',
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
        description: 'Fields to update',
        required: true,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'username', type: 'string', description: 'Minimum 3 characters'),
                new OA\Property(property: 'emailAddress', type: 'string', format: 'email'),
                new OA\Property(property: 'firstName', type: 'string'),
                new OA\Property(property: 'lastName', type: 'string'),
            ],
        ),
    ),
    responses: [
        new OA\Response(
            response: 200,
            description: 'The updated user',
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
                        ],
                    ],
                ],
            ),
        ),
        new OA\Response(response: 500, ref: '#/components/responses/InternalServerError'),
    ],
)]
class UpdateUserAction extends UserAction
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

        $request = UpdateUserRequest::fromBody((array) $this->getFormData());
        $request->excludedUserId = $userId;

        $this->validateObject($request);

        if ($request->username !== null) {
            $user->setUsername($request->username);
        }

        if ($request->emailAddress !== null) {
            $user->setEmailAddress($request->emailAddress);
        }

        if ($request->firstName !== null) {
            $user->setFirstName($request->firstName);
        }

        if ($request->lastName !== null) {
            $user->setLastName($request->lastName);
        }

        try {
            $this->userRepository->updateUser($user);
        } catch (UserAlreadyExistsException $e) {
            return $this->respondWithError(
                ActionError::RESOURCE_CONFLICT,
                $e->getMessage(),
                StatusCodeInterface::STATUS_CONFLICT
            );
        }

        $this->logger->info("The user with ID `{$userId}` was updated.");

        return $this->respondWithData($user);
    }
}
