<?php

declare(strict_types=1);

namespace App\Application\ResponseEmitter;

use Psr\Http\Message\ResponseInterface;
use Slim\ResponseEmitter as SlimResponseEmitter;

class ResponseEmitter extends SlimResponseEmitter
{
    /**
     * {@inheritdoc}
     */
    public function emit(ResponseInterface $response): void
    {
        // Cross-origin handling is owned by the CorsMiddleware, which applies
        // an allow-list of origins (see cors.allowed_origins in
        // app/settings.php). The response must not be modified here.

        if (ob_get_contents()) {
            ob_clean();
        }

        parent::emit($response);
    }
}
