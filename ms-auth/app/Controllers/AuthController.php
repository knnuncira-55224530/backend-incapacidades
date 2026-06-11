<?php

namespace App\Controllers;

use App\Models\Usuario;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function login(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $login = trim($body['user'] ?? '');
        $password = trim($body['password'] ?? '');

        if ($login === '' || $password === '') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Debes enviar usuario/correo y contraseña'
            ], 400);
        }

        $usuario = Usuario::where('usuario', $login)
            ->orWhere('correo', $login)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Usuario no encontrado o inactivo'
            ], 404);
        }

        if ($usuario->contrasena !== $password) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Contraseña incorrecta'
            ], 401);
        }

        $token = bin2hex(random_bytes(32));
        $usuario->token = $token;
        $usuario->sesion_activa = true;
        $usuario->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'usuario' => $usuario->usuario,
                'correo' => $usuario->correo,
                'rol' => $usuario->rol,
                'token' => $token
            ]
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();
        $token = trim($body['token'] ?? '');

        if ($token === '') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Token requerido'
            ], 400);
        }

        $usuario = Usuario::where('token', $token)->first();

        if (!$usuario) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Token inválido'
            ], 404);
        }

        $usuario->token = null;
        $usuario->sesion_activa = false;
        $usuario->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    public function validateSession(Request $request, Response $response): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $token = '';

        if (str_starts_with($authHeader, 'Bearer ')) {
            $token = trim(substr($authHeader, 7));
        }

        if ($token === '') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Token requerido'
            ], 401);
        }

        $usuario = Usuario::where('token', $token)
            ->where('sesion_activa', true)
            ->where('estado', 'activo')
            ->first();

        if (!$usuario) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Sesión no válida'
            ], 401);
        }

        return $this->json($response, [
            'success' => true,
            'message' => 'Sesión válida',
            'data' => [
                'id' => $usuario->id,
                'nombre' => $usuario->nombre,
                'usuario' => $usuario->usuario,
                'rol' => $usuario->rol
            ]
        ]);
    }
}