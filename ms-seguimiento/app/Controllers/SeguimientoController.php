<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\ResponseHelper;
use App\Models\Seguimiento;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class SeguimientoController
{
    // GET /seguimientos - Listar todo (con filtro por incapacidad)
    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $params = $request->getQueryParams();
        $query = Seguimiento::query();
        
        // Filtro por incapacidad_id
        if (!empty($params['incapacidad_id'])) {
            $query->where('incapacidad_id', (int) $params['incapacidad_id']);
        }
        
        // Filtro por usuario
        if (!empty($params['usuario'])) {
            $query->where('usuario_responsable', 'like', '%' . $params['usuario'] . '%');
        }
        
        // Ordenar por fecha descendente (más reciente primero)
        $seguimientos = $query->orderBy('fecha', 'desc')
                              ->orderBy('created_at', 'desc')
                              ->get();
        
        return ResponseHelper::success([
            'seguimientos' => $seguimientos,
            'total' => $seguimientos->count()
        ], 'Historial obtenido');
    }

    // GET /seguimientos/{id}
    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $seg = Seguimiento::find($id);
        
        if (!$seg) {
            return ResponseHelper::error('Seguimiento no encontrado', 404);
        }
        
        return ResponseHelper::success(['seguimiento' => $seg], 'Seguimiento encontrado');
    }

    // POST /seguimientos - Crear seguimiento
    public function store(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $request->getParsedBody();
        
        // Validar campos obligatorios
        $errores = [];
        $obligatorios = ['incapacidad_id', 'fecha', 'comentario', 'estado', 'usuario_responsable'];
        foreach ($obligatorios as $campo) {
            if (empty($data[$campo]) || trim((string)$data[$campo]) === '') {
                $errores[$campo] = 'Campo obligatorio';
            }
        }
        
        if (!empty($errores)) {
            return ResponseHelper::error('Validacion fallida', 422, $errores);
        }
        
        // Validar fecha
        if (!$this->fechaValida($data['fecha'])) {
            return ResponseHelper::error('Fecha invalida (formato YYYY-MM-DD)', 422);
        }
        
        // Validar estado permitido
        $estadosPermitidos = ['registrada', 'en_revision', 'aprobada', 'rechazada', 'finalizada'];
        if (!in_array($data['estado'], $estadosPermitidos)) {
            return ResponseHelper::error('Estado no valido. Use: ' . implode(', ', $estadosPermitidos), 422);
        }
        
        $seguimiento = Seguimiento::create([
            'incapacidad_id' => (int) $data['incapacidad_id'],
            'fecha' => $data['fecha'],
            'comentario' => trim($data['comentario']),
            'estado' => $data['estado'],
            'usuario_responsable' => trim($data['usuario_responsable'])
        ]);
        
        return ResponseHelper::success([
            'seguimiento' => $seguimiento
        ], 'Seguimiento registrado', 201);
    }

    // GET /seguimientos/incapacidad/{incapacidad_id} - Historial completo de una incapacidad
    public function historialIncapacidad(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $incapacidadId = (int) $args['incapacidad_id'];
        
        $seguimientos = Seguimiento::where('incapacidad_id', $incapacidadId)
                                   ->orderBy('fecha', 'asc')
                                   ->orderBy('created_at', 'asc')
                                   ->get();
        
        return ResponseHelper::success([
            'incapacidad_id' => $incapacidadId,
            'seguimientos' => $seguimientos,
            'total' => $seguimientos->count()
        ], 'Historial de incapacidad obtenido');
    }

    private function fechaValida(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('Y-m-d', $fecha);
        return $d && $d->format('Y-m-d') === $fecha;
    }
}