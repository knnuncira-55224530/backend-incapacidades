<?php

namespace App\Middleware;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    private function json(ResponseInterface $response, array $data, int $status = 401): ResponseInterface
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getHeaderLine('Authorization');
        $token = str_starts_with($auth, 'Bearer ') ? trim(substr($auth, 7)) : '';

        if ($token === '') {
            $response = new \Slim\Psr7\Response();
            return $this->json($response, [
                'success' => false,
                'message' => 'Token requerido'
            ]);
        }

        $user = Usuario::where('token', $token)
            ->where('session_active', 1)
            ->first();

        if (!$user) {
            $response = new \Slim\Psr7\Response();
            return $this->json($response, [
                'success' => false,
                'message' => 'Sesión no válida'
            ]);
        }

        $request = $request->withAttribute('auth_user', $user);
        return $handler->handle($request);
    }
}