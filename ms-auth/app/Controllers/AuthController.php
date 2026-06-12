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

    private function body(Request $request): array
    {
        $parsed = $request->getParsedBody();
        return is_array($parsed) ? $parsed : [];
    }

    private function token(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function login(Request $request, Response $response): Response
    {
        $body = $this->body($request);
        $credencial = trim((string)($body['usuario'] ?? $body['correo'] ?? ''));
        $contrasena = (string)($body['contrasena'] ?? $body['password'] ?? '');

        if ($credencial === '' || $contrasena === '') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Usuario/correo y contraseña son obligatorios'
            ], 400);
        }

        $user = Usuario::where('usuario', $credencial)
            ->orWhere('correo', $credencial)
            ->first();

        if (!$user) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        if ($user->estado !== 'activo') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Usuario inactivo'
            ], 403);
        }

        $stored = (string) $user->contrasena;
        $ok = password_get_info($stored)['algo'] ? password_verify($contrasena, $stored) : ($contrasena === $stored);

        if (!$ok) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Credenciales inválidas'
            ], 401);
        }

        $user->token = $this->token();
        $user->sesion_activa = 1;
        $user->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'token' => $user->token,
            'data' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
                'usuario' => $user->usuario,
                'rol' => $user->rol,
                'estado' => $user->estado
            ]
        ]);
    }

    public function logout(Request $request, Response $response): Response
    {
        $auth = $request->getHeaderLine('Authorization');
        $token = str_starts_with($auth, 'Bearer ') ? trim(substr($auth, 7)) : '';

        if ($token === '') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Token requerido'
            ], 401);
        }

        $user = Usuario::where('token', $token)->first();

        if (!$user) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Token inválido'
            ], 401);
        }

        $user->token = null;
        $user->sesion_activa = 0;
        $user->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    public function validate(Request $request, Response $response): Response
    {
        $auth = $request->getHeaderLine('Authorization');
        $token = str_starts_with($auth, 'Bearer ') ? trim(substr($auth, 7)) : '';

        if ($token === '') {
            return $this->json($response, [
                'success' => false,
                'message' => 'Token requerido'
            ], 401);
        }

        $user = Usuario::where('token', $token)->where('sesion_activa', 1)->where('estado', 'activo')->first();

        if (!$user) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Sesión no válida'
            ], 401);
        }

        return $this->json($response, [
            'success' => true,
            'message' => 'Sesión válida',
            'data' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'correo' => $user->correo,
                'usuario' => $user->usuario,
                'rol' => $user->rol
            ]
        ]);
    }
}