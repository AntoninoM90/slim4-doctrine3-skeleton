<?php

declare(strict_types=1);

namespace App\Application\Actions;

use App\Application\Exception\JsonEncodingException;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Doctrine\ORM\EntityManager;
use Fig\Http\Message\StatusCodeInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpNotFoundException;

abstract class Action
{
    protected ContainerInterface $container;

    protected LoggerInterface $logger;

    protected EntityManager $entityManager;

    protected Request $request;

    protected Response $response;

    /** @var array<string, string> */
    protected array $args = [];

    /**
     * The constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->logger = $container->get(LoggerInterface::class);
        $this->entityManager = $container->get(EntityManager::class);
    }

    /**
     * @param array<string, string> $args
     *
     * @throws HttpNotFoundException
     * @throws HttpBadRequestException
     */
    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        try {
            return $this->action();
        } catch (DomainRecordNotFoundException $e) {
            throw new HttpNotFoundException($this->request, $e->getMessage());
        }
    }

    /**
     * @throws DomainRecordNotFoundException
     * @throws HttpBadRequestException
     */
    abstract protected function action(): Response;

    /**
     * @return array<array-key, mixed>|object|null
     */
    protected function getFormData()
    {
        return $this->request->getParsedBody();
    }

    /**
     * @return mixed
     * @throws HttpBadRequestException
     */
    protected function resolveArg(string $name)
    {
        if (!isset($this->args[$name])) {
            throw new HttpBadRequestException($this->request, "Could not resolve argument `{$name}`.");
        }

        return $this->args[$name];
    }

    /**
     * @param array<array-key, mixed>|object|null $data
     */
    protected function respondWithData($data = null, int $statusCode = StatusCodeInterface::STATUS_OK): Response
    {
        $payload = new ActionPayload($statusCode, $data);

        return $this->respond($payload);
    }

    protected function respondWithStatus(int $statusCode = StatusCodeInterface::STATUS_OK): Response
    {
        $payload = new ActionPayload($statusCode);

        return $this->respond($payload);
    }

    protected function respondWithError(
        string $type,
        ?string $description = null,
        int $statusCode = StatusCodeInterface::STATUS_BAD_REQUEST
    ): Response {
        $actionError = new ActionError($type, $description);
        $payload = new ActionPayload($statusCode, null, $actionError);

        return $this->respond($payload);
    }

    protected function respond(ActionPayload $payload): Response
    {
        $json = json_encode($payload, JSON_PRETTY_PRINT);

        if ($json === false) {
            throw new JsonEncodingException('Unable to encode the response payload.');
        }

        $this->response->getBody()->write($json);

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($payload->getStatusCode());
    }
}
