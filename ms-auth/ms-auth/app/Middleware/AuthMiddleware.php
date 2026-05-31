<?php
namespace App\Middleware;

use App\Models\Usuario;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler)
    {
        $headers = $request->getHeaders();
        $token = $headers['Authorization'][0] ?? '';
        $token = str_replace('Bearer ', '', $token);

        $user = Usuario::where('token', $token)
               ->where('sesion_activa', true)
               ->where('estado', 'activo')
               ->first();

        if (!$user) {
            $response = new Response();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'No autorizado'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Añadir usuario a la request para usarlo en controllers
        $request = $request->withAttribute('usuario', $user);
        return $handler->handle($request);
    }
}