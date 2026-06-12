<?php

namespace App\Controllers;

use App\Models\Seguimiento;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class SeguimientoController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function index(Request $request, Response $response): Response
    {
        $query = Seguimiento::query();
        $params = $request->getQueryParams();

        if (!empty($params['incapacidad_id'])) {
            $query->where('incapacidad_id', $params['incapacidad_id']);
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        if (!empty($params['fecha'])) {
            $query->where('fecha', $params['fecha']);
        }

        $seguimientos = $query->orderBy('id', 'desc')->get();

        return $this->json($response, [
            'success' => true,
            'data' => $seguimientos
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        foreach (['incapacidad_id', 'fecha', 'comentario', 'estado', 'usuario_responsable'] as $campo) {
            if (!isset($body[$campo]) || trim((string) $body[$campo]) === '') {
                return $this->json($response, [
                    'success' => false,
                    'message' => "El campo {$campo} es obligatorio"
                ], 400);
            }
        }

        $seguimiento = new Seguimiento();
        $seguimiento->incapacidad_id = $body['incapacidad_id'];
        $seguimiento->fecha = $body['fecha'];
        $seguimiento->comentario = $body['comentario'];
        $seguimiento->estado = $body['estado'];
        $seguimiento->usuario_responsable = $body['usuario_responsable'];
        $seguimiento->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Seguimiento guardado correctamente',
            'data' => $seguimiento
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $seguimiento = Seguimiento::find($args['id']);

        if (!$seguimiento) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Seguimiento no encontrado'
            ], 404);
        }

        $body = (array) $request->getParsedBody();

        if (isset($body['incapacidad_id'])) $seguimiento->incapacidad_id = $body['incapacidad_id'];
        if (isset($body['fecha'])) $seguimiento->fecha = $body['fecha'];
        if (isset($body['comentario'])) $seguimiento->comentario = $body['comentario'];
        if (isset($body['estado'])) $seguimiento->estado = $body['estado'];
        if (isset($body['usuario_responsable'])) $seguimiento->usuario_responsable = $body['usuario_responsable'];

        $seguimiento->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Seguimiento actualizado correctamente',
            'data' => $seguimiento
        ]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $seguimiento = Seguimiento::find($args['id']);

        if (!$seguimiento) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Seguimiento no encontrado'
            ], 404);
        }

        $seguimiento->delete();

        return $this->json($response, [
            'success' => true,
            'message' => 'Seguimiento eliminado correctamente'
        ]);
    }
}