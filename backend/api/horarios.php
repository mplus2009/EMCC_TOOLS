<?php
/**
 * API de Horarios
 * Backend - Sistema Escolar EMCC Digital
 */

require_once '../config/database.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    case 'obtener_horario':
        obtenerHorario();
        break;
    case 'obtener_asignatura_actual':
        obtenerAsignaturaActual();
        break;
    case 'guardar_horario':
        guardarHorario();
        break;
    case 'obtener_configuracion':
        obtenerConfiguracion();
        break;
    case 'guardar_configuracion':
        guardarConfiguracion();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

/**
 * Obtener horario de un grado y pelotón
 */
function obtenerHorario() {
    $usuario = verificarSesion();
    
    $grado = $_GET['grado'] ?? '';
    $peloton = (int)($_GET['peloton'] ?? 0);
    
    if (empty($grado) || $peloton <= 0) {
        // Si es estudiante, obtener su grado y pelotón
        if ($usuario['rol'] === 'estudiante') {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT grado, peloton FROM estudiante WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $est = $stmt->fetch();
            if ($est) {
                $grado = $est['grado'];
                $peloton = (int)$est['peloton'];
            }
        }
        
        if (empty($grado) || $peloton <= 0) {
            jsonResponse(false, null, 'Grado y pelotón requeridos', 400);
        }
    }
    
    $pdo = getDBConnection();
    
    // Verificar si existe la tabla horarios
    $stmt = $pdo->query("SHOW TABLES LIKE 'horarios'");
    if (!$stmt->rowCount()) {
        jsonResponse(false, null, 'Tabla de horarios no encontrada', 404);
    }
    
    $stmt = $pdo->prepare("SELECT h.*, a.nombre as asignatura_nombre 
                          FROM horarios h
                          LEFT JOIN asignaturas a ON h.asignatura_id = a.id
                          WHERE h.grado = ? AND h.peloton = ?
                          ORDER BY h.dia_semana, h.hora_inicio");
    $stmt->execute([$grado, $peloton]);
    $horario = $stmt->fetchAll();
    
    // Agrupar por día
    $dias = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $horario_agrupado = [];
    
    foreach ($dias as $dia) {
        $horario_agrupado[$dia] = array_filter($horario, fn($h) => $h['dia_semana'] === $dia);
    }
    
    jsonResponse(true, [
        'grado' => $grado,
        'peloton' => $peloton,
        'horario' => $horario_agrupado,
        'dias' => $dias
    ]);
}

/**
 * Obtener asignatura actual según hora y día
 */
function obtenerAsignaturaActual() {
    $usuario = verificarSesion();
    
    if ($usuario['rol'] !== 'estudiante') {
        jsonResponse(false, null, 'Solo estudiantes pueden consultar asignatura actual', 403);
    }
    
    $pdo = getDBConnection();
    
    // Obtener grado y pelotón del estudiante
    $stmt = $pdo->prepare("SELECT grado, peloton FROM estudiante WHERE id = ?");
    $stmt->execute([$usuario['id']]);
    $est = $stmt->fetch();
    
    if (!$est) {
        jsonResponse(false, null, 'Estudiante no encontrado', 404);
    }
    
    $grado = $est['grado'];
    $peloton = (int)$est['peloton'];
    
    // Obtener hora y día actual
    $hora_actual = date('H:i');
    $dia_numero = (int)date('N'); // 1=Lunes, 7=Domingo
    $dias = ['', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
    $dia_actual = $dias[$dia_numero];
    
    // Buscar asignatura en curso
    $stmt = $pdo->prepare("SELECT h.*, a.nombre as asignatura_nombre 
                          FROM horarios h
                          LEFT JOIN asignaturas a ON h.asignatura_id = a.id
                          WHERE h.grado = ? AND h.peloton = ? AND h.dia_semana = ?
                          AND h.hora_inicio <= ? AND h.hora_fin >= ?
                          LIMIT 1");
    $stmt->execute([$grado, $peloton, $dia_actual, $hora_actual, $hora_actual]);
    $asignatura = $stmt->fetch();
    
    if ($asignatura) {
        jsonResponse(true, [
            'asignatura' => $asignatura['asignatura_nombre'] ?? 'Sin asignatura',
            'hora_inicio' => $asignatura['hora_inicio'],
            'hora_fin' => $asignatura['hora_fin'],
            'tipo_evento' => $asignatura['tipo_evento'] ?? 'asignatura'
        ]);
    } else {
        jsonResponse(true, null, 'No hay clase en este momento');
    }
}

/**
 * Guardar horario (solo directiva)
 */
function guardarHorario() {
    $usuario = verificarSesion();
    
    if ($usuario['rol'] !== 'directiva') {
        jsonResponse(false, null, 'Solo directiva puede editar horarios', 403);
    }
    
    $grado = $_POST['grado'] ?? '';
    $peloton = (int)($_POST['peloton'] ?? 0);
    $horario_data = json_decode($_POST['horario'] ?? '[]', true);
    
    if (empty($grado) || $peloton <= 0 || empty($horario_data)) {
        jsonResponse(false, null, 'Datos incompletos', 400);
    }
    
    $pdo = getDBConnection();
    
    try {
        $pdo->beginTransaction();
        
        // Eliminar horario existente
        $stmt = $pdo->prepare("DELETE FROM horarios WHERE grado = ? AND peloton = ?");
        $stmt->execute([$grado, $peloton]);
        
        // Insertar nuevo horario
        $stmt = $pdo->prepare("INSERT INTO horarios 
            (grado, peloton, dia_semana, hora_inicio, hora_fin, asignatura_id, tipo_evento) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($horario_data as $item) {
            $stmt->execute([
                $grado,
                $peloton,
                $item['dia_semana'],
                $item['hora_inicio'],
                $item['hora_fin'],
                $item['asignatura_id'] ?? null,
                $item['tipo_evento'] ?? 'asignatura'
            ]);
        }
        
        $pdo->commit();
        jsonResponse(true, null, 'Horario guardado exitosamente');
        
    } catch (Exception $e) {
        $pdo->rollBack();
        jsonResponse(false, null, 'Error al guardar horario: ' . $e->getMessage(), 500);
    }
}

/**
 * Obtener configuración de horario
 */
function obtenerConfiguracion() {
    $usuario = verificarSesion();
    
    $pdo = getDBConnection();
    
    // Obtener grados disponibles
    $stmt = $pdo->query("SELECT DISTINCT grado FROM estudiante ORDER BY grado");
    $grados = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Obtener configuración de alarmas
    $config = [];
    $stmt = $pdo->query("SELECT * FROM configuracion_horario LIMIT 1");
    if ($stmt->rowCount()) {
        $config = $stmt->fetch();
    }
    
    jsonResponse(true, [
        'grados' => $grados,
        'configuracion' => $config
    ]);
}

/**
 * Guardar configuración (solo directiva)
 */
function guardarConfiguracion() {
    $usuario = verificarSesion();
    
    if ($usuario['rol'] !== 'directiva') {
        jsonResponse(false, null, 'Solo directiva puede modificar configuración', 403);
    }
    
    $config_data = json_decode($_POST['configuracion'] ?? '{}', true);
    
    $pdo = getDBConnection();
    
    // Verificar si existe configuración
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM configuracion_horario");
    $existe = $stmt->fetch()['count'] > 0;
    
    if ($existe) {
        $stmt = $pdo->prepare("UPDATE configuracion_horario SET 
            alerta_demeritos = ?, hora_entrada = ?, hora_salida = ?
            WHERE id = (SELECT id FROM configuracion_horario LIMIT 1)");
    } else {
        $stmt = $pdo->prepare("INSERT INTO configuracion_horario 
            (alerta_demeritos, hora_entrada, hora_salida) VALUES (?, ?, ?)");
    }
    
    $stmt->execute([
        $config_data['alerta_demeritos'] ?? 3,
        $config_data['hora_entrada'] ?? '07:00',
        $config_data['hora_salida'] ?? '17:00'
    ]);
    
    jsonResponse(true, null, 'Configuración guardada exitosamente');
}
?>
