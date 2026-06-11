    // GET /incapacidades/estadisticas/resumen
    // Dashboard con conteos por estado y tipo
    public function resumen(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $total = Incapacidad::count();
        
        $porEstado = Incapacidad::selectRaw('estado, COUNT(*) as cantidad')
            ->groupBy('estado')
            ->pluck('cantidad', 'estado');
        
        $porTipo = Incapacidad::selectRaw('tipo, COUNT(*) as cantidad')
            ->groupBy('tipo')
            ->pluck('cantidad', 'tipo');
        
        $diasPromedio = Incapacidad::avg('dias_incapacidad');
        
        $activas = Incapacidad::whereNotIn('estado', ['finalizada', 'rechazada'])->count();
        
        return ResponseHelper::success([
            'total_incapacidades' => $total,
            'activas' => $activas,
            'por_estado' => $porEstado,
            'por_tipo' => $porTipo,
            'dias_promedio' => round((float)$diasPromedio, 1)
        ], 'Resumen de incapacidades');
    }
        // PATCH /incapacidades/{id}/estado
    // Cambiar estado con validación de flujo
    public function cambiarEstado(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = (int) $args['id'];
        $inc = Incapacidad::find($id);
        
        if (!$inc) {
            return ResponseHelper::error('Incapacidad no encontrada', 404);
        }
        
        $data = $request->getParsedBody();
        $nuevoEstado = $data['estado'] ?? null;
        
        $estadosPermitidos = ['registrada', 'en_revision', 'aprobada', 'rechazada', 'finalizada'];
        if (!in_array($nuevoEstado, $estadosPermitidos)) {
            return ResponseHelper::error('Estado no valido', 422);
        }
        
        // Flujo: no se puede volver atras
        $flujo = [
            'registrada' => ['en_revision', 'rechazada'],
            'en_revision' => ['aprobada', 'rechazada'],
            'aprobada' => ['finalizada'],
            'rechazada' => [],
            'finalizada' => []
        ];
        
        $actual = $inc->estado;
        if (!in_array($nuevoEstado, $flujo[$actual])) {
            return ResponseHelper::error(
                "No se puede cambiar de '{$actual}' a '{$nuevoEstado}'", 
                422
            );
        }
        
        $inc->estado = $nuevoEstado;
        $inc->save();
        
        return ResponseHelper::success([
            'incapacidad' => $inc
        ], "Estado actualizado a: {$nuevoEstado}");
    }