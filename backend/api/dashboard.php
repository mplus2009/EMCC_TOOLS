<?php
/**
 * API del Dashboard
 * Backend - Sistema Escolar EMCC Digital
 */

require_once '../config/database.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    case 'obtener_datos':
        obtenerDatosDashboard();
        break;
    case 'buscar_estudiante':
        buscarEstudiante();
        break;
    case 'obtener_actividades':
        obtenerActividades();
        break;
    case 'obtener_estadisticas':
        obtenerEstadisticas();
        break;
    case 'marcar_tutorial_visto':
        marcarTutorialVisto();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

/**
 * Obtener datos del dashboard según el rol
 */
function obtenerDatosDashboard() {
    $usuario = verificarSesion();
    $pdo = getDBConnection();
    
    $datos = [
        'usuario' => $usuario,
        'mostrar_tutorial' => false,
        'nuevas_actividades' => 0,
        'alarma_activa' => false
    ];
    
    // Verificar si debe mostrar tutorial
    $stmt = $pdo->prepare("SELECT tutorial_visto FROM usuarios WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $user_data = $stmt->fetch();
    $datos['mostrar_tutorial'] = !$user_data['tutorial_visto'];
    
    if ($usuario['rol'] === 'estudiante') {
        // Obtener estadísticas del estudiante
        $datos = array_merge($datos, obtenerEstadisticasEstudiante($usuario['id'], $pdo));
        
        // Obtener actividades de la semana
        $datos['actividades_semana'] = obtenerActividadesSemana($usuario['id'], $pdo);
        
        // Verificar alarma
        $stmt = $pdo->prepare("SELECT COUNT(*) as demeritos FROM actividades 
                              WHERE destinatario_id = ? AND tipo = 'demerito' 
                              AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
        $stmt->execute([$usuario['id']]);
        $demeritos = $stmt->fetch()['demeritos'];
        $datos['alarma_activa'] = $demeritos >= 3; // Configurable
        
    } elseif (in_array($usuario['rol'], ['profesor', 'oficial', 'directiva'])) {
        // Contar nuevas actividades para notificaciones
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM actividades 
                              WHERE destinatario_id = ? AND leido = 0");
        $stmt->execute([$usuario['id']]);
        $datos['nuevas_actividades'] = $stmt->fetch()['count'];
    }
    
    jsonResponse(true, $datos);
}

/**
 * Obtener estadísticas del estudiante
 */
function obtenerEstadisticasEstudiante($usuario_id, $pdo) {
    // Méritos de la semana
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) as total FROM actividades 
                          WHERE destinatario_id = ? AND tipo = 'merito' 
                          AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $stmt->execute([$usuario_id]);
    $meritos_semana = $stmt->fetch()['total'];
    
    // Deméritos de la semana
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) as total FROM actividades 
                          WHERE destinatario_id = ? AND tipo = 'demerito' 
                          AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $stmt->execute([$usuario_id]);
    $demeritos_semana = $stmt->fetch()['total'];
    
    // Balance semanal
    $balance_semana = $meritos_semana - $demeritos_semana;
    
    // Totales históricos
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) as total FROM actividades 
                          WHERE destinatario_id = ? AND tipo = 'merito'");
    $stmt->execute([$usuario_id]);
    $meritos_total = $stmt->fetch()['total'];
    
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(cantidad), 0) as total FROM actividades 
                          WHERE destinatario_id = ? AND tipo = 'demerito'");
    $stmt->execute([$usuario_id]);
    $demeritos_total = $stmt->fetch()['total'];
    
    return [
        'meritos_semana' => (int)$meritos_semana,
        'demeritos_semana' => (int)$demeritos_semana,
        'balance_semana' => (int)$balance_semana,
        'meritos_total' => (int)$meritos_total,
        'demeritos_total' => (int)$demeritos_total,
        'balance_total' => (int)($meritos_total - $demeritos_total)
    ];
}

/**
 * Buscar estudiantes
 */
function buscarEstudiante() {
    $usuario = verificarSesion();
    
    // Solo ciertos roles pueden buscar
    if (!in_array($usuario['rol'], ['profesor', 'oficial', 'directiva'])) {
        jsonResponse(false, null, 'No tiene permisos para buscar estudiantes', 403);
    }
    
    $termino = $_GET['q'] ?? '';
    
    if (strlen($termino) < 2) {
        jsonResponse(false, null, 'Término de búsqueda muy corto', 400);
    }
    
    $pdo = getDBConnection();
    $busqueda = "%{$termino}%";
    
    $stmt = $pdo->prepare("SELECT e.id, u.nombre, u.apellidos, u.CI, e.grado, e.peloton,
                          (SELECT COALESCE(SUM(a.cantidad), 0) FROM actividades a 
                           WHERE a.destinatario_id = e.id AND a.tipo = 'merito') as meritos,
                          (SELECT COALESCE(SUM(a.cantidad), 0) FROM actividades a 
                           WHERE a.destinatario_id = e.id AND a.tipo = 'demerito') as demeritos
                          FROM estudiante e
                          INNER JOIN usuarios u ON e.id = u.id
                          WHERE (u.nombre LIKE ? OR u.apellidos LIKE ? OR u.CI LIKE ? OR e.grado LIKE ?)
                          AND u.estado = 'activo'
                          LIMIT 20");
    $stmt->execute([$busqueda, $busqueda, $busqueda, $busqueda]);
    $resultados = $stmt->fetchAll();
    
    // Calcular balance para cada resultado
    foreach ($resultados as &$est) {
        $est['balance'] = (int)$est['meritos'] - (int)$est['demeritos'];
        $est['nombre_completo'] = trim($est['nombre'] . ' ' . $est['apellidos']);
    }
    
    jsonResponse(true, $resultados);
}

/**
 * Obtener actividades del usuario
 */
function obtenerActividades() {
    $usuario = verificarSesion();
    $pdo = getDBConnection();
    
    $filtro = $_GET['filtro'] ?? 'semana'; // semana, mes, todas
    $tipo = $_GET['tipo'] ?? 'todas'; // todas, merito, demerito
    
    $where = "WHERE a.destinatario_id = ?";
    $params = [$usuario['id']];
    
    if ($filtro === 'semana') {
        $where .= " AND YEARWEEK(a.fecha, 1) = YEARWEEK(CURDATE(), 1)";
    } elseif ($filtro === 'mes') {
        $where .= " AND MONTH(a.fecha) = MONTH(CURDATE()) AND YEAR(a.fecha) = YEAR(CURDATE())";
    }
    
    if ($tipo !== 'todas') {
        $where .= " AND a.tipo = ?";
        $params[] = $tipo;
    }
    
    $stmt = $pdo->prepare("SELECT a.*, u.nombre as notificador_nombre, u.apellidos as notificador_apellidos,
                          (SELECT alegacion FROM alegaciones WHERE actividad_id = a.id LIMIT 1) as alegacion
                          FROM actividades a
                          LEFT JOIN usuarios u ON a.notificador_id = u.id
                          {$where}
                          ORDER BY a.fecha DESC, a.hora DESC
                          LIMIT 50");
    $stmt->execute($params);
    $actividades = $stmt->fetchAll();
    
    // Formatear respuesta
    foreach ($actividades as &$act) {
        $act['notificador'] = trim($act['notificador_nombre'] . ' ' . $act['notificador_apellidos']);
        $act['es_merito'] = $act['tipo'] === 'merito';
    }
    
    jsonResponse(true, $actividades);
}

/**
 * Obtener actividades de la semana agrupadas
 */
function obtenerActividadesSemana($usuario_id, $pdo) {
    $stmt = $pdo->prepare("SELECT a.*, u.nombre as notificador_nombre, u.apellidos as notificador_apellidos,
                          (SELECT alegacion FROM alegaciones WHERE actividad_id = a.id LIMIT 1) as alegacion
                          FROM actividades a
                          LEFT JOIN usuarios u ON a.notificador_id = u.id
                          WHERE a.destinatario_id = ? AND YEARWEEK(a.fecha, 1) = YEARWEEK(CURDATE(), 1)
                          ORDER BY a.fecha DESC, a.hora DESC");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll();
}

/**
 * Obtener estadísticas generales (para admin)
 */
function obtenerEstadisticas() {
    $usuario = verificarSesion();
    
    if (!in_array($usuario['rol'], ['directiva', 'oficial'])) {
        jsonResponse(false, null, 'No tiene permisos para ver estadísticas', 403);
    }
    
    $pdo = getDBConnection();
    
    $stats = [];
    
    // Total de estudiantes
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM estudiante");
    $stats['total_estudiantes'] = $stmt->fetch()['total'];
    
    // Total de profesores
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE rol = 'profesor'");
    $stats['total_profesores'] = $stmt->fetch()['total'];
    
    // Méritos esta semana
    $stmt = $pdo->query("SELECT COALESCE(SUM(cantidad), 0) as total FROM actividades 
                        WHERE tipo = 'merito' AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $stats['meritos_semana'] = $stmt->fetch()['total'];
    
    // Deméritos esta semana
    $stmt = $pdo->query("SELECT COALESCE(SUM(cantidad), 0) as total FROM actividades 
                        WHERE tipo = 'demerito' AND YEARWEEK(fecha, 1) = YEARWEEK(CURDATE(), 1)");
    $stats['demeritos_semana'] = $stmt->fetch()['total'];
    
    jsonResponse(true, $stats);
}

/**
 * Marcar tutorial como visto
 */
function marcarTutorialVisto() {
    $usuario = verificarSesion();
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("UPDATE usuarios SET tutorial_visto = 1 WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    
    jsonResponse(true, null, 'Tutorial marcado como visto');
}
?>
