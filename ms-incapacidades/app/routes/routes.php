    // Verificar incapacidades activas de un empleado
    $app->get('/incapacidades/empleado/{empleado_id}/activas', [$controller, 'activasPorEmpleado']);