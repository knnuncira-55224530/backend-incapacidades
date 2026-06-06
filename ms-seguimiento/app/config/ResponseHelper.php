<?php
declare(strict_types=1);

namespace App\Config;

use Psr\Http\Message\ResponseInterface;
use Slim\Psr7\Response;

class ResponseHelper
{
    public static function success(array $data, string $message = 'Operacion exitosa', int $status = 200): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public static function error(string $message, int $status = 400, array $errors = []): ResponseInterface
    {
        $response = new Response();
        $payload = ['success' => false, 'message' => $message];
        if (!empty($errors)) $payload['errors'] = $errors;
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }
}