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

        $seguimientos = $query->orderBy('id', 'desc')->get();

        return $this->json($response, [
            'success' => true,
            'data' => $seguimientos
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $required = [
            'incapacidad_id',
            'fecha',
            'comentario',
            'estado',
            'usuario_responsable'
        ];

        foreach ($required as $campo) {
            if (empty($body[$campo])) {
                return $this->json($response, [
                    'success' => false,
                    'message' => "El campo {$campo} es obligatorio"
                ], 400);
            }
        }

        $seguimiento = Seguimiento::create([
            'incapacidad_id' => $body['incapacidad_id'],
            'fecha' => $body['fecha'],
            'comentario' => $body['comentario'],
            'estado' => $body['estado'],
            'usuario_responsable' => $body['usuario_responsable']
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Seguimiento registrado correctamente',
            'data' => $seguimiento
        ], 201);
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $seguimiento = Seguimiento::find($args['id']);

        if (!$seguimiento) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Seguimiento no encontrado'
            ], 404);
        }

        $body = (array) $request->getParsedBody();
        $estado = $body['estado'] ?? '';

        if (empty($estado)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'El campo estado es obligatorio'
            ], 400);
        }

        $seguimiento->estado = $estado;
        $seguimiento->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Estado actualizado correctamente',
            'data' => $seguimiento
        ]);
    }
}