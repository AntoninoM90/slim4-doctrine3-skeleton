<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;

class DeleteUserActionTest extends UserActionTestCase
{
    public function testDeleteUserReturnsEmptyResponse()
    {
        $app = $this->getAppWithErrorHandling();

        $user = $this->createUser();
        $userId = (int) $user->getId();

        $request = $this->createRequest('DELETE', '/user/' . $userId);
        $response = $app->handle($request);

        $this->assertEquals(204, $response->getStatusCode());
        $this->assertSame('', (string) $response->getBody());

        $this->assertNull($this->getUserRepository()->findUserOfId($userId));
    }

    public function testDeleteUserNotFound()
    {
        $app = $this->getAppWithErrorHandling();

        $request = $this->createRequest('DELETE', '/user/' . $this->getNonExistentUserId());
        $response = $app->handle($request);

        $this->assertEquals(404, $response->getStatusCode());

        $payload = json_decode((string) $response->getBody(), true);

        $this->assertIsArray($payload);
        $this->assertSame(404, $payload['statusCode']);
        $this->assertSame(ActionError::RESOURCE_NOT_FOUND, $payload['error']['type']);
    }
}
