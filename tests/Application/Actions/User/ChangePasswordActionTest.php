<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;

class ChangePasswordActionTest extends UserActionTestCase
{
    private const USER_PASSWORD_ROUTE = '/user/';

    public function testChangePasswordReturnsUpdatedUserAndPersists()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser();
        $userId = (int) $user->getId();

        $request = $this->createRequest('PATCH', self::USER_PASSWORD_ROUTE . $userId . '/password')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'newPassword' => 'new-secret-123',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(self::CONTENT_TYPE_JSON, $response->getHeaderLine('Content-Type'));

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(200, $payload['statusCode']);
        $this->assertSame($userId, $payload['data']['id']);

        // Re-read from the database with a fresh repository: the new password
        // must have been hashed and flushed.
        $storedUser = $this->getUserRepository()->findUserOfId($userId);

        $this->assertNotNull($storedUser);
        $this->assertTrue(password_verify('new-secret-123', $storedUser->getPassword()));
        $this->assertFalse(password_verify('password123', $storedUser->getPassword()));
    }

    public function testChangePasswordWithInvalidBodyReturnsValidationError()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser();
        $userId = (int) $user->getId();

        $request = $this->createRequest('PATCH', self::USER_PASSWORD_ROUTE . $userId . '/password')
            ->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'newPassword' => 'short',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(422, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(422, $payload['statusCode']);
        $this->assertSame(ActionError::VALIDATION_ERROR, $payload['error']['type']);
        $this->assertArrayHasKey('newPassword', $payload['error']['details']);
    }

    public function testChangePasswordUserNotFound()
    {
        $app = $this->getAppWithErrorHandling();

        $request = $this->createRequest(
            'PATCH',
            self::USER_PASSWORD_ROUTE . $this->getNonExistentUserId() . '/password'
        )->withHeader('Content-Type', self::CONTENT_TYPE_JSON);
        $request->getBody()->write(json_encode([
            'newPassword' => 'new-secret-123',
        ], JSON_THROW_ON_ERROR));

        $response = $app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(404, $payload['statusCode']);
        $this->assertSame(ActionError::RESOURCE_NOT_FOUND, $payload['error']['type']);
    }
}
