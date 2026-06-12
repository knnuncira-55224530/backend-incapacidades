<?php

namespace App\Controllers;

use App\Models\Empleado;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class EmpleadoController
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

    public function index(Request $request, Response $response): Response
    {
        $q = Empleado::query();
        $p = $request->getQueryParams();

        if (!empty($p['documento'])) $q->where('documento', 'like', '%' . $p['documento'] . '%');
        if (!empty($p['area'])) $q->where('area', 'like', '%' . $p['area'] . '%');
        if (!empty($p['estado'])) $q->where('estado', $p['estado']);

        return $this->json($response, [
            'success' => true,
            'data' => $q->orderBy('id', 'desc')->get()
        ]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $item = Empleado::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Empleado no encontrado'], 404);

        return $this->json($response, ['success' => true, 'data' => $item]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $this->body($request);

        foreach (['nombres','apellidos','documento','correo','telefono','cargo','area','fecha_ingreso'] as $f) {
            if (!isset($body[$f]) || trim((string)$body[$f]) === '') {
                return $this->json($response, ['success' => false, 'message' => "El campo {$f} es obligatorio"], 400);
            }
        }

        if (strtotime($body['fecha_ingreso']) === false) {
            return $this->json($response, ['success' => false, 'message' => 'Fecha de ingreso inválida'], 400);
        }

        if (Empleado::where('documento', $body['documento'])->exists()) {
            return $this->json($response, ['success' => false, 'message' => 'El documento ya existe'], 409);
        }

        if (Empleado::where('correo', $body['correo'])->exists()) {
            return $this->json($response, ['success' => false, 'message' => 'El correo ya existe'], 409);
        }

        $item = Empleado::create([
            'nombres' => trim($body['nombres']),
            'apellidos' => trim($body['apellidos']),
            'documento' => trim($body['documento']),
            'correo' => trim($body['correo']),
            'telefono' => trim($body['telefono']),
            'cargo' => trim($body['cargo']),
            'area' => trim($body['area']),
            'fecha_ingreso' => $body['fecha_ingreso'],
            'estado' => $body['estado'] ?? 'activo'
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Empleado registrado correctamente',
            'data' => $item
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $item = Empleado::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Empleado no encontrado'], 404);

        $body = $this->body($request);

        if (isset($body['documento']) && $body['documento'] !== $item->documento) {
            if (Empleado::where('documento', $body['documento'])->where('id', '!=', $item->id)->exists()) {
                return $this->json($response, ['success' => false, 'message' => 'El documento ya existe'], 409);
            }
            $item->documento = trim($body['documento']);
        }

        if (isset($body['correo']) && $body['correo'] !== $item->correo) {
            if (Empleado::where('correo', $body['correo'])->where('id', '!=', $item->id)->exists()) {
                return $this->json($response, ['success' => false, 'message' => 'El correo ya existe'], 409);
            }
            $item->correo = trim($body['correo']);
        }

        if (isset($body['nombres'])) $item->nombres = trim($body['nombres']);
        if (isset($body['apellidos'])) $item->apellidos = trim($body['apellidos']);
        if (isset($body['telefono'])) $item->telefono = trim($body['telefono']);
        if (isset($body['cargo'])) $item->cargo = trim($body['cargo']);
        if (isset($body['area'])) $item->area = trim($body['area']);
        if (isset($body['fecha_ingreso'])) {
            if (strtotime($body['fecha_ingreso']) === false) {
                return $this->json($response, ['success' => false, 'message' => 'Fecha de ingreso inválida'], 400);
            }
            $item->fecha_ingreso = $body['fecha_ingreso'];
        }
        if (isset($body['estado'])) $item->estado = $body['estado'];

        $item->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Empleado actualizado correctamente',
            'data' => $item
        ]);
    }

    public function changeState(Request $request, Response $response, array $args): Response
    {
        $item = Empleado::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Empleado no encontrado'], 404);

        $body = $this->body($request);
        $estado = $body['estado'] ?? null;

        if (!in_array($estado, ['activo', 'inactivo'], true)) {
            return $this->json($response, ['success' => false, 'message' => 'Estado inválido'], 400);
        }

        $item->estado = $estado;
        $item->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $item
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $item = Empleado::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Empleado no encontrado'], 404);

        $item->delete();

        return $this->json($response, ['success' => true, 'message' => 'Empleado eliminado correctamente']);
    }
}