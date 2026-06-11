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

    public function index(Request $request, Response $response): Response
    {
        $query = Empleado::query();

        $params = $request->getQueryParams();

        if (!empty($params['documento'])) {
            $query->where('documento', $params['documento']);
        }

        if (!empty($params['area'])) {
            $query->where('area', $params['area']);
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        $empleados = $query->orderBy('id', 'desc')->get();

        return $this->json($response, [
            'success' => true,
            'data' => $empleados
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $required = ['nombres', 'apellidos', 'documento', 'correo', 'telefono', 'cargo', 'area', 'fecha_ingreso'];
        foreach ($required as $campo) {
            if (empty($body[$campo])) {
                return $this->json($response, [
                    'success' => false,
                    'message' => "El campo {$campo} es obligatorio"
                ], 400);
            }
        }

        if (Empleado::where('documento', $body['documento'])->exists()) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El documento ya existe'
            ], 409);
        }

        if (Empleado::where('correo', $body['correo'])->exists()) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El correo ya existe'
            ], 409);
        }

        $empleado = Empleado::create([
            'nombres' => $body['nombres'],
            'apellidos' => $body['apellidos'],
            'documento' => $body['documento'],
            'correo' => $body['correo'],
            'telefono' => $body['telefono'],
            'cargo' => $body['cargo'],
            'area' => $body['area'],
            'fecha_ingreso' => $body['fecha_ingreso'],
            'estado' => $body['estado'] ?? 'activo'
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Empleado creado correctamente',
            'data' => $empleado
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $empleado = Empleado::find($args['id']);

        if (!$empleado) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        $body = (array) $request->getParsedBody();

        if (!empty($body['documento']) && Empleado::where('documento', $body['documento'])->where('id', '!=', $empleado->id)->exists()) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El documento ya existe'
            ], 409);
        }

        if (!empty($body['correo']) && Empleado::where('correo', $body['correo'])->where('id', '!=', $empleado->id)->exists()) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El correo ya existe'
            ], 409);
        }

        $empleado->update($body);

        return $this->json($response, [
            'success' => true,
            'message' => 'Empleado actualizado correctamente',
            'data' => $empleado
        ]);
    }

    public function changeStatus(Request $request, Response $response, array $args): Response
    {
        $empleado = Empleado::find($args['id']);

        if (!$empleado) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Empleado no encontrado'
            ], 404);
        }

        $body = (array) $request->getParsedBody();
        $estado = $body['estado'] ?? '';

        if (!in_array($estado, ['activo', 'inactivo'])) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Estado inválido'
            ], 400);
        }

        $empleado->estado = $estado;
        $empleado->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $empleado
        ]);
    }
}