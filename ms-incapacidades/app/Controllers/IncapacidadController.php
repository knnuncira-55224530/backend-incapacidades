<?php

namespace App\Controllers;

use App\Models\Incapacidad;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IncapacidadController
{
    private function json(Response $response, array $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
    }

    public function index(Request $request, Response $response): Response
    {
        $query = Incapacidad::query();
        $params = $request->getQueryParams();

        if (!empty($params['empleado_id'])) {
            $query->where('empleado_id', $params['empleado_id']);
        }

        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }

        if (!empty($params['tipo'])) {
            $query->where('tipo', $params['tipo']);
        }

        if (!empty($params['fecha'])) {
            $query->whereDate('fecha_inicio', '<=', $params['fecha'])
                  ->whereDate('fecha_fin', '>=', $params['fecha']);
        }

        $incapacidades = $query->orderBy('id', 'desc')->get();

        return $this->json($response, [
            'success' => true,
            'data' => $incapacidades
        ]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = (array) $request->getParsedBody();

        $required = [
            'empleado_id',
            'fecha_inicio',
            'fecha_fin',
            'tipo',
            'diagnostico_general',
            'entidad_medica'
        ];

        foreach ($required as $campo) {
            if (empty($body[$campo])) {
                return $this->json($response, [
                    'success' => false,
                    'message' => "El campo {$campo} es obligatorio"
                ], 400);
            }
        }

        if (strtotime($body['fecha_fin']) < strtotime($body['fecha_inicio'])) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La fecha fin no puede ser menor a la fecha inicio'
            ], 400);
        }

        $duplicada = Incapacidad::where('empleado_id', $body['empleado_id'])
            ->where('fecha_inicio', $body['fecha_inicio'])
            ->where('fecha_fin', $body['fecha_fin'])
            ->exists();

        if ($duplicada) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe una incapacidad para ese rango de fechas'
            ], 409);
        }

        $dias = date_diff(
            date_create($body['fecha_inicio']),
            date_create($body['fecha_fin'])
        )->days + 1;

        $incapacidad = Incapacidad::create([
            'empleado_id' => $body['empleado_id'],
            'fecha_inicio' => $body['fecha_inicio'],
            'fecha_fin' => $body['fecha_fin'],
            'tipo' => $body['tipo'],
            'diagnostico_general' => $body['diagnostico_general'],
            'entidad_medica' => $body['entidad_medica'],
            'observaciones' => $body['observaciones'] ?? null,
            'dias_incapacidad' => $dias,
            'estado' => $body['estado'] ?? 'registrada'
        ]);

        return $this->json($response, [
            'success' => true,
            'message' => 'Incapacidad registrada correctamente',
            'data' => $incapacidad
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $incapacidad = Incapacidad::find($args['id']);

        if (!$incapacidad) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Incapacidad no encontrada'
            ], 404);
        }

        $body = (array) $request->getParsedBody();

        if (!empty($body['fecha_inicio']) && !empty($body['fecha_fin'])) {
            if (strtotime($body['fecha_fin']) < strtotime($body['fecha_inicio'])) {
                return $this->json($response, [
                    'success' => false,
                    'message' => 'La fecha fin no puede ser menor a la fecha inicio'
                ], 400);
            }

            $body['dias_incapacidad'] = date_diff(
                date_create($body['fecha_inicio']),
                date_create($body['fecha_fin'])
            )->days + 1;
        }

        $incapacidad->update($body);

        return $this->json($response, [
            'success' => true,
            'message' => 'Incapacidad actualizada correctamente',
            'data' => $incapacidad
        ]);
    }

    public function finalize(Request $request, Response $response, array $args): Response
    {
        $incapacidad = Incapacidad::find($args['id']);

        if (!$incapacidad) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Incapacidad no encontrada'
            ], 404);
        }

        $incapacidad->estado = 'finalizada';
        $incapacidad->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Incapacidad finalizada correctamente',
            'data' => $incapacidad
        ]);
    }
}