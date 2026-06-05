<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\ResponseHelper;
use App\Models\Incapacidad;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IncapacidadController
{
    // GET /incapacidades - Listar con filtros
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $query = Incapacidad::query();
        
        if (!empty($params['empleado_id'])) {
            $query->where('empleado_id', $params['empleado_id']);
        }
        if (!empty($params['tipo'])) {
            $query->where('tipo', $params['tipo']);
        }
        if (!empty($params['estado'])) {
            $query->where('estado', $params['estado']);
        }
        if (!empty($params['fecha_inicio']) && !empty($params['fecha_fin'])) {
            $query->whereBetween('fecha_inicio', [$params['fecha_inicio'], $params['fecha_fin']]);
        }
        
        $incapacidades = $query->orderBy('created_at', 'desc')->get();
        
        return ResponseHelper::success([
            'incapacidades' => $incapacidades,
            'total' => $incapacidades->count()
        ], 'Incapacidades obtenidas');
    }

    // GET /incapacidades/{id}
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $inc = Incapacidad::find($id);
        
        if (!$inc) {
            return ResponseHelper::error('Incapacidad no encontrada', 404);
        }
        
        return ResponseHelper::success(['incapacidad' => $inc], 'Incapacidad encontrada');
    }

    // POST /incapacidades - Crear
    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        // Validar campos obligatorios
        $errores = [];
        $obligatorios = ['empleado_id', 'fecha_inicio', 'fecha_fin', 'tipo', 'diagnostico_general', 'entidad_medica'];
        foreach ($obligatorios as $campo) {
            if (empty($data[$campo])) $errores[$campo] = "Campo obligatorio";
        }
        if (!empty($errores)) {
            return ResponseHelper::error('Validacion fallida', 422, $errores);
        }
        
        // Validar fechas
        if (!$this->fechaValida($data['fecha_inicio']) || !$this->fechaValida($data['fecha_fin'])) {
            return ResponseHelper::error('Fechas invalidas (formato YYYY-MM-DD)', 422);
        }
        
        $inicio = new \DateTime($data['fecha_inicio']);
        $fin = new \DateTime($data['fecha_fin']);
        
        if ($fin < $inicio) {
            return ResponseHelper::error('Fecha fin no puede ser menor a fecha inicio', 422);
        }
        
        // Calcular dias automaticamente
        $dias = (int) $inicio->diff($fin)->days + 1;
        
        // Validar no duplicado en mismo rango (mismo empleado, fechas que se solapan)
        $existe = Incapacidad::where('empleado_id', $data['empleado_id'])
            ->where(function($q) use ($data) {
                $q->whereBetween('fecha_inicio', [$data['fecha_inicio'], $data['fecha_fin']])
                  ->orWhereBetween('fecha_fin', [$data['fecha_inicio'], $data['fecha_fin']])
                  ->orWhere(function($q2) use ($data) {
                      $q2->where('fecha_inicio', '<=', $data['fecha_inicio'])
                         ->where('fecha_fin', '>=', $data['fecha_fin']);
                  });
            })
            ->whereNotIn('estado', ['rechazada', 'finalizada'])
            ->exists();
        
        if ($existe) {
            return ResponseHelper::error('Ya existe una incapacidad activa para este empleado en ese rango de fechas', 409);
        }
        
        $incapacidad = Incapacidad::create([
            'empleado_id' => (int) $data['empleado_id'],
            'fecha_inicio' => $data['fecha_inicio'],
            'fecha_fin' => $data['fecha_fin'],
            'tipo' => $data['tipo'],
            'diagnostico_general' => trim($data['diagnostico_general']),
            'entidad_medica' => trim($data['entidad_medica']),
            'observaciones' => !empty($data['observaciones']) ? trim($data['observaciones']) : null,
            'dias_incapacidad' => $dias,
            'estado' => 'registrada'
        ]);
        
        return ResponseHelper::success([
            'incapacidad' => $incapacidad
        ], 'Incapacidad registrada', 201);
    }

    // PUT /incapacidades/{id} - Editar
    public function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $inc = Incapacidad::find($id);
        
        if (!$inc) {
            return ResponseHelper::error('Incapacidad no encontrada', 404);
        }
        
        $data = $request->getParsedBody();
        
        // Si cambian fechas, recalcular dias
        if (!empty($data['fecha_inicio']) || !empty($data['fecha_fin'])) {
            $fechaInicio = $data['fecha_inicio'] ?? $inc->fecha_inicio;
            $fechaFin = $data['fecha_fin'] ?? $inc->fecha_fin;
            
            if (!$this->fechaValida($fechaInicio) || !$this->fechaValida($fechaFin)) {
                return ResponseHelper::error('Fechas invalidas', 422);
            }
            
            $inicio = new \DateTime($fechaInicio);
            $fin = new \DateTime($fechaFin);
            
            if ($fin < $inicio) {
                return ResponseHelper::error('Fecha fin no puede ser menor a fecha inicio', 422);
            }
            
            $inc->dias_incapacidad = (int) $inicio->diff($fin)->days + 1;
            $inc->fecha_inicio = $fechaInicio;
            $inc->fecha_fin = $fechaFin;
        }
        
        // Campos editables
        $editables = ['tipo', 'diagnostico_general', 'entidad_medica', 'observaciones', 'estado'];
        foreach ($editables as $campo) {
            if (isset($data[$campo])) {
                $inc->$campo = is_string($data[$campo]) ? trim($data[$campo]) : $data[$campo];
            }
        }
        
        $inc->save();
        
        return ResponseHelper::success([
            'incapacidad' => $inc->fresh()
        ], 'Incapacidad actualizada');
    }

    // PATCH /incapacidades/{id}/finalizar
    public function finalizar(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $inc = Incapacidad::find($id);
        
        if (!$inc) {
            return ResponseHelper::error('Incapacidad no encontrada', 404);
        }
        
        $inc->estado = 'finalizada';
        $inc->save();
        
        return ResponseHelper::success([
            'incapacidad' => $inc
        ], 'Incapacidad finalizada');
    }

    private function fechaValida(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}