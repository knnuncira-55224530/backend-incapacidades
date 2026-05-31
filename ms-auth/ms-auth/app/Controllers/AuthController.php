<?php
namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    // POST /login
    public function login(Request $request, Response $response): Response
    {
        $data = $request->getParsedBody();
        $usuario = $data['usuario'] ?? '';
        $contrasena = $data['contrasena'] ?? '';

        // Buscar por usuario o correo
        $user = Usuario::where('usuario', $usuario)
               ->orWhere('correo', $usuario)
               ->where('estado', 'activo')
               ->first();

        // Validar contraseña (sin hash por ahora, como puso el profe en la BD)
        if (!$user || $contrasena !== $user->contrasena) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Credenciales incorrectas'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        // Generar token simple
        $token = bin2hex(random_bytes(32));
        $user->token = $token;
        $user->sesion_activa = true;
        $user->save();

        $response->getBody()->write(json_encode([
            'success' => true,
            'token' => $token,
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'correo' => $user->correo,
                'rol' => $user->rol
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // POST /logout
    public function logout(Request $request, Response $response): Response
    {
        $headers = $request->getHeaders();
        $token = $headers['Authorization'][0] ?? '';
        $token = str_replace('Bearer ', '', $token);

        $user = Usuario::where('token', $token)->first();
        if ($user) {
            $user->token = null;
            $user->sesion_activa = false;
            $user->save();
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Sesión cerrada'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }

    // GET /validar
    public function validar(Request $request, Response $response): Response
    {
        $headers = $request->getHeaders();
        $token = $headers['Authorization'][0] ?? '';
        $token = str_replace('Bearer ', '', $token);

        $user = Usuario::where('token', $token)
               ->where('sesion_activa', true)
               ->where('estado', 'activo')
               ->first();

        if (!$user) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Sesión inválida'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $response->getBody()->write(json_encode([
            'success' => true,
            'usuario' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'usuario' => $user->usuario,
                'rol' => $user->rol
            ]
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}