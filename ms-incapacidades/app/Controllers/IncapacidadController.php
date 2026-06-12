<?php

namespace App\Controllers;

use App\Models\Incapacidad;
use Illuminate\Support\Facades\DB;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class IncapacidadController
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

    private function days(string $a, string $b): int
    {
        return date_diff(date_create($a), date_create($b))->days + 1;
    }

    public function index(Request $request, Response $response): Response
    {
        $q = Incapacidad::query();
        $p = $request->getQueryParams();

        if (!empty($p['empleado_id'])) $q->where('empleado_id', $p['empleado_id']);
        if (!empty($p['estado'])) $q->where('estado', $p['estado']);
        if (!empty($p['tipo'])) $q->where('tipo', $p['tipo']);
        if (!empty($p['fecha'])) $q->whereDate('fecha_inicio', '<=', $p['fecha'])->whereDate('fecha_fin', '>=', $p['fecha']);

        return $this->json($response, ['success' => true, 'data' => $q->orderBy('id', 'desc')->get()]);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $item = Incapacidad::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Incapacidad no encontrada'], 404);
        return $this->json($response, ['success' => true, 'data' => $item]);
    }

    public function store(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();
        $body = is_array($body) ? $body : [];

        $required = [
            'empleado_id',
            'fecha_inicio',
            'fecha_fin',
            'tipo',
            'diagnostico_general',
            'entidad_medica'
        ];

        foreach ($required as $campo) {
            if (!isset($body[$campo]) || trim((string)$body[$campo]) === '') {
                return $this->json($response, [
                    'success' => false,
                    'message' => "El campo {$campo} es obligatorio"
                ], 400);
            }
        }

        $permitidos = [
            'enfermedad_general',
            'accidente_laboral',
            'licencia_medica',
            'incapacidad_temporal'
        ];

        if (!in_array($body['tipo'], $permitidos, true)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Tipo de incapacidad inválido'
            ], 400);
        }

        $fechaInicio = $body['fecha_inicio'];
        $fechaFin = $body['fecha_fin'];

        if (strtotime($fechaFin) < strtotime($fechaInicio)) {
            return $this->json($response, [
                'success' => false,
                'message' => 'La fecha fin no puede ser menor a la fecha inicio'
            ], 400);
        }

        $empleadoId = (int) $body['empleado_id'];

        $dias = date_diff(date_create($fechaInicio), date_create($fechaFin))->days + 1;

        $duplicada = Incapacidad::where('empleado_id', $empleadoId)
            ->where('fecha_inicio', $fechaInicio)
            ->where('fecha_fin', $fechaFin)
            ->exists();

        if ($duplicada) {
            return $this->json($response, [
                'success' => false,
                'message' => 'Ya existe una incapacidad para ese rango de fechas'
            ], 409);
        }

        $item = new Incapacidad();
        $item->empleado_id = $empleadoId;
        $item->fecha_inicio = $fechaInicio;
        $item->fecha_fin = $fechaFin;
        $item->tipo = $body['tipo'];
        $item->diagnostico_general = $body['diagnostico_general'];
        $item->entidad_medica = $body['entidad_medica'];
        $item->observaciones = $body['observaciones'] ?? null;
        $item->dias_incapacidad = $dias;
        $item->estado = $body['estado'] ?? 'registrada';
        $item->save();

        return $this->json($response, [
            'success' => true,
            'message' => 'Incapacidad registrada correctamente',
            'data' => $item
        ], 201);
    }

    public function update(Request $request, Response $response, array $args): Response
    {
        $item = Incapacidad::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Incapacidad no encontrada'], 404);

        $body = $this->body($request);

        if (isset($body['fecha_inicio']) && isset($body['fecha_fin'])) {
            if (strtotime($body['fecha_fin']) < strtotime($body['fecha_inicio'])) {
                return $this->json($response, ['success' => false, 'message' => 'La fecha fin no puede ser menor a la fecha inicio'], 400);
            }
            $item->fecha_inicio = $body['fecha_inicio'];
            $item->fecha_fin = $body['fecha_fin'];
            $item->dias_incapacidad = $this->days($body['fecha_inicio'], $body['fecha_fin']);
        }

        if (isset($body['observaciones'])) $item->observaciones = $body['observaciones'];
        if (isset($body['estado'])) $item->estado = $body['estado'];

        $item->save();

        return $this->json($response, ['success' => true, 'message' => 'Incapacidad actualizada correctamente', 'data' => $item]);
    }

    public function finalize(Request $request, Response $response, array $args): Response
    {
        $item = Incapacidad::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Incapacidad no encontrada'], 404);

        $item->estado = 'finalizada';
        $item->save();

        return $this->json($response, ['success' => true, 'message' => 'Incapacidad finalizada correctamente', 'data' => $item]);
    }

    public function destroy(Request $request, Response $response, array $args): Response
    {
        $item = Incapacidad::find($args['id']);
        if (!$item) return $this->json($response, ['success' => false, 'message' => 'Incapacidad no encontrada'], 404);

        $item->delete();

        return $this->json($response, ['success' => true, 'message' => 'Incapacidad eliminada correctamente']);
    }
}