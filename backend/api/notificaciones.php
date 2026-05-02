<?php
/**
 * API de Notificaciones
 * Backend - Sistema Escolar EMCC Digital
 */

require_once '../config/database.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    case 'obtener_catalogos':
        obtenerCatalogos();
        break;
    case 'crear_notificacion':
        crearNotificacion();
        break;
    case 'obtener_mis_notificaciones':
        obtenerMisNotificaciones();
        break;
    case 'marcar_leido':
        marcarLeido();
        break;
    case 'guardar_alegacion':
        guardarAlegacion();
        break;
    case 'buscar_actividad':
        buscarActividad();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

/**
 * Obtener catálogos de méritos y deméritos
 */
function obtenerCatalogos() {
    $pdo = getDBConnection();
    
    $catalogo_meritos = [];
    $catalogo_demeritos = [];
    
    // Obtener méritos
    $stmt = $pdo->query("SELECT * FROM meritos ORDER BY categoria, causa");
    if ($stmt) {
        $catalogo_meritos = $stmt->fetchAll();
    }
    
    // Obtener deméritos
    $stmt = $pdo->query("SELECT * FROM demeritos ORDER BY categoria, falta");
    if ($stmt) {
        $catalogo_demeritos = $stmt->fetchAll();
    }
    
    jsonResponse(true, [
        'meritos' => $catalogo_meritos,
        'demeritos' => $catalogo_demeritos
    ]);
}

/**
 * Crear notificación de actividad
 */
function crearNotificacion() {
    $usuario = verificarSesion();
    
    // Verificar permisos
    if (!in_array($usuario['rol'], ['profesor', 'oficial', 'directiva'])) {
        jsonResponse(false, null, 'No tiene permisos para crear notificaciones', 403);
    }
    
    $destinatarios = json_decode($_POST['destinatarios'] ?? '[]', true);
    $actividades = json_decode($_POST['actividades'] ?? '[]', true);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? date('H:i');
    $observaciones = $_POST['observaciones'] ?? '';
    
    if (empty($destinatarios) || empty($actividades)) {
        jsonResponse(false, null, 'Debe seleccionar destinatarios y actividades', 400);
    }
    
    $pdo = getDBConnection();
    $creadas = 0;
    
    try {
        $pdo->beginTransaction();
        
        foreach ($destinatarios as $dest) {
            $destinatario_id = $dest['id'];
            
            foreach ($actividades as $act) {
                $tipo = $act['tipo'];
                $categoria = $act['categoria'];
                $descripcion = $act['descripcion'];
                $cantidad = (int)$act['cantidad'];
                
                $stmt = $pdo->prepare("INSERT INTO actividades 
                    (destinatario_id, notificador_id, tipo, categoria, falta_causa, cantidad, fecha, hora, observaciones) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $stmt->execute([
                    $destinatario_id,
                    $usuario['id'],
                    $tipo,
                    $categoria,
                    $descripcion,
                    $cantidad,
                    $fecha,
                    $hora,
                    $observaciones
                ]);
                
                $creadas++;
            }
        }
        
        $pdo->commit();
        jsonResponse(true, ['creadas' => $creadas], 'Notificación creada exitosamente');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, null, 'Error al crear notificación: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener mis notificaciones (para profesores/oficiales)
 */
function obtenerMisNotificaciones() {
    $usuario = verificarSesion();
    $pdo = getDBConnection();
    
    $filtro = $_GET['filtro'] ?? 'todas'; // todas, merito, demerito
    $estado = $_GET['estado'] ?? 'todas'; // todas, leido, no_leido
    
    $where = "WHERE a.notificador_id = ?";
    $params = [$usuario['id']];
    
    if ($filtro !== 'todas') {
        $where .= " AND a.tipo = ?";
        $params[] = $filtro;
    }
    
    if ($estado === 'leido') {
        $where .= " AND a.leido = 1";
    } elseif ($estado === 'no_leido') {
        $where .= " AND a.leido = 0";
    }
    
    $stmt = $pdo->prepare("SELECT a.*, 
                          u.nombre as destinatario_nombre, u.apellidos as destinatario_apellidos,
                          e.grado, e.peloton
                          FROM actividades a
                          INNER JOIN usuarios u ON a.destinatario_id = u.id
                          LEFT JOIN estudiante e ON a.destinatario_id = e.id
                          {$where}
                          ORDER BY a.fecha DESC, a.hora DESC
                          LIMIT 100");
    $stmt->execute($params);
    $notificaciones = $stmt->fetchAll();
    
    // Formatear
    foreach ($notificaciones as &$notif) {
        $notif['destinatario_nombre_completo'] = trim($notif['destinatario_nombre'] . ' ' . $notif['destinatario_apellidos']);
    }
    
    jsonResponse(true, $notificaciones);
}

/**
 * Marcar actividad como leída
 */
function marcarLeido() {
    $usuario = verificarSesion();
    $actividad_id = $_POST['actividad_id'] ?? 0;
    
    if (!$actividad_id) {
        jsonResponse(false, null, 'ID de actividad requerido', 400);
    }
    
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("UPDATE actividades SET leido = 1 WHERE id = ? AND destinatario_id = ?");
    $stmt->execute([$actividad_id, $usuario['id']]);
    
    if ($stmt->rowCount() > 0) {
        jsonResponse(true, null, 'Actividad marcada como leída');
    } else {
        jsonResponse(false, null, 'No se pudo actualizar la actividad', 500);
    }
}

/**
 * Guardar alegación para un demérito
 */
function guardarAlegacion() {
    $usuario = verificarSesion();
    
    if ($usuario['rol'] !== 'estudiante') {
        jsonResponse(false, null, 'Solo estudiantes pueden presentar alegaciones', 403);
    }
    
    $actividad_id = $_POST['actividad_id'] ?? 0;
    $alegacion = $_POST['alegacion'] ?? '';
    
    if (!$actividad_id || empty(trim($alegacion))) {
        jsonResponse(false, null, 'Datos incompletos', 400);
    }
    
    // Verificar que la actividad es del estudiante
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, tipo FROM actividades WHERE id = ? AND destinatario_id = ?");
    $stmt->execute([$actividad_id, $usuario['id']]);
    $actividad = $stmt->fetch();
    
    if (!$actividad) {
        jsonResponse(false, null, 'Actividad no encontrada', 404);
    }
    
    if ($actividad['tipo'] !== 'demerito') {
        jsonResponse(false, null, 'Solo se puede alegar sobre deméritos', 400);
    }
    
    // Verificar si ya existe alegación
    $stmt = $pdo->prepare("SELECT id FROM alegaciones WHERE actividad_id = ?");
    $stmt->execute([$actividad_id]);
    
    if ($stmt->fetch()) {
        // Actualizar alegación existente
        $stmt = $pdo->prepare("UPDATE alegaciones SET alegacion = ?, fecha_modificacion = NOW() WHERE actividad_id = ?");
        $stmt->execute([$alegacion, $actividad_id]);
    } else {
        // Insertar nueva alegación
        $stmt = $pdo->prepare("INSERT INTO alegaciones (actividad_id, alegacion, fecha_creacion) VALUES (?, ?, NOW())");
        $stmt->execute([$actividad_id, $alegacion]);
    }
    
    jsonResponse(true, null, 'Alegación guardada exitosamente');
}

/**
 * Buscar actividad en catálogos
 */
function buscarActividad() {
    $usuario = verificarSesion();
    $termino = $_GET['q'] ?? '';
    $tipo = $_GET['tipo'] ?? 'todas';
    
    if (strlen($termino) < 2) {
        jsonResponse(false, null, 'Término muy corto', 400);
    }
    
    $pdo = getDBConnection();
    $busqueda = "%{$termino}%";
    $resultados = [];
    
    if ($tipo === 'todas' || $tipo === 'merito') {
        $stmt = $pdo->prepare("SELECT id, categoria, causa as descripcion, cantidad, 'merito' as tipo 
                              FROM meritos 
                              WHERE causa LIKE ? OR categoria LIKE ?
                              LIMIT 10");
        $stmt->execute([$busqueda, $busqueda]);
        $resultados = array_merge($resultados, $stmt->fetchAll());
    }
    
    if ($tipo === 'todas' || $tipo === 'demerito') {
        $stmt = $pdo->prepare("SELECT id, categoria, falta as descripcion, demeritos_10mo as cantidad, 'demerito' as tipo 
                              FROM demeritos 
                              WHERE falta LIKE ? OR categoria LIKE ?
                              LIMIT 10");
        $stmt->execute([$busqueda, $busqueda]);
        $resultados = array_merge($resultados, $stmt->fetchAll());
    }
    
    jsonResponse(true, $resultados);
}
?>
