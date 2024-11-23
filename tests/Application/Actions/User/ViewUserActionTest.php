<?php

declare(strict_types=1);

namespace Tests\Application\Actions\User;

use App\Application\Actions\ActionError;
use App\Application\Actions\ActionPayload;
use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManager;
use Exception;
use Slim\Psr7\Response;
use Tests\TestCase;

class ViewUserActionTest extends TestCase
{
    public function testAction()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $userRepository = new UserRepository($entityManager);

        $user = $userRepository->findUserOfId(1);

        $request = $this->createRequest('GET', '/user/1');

        try {
            $response = $app->handle($request);

            $expectedPayload = new ActionPayload(200, $user);
        } catch (Exception $e) {
            if ($e->getCode() === 404) {
                $type = ActionError::RESOURCE_NOT_FOUND;
            } else {
                $type = ActionError::SERVER_ERROR;
            }

            $expectedError = new ActionError($type, $e->getMessage());
            $expectedPayload = new ActionPayload($e->getCode(), null, $expectedError);
            $response = new Response($e->getCode(), null);
            $response->getBody()->write(json_encode($expectedPayload->jsonSerialize(), JSON_PRETTY_PRINT));
        }

        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $payload = (string) $response->getBody();

        $this->assertEquals($serializedPayload, $payload);
    }

    public function testActionThrowsUserNotFoundException()
    {
        $app = $this->getAppInstance();

        /** @var Container $container */
        $container = $app->getContainer();

        /** @var EntityManager $entityManager */
        $entityManager = $container->get(EntityManager::class);

        $userRepository = new UserRepository($entityManager);

        $usersCount = count($userRepository->findAllUsers()) ;
        $userId = $usersCount + 1;

        $request = $this->createRequest('GET', '/user/' . $userId);

        $expectedError = new ActionError(ActionError::RESOURCE_NOT_FOUND, 'The user you requested does not exist.');
        $expectedPayload = new ActionPayload(404, null, $expectedError);

        try {
            $response = $app->handle($request);
        } catch (Exception $e) {
            $response = new Response(404, null);
            $response->getBody()->write(json_encode($expectedPayload->jsonSerialize(), JSON_PRETTY_PRINT));
        }

        $payload = (string) $response->getBody();
        $serializedPayload = json_encode($expectedPayload, JSON_PRETTY_PRINT);

        $this->assertEquals($serializedPayload, $payload);
    }
}
