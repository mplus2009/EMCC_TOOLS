<?php
session_start();
require_once '../../../db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

if (($_SESSION['usuario_cargo'] ?? '') !== 'directiva') {
    $response['message'] = 'No tienes permiso';
    echo json_encode($response);
    exit();
}

$accion = $_POST['accion'] ?? '';

// ============================================
// GUARDAR CONFIGURACIÓN
// ============================================
if ($accion === 'guardar_config') {
    $hora_inicio = $_POST['hora_inicio'] ?? '08:00';
    $duracion_lj = (int)($_POST['duracion_lunes_jueves'] ?? 45);
    $duracion_v = (int)($_POST['duracion_viernes'] ?? 40);
    $descanso = (int)($_POST['descanso'] ?? 5);
    $merienda_turno = (int)($_POST['merienda_turno'] ?? 3);
    $merienda_duracion = (int)($_POST['merienda_duracion'] ?? 15);
    $viernes_merienda = isset($_POST['viernes_merienda']) ? 1 : 0;
    $sabado_clases = isset($_POST['sabado_clases']) ? 1 : 0;
    
    $check = $conexion->query("SELECT id FROM horario_config LIMIT 1");
    if ($check && $check->num_rows > 0) {
        $sql = "UPDATE horario_config SET hora_inicio=?, duracion_turno_lunes_jueves=?, duracion_turno_viernes=?, descanso_entre_turnos=?, merienda_despues_turno=?, duracion_merienda=?, viernes_con_merienda=?, sabado_con_clases=?, ultima_actualizacion=NOW() WHERE id=1";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("siiiiiii", $hora_inicio, $duracion_lj, $duracion_v, $descanso, $merienda_turno, $merienda_duracion, $viernes_merienda, $sabado_clases);
    } else {
        $sql = "INSERT INTO horario_config (hora_inicio, duracion_turno_lunes_jueves, duracion_turno_viernes, descanso_entre_turnos, merienda_despues_turno, duracion_merienda, viernes_con_merienda, sabado_con_clases) VALUES (?,?,?,?,?,?,?,?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("siiiiiii", $hora_inicio, $duracion_lj, $duracion_v, $descanso, $merienda_turno, $merienda_duracion, $viernes_merienda, $sabado_clases);
    }
    $response['success'] = $stmt->execute();
    if (!$response['success']) $response['message'] = $stmt->error;
    $stmt->close();
}

// ============================================
// AGREGAR TURNO
// ============================================
elseif ($accion === 'agregar_turno') {
    $peloton_id = (int)($_POST['peloton_id'] ?? 0);
    $dia = (int)($_POST['dia'] ?? 1);
    $turno = (int)($_POST['turno'] ?? 1);
    $asignatura_id = (int)($_POST['asignatura_id'] ?? 0);
    $duracion = (int)($_POST['duracion'] ?? 1);
    $tipo = $_POST['tipo'] ?? 'asignatura';
    $semana = $_POST['semana'] ?? 'esta';
    
    if ($peloton_id == 0) {
        $response['message'] = 'Pelotón no válido';
        echo json_encode($response);
        exit();
    }
    
    if ($asignatura_id == 0) {
        $response['message'] = 'Selecciona una asignatura';
        echo json_encode($response);
        exit();
    }
    
    // Verificar si ya existe un turno en esa posición
    $check = $conexion->prepare("SELECT id FROM horario_asignaturas WHERE peloton_id=? AND dia_semana=? AND turno_inicio=? AND semana=?");
    $check->bind_param("iiis", $peloton_id, $dia, $turno, $semana);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $response['message'] = 'Ya existe un turno en esa posición';
        echo json_encode($response);
        exit();
    }
    $check->close();
    
    $sql = "INSERT INTO horario_asignaturas (peloton_id, dia_semana, turno_inicio, turnos_duracion, asignatura_id, tipo_evento, semana) VALUES (?,?,?,?,?,?,?)";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iiiiiss", $peloton_id, $dia, $turno, $duracion, $asignatura_id, $tipo, $semana);
    $response['success'] = $stmt->execute();
    if (!$response['success']) $response['message'] = $stmt->error;
    $stmt->close();
}

// ============================================
// GUARDAR TURNO (EDITAR)
// ============================================
elseif ($accion === 'guardar_turno') {
    $turno_id = (int)($_POST['turno_id'] ?? 0);
    $asignatura_id = (int)($_POST['asignatura_id'] ?? 0);
    $duracion = (int)($_POST['duracion'] ?? 1);
    $tipo = $_POST['tipo'] ?? 'asignatura';
    
    if ($turno_id > 0 && $asignatura_id > 0) {
        $sql = "UPDATE horario_asignaturas SET asignatura_id=?, turnos_duracion=?, tipo_evento=? WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("iisi", $asignatura_id, $duracion, $tipo, $turno_id);
        $response['success'] = $stmt->execute();
        if (!$response['success']) $response['message'] = $stmt->error;
        $stmt->close();
    }
}

// ============================================
// ELIMINAR TURNO
// ============================================
elseif ($accion === 'eliminar_turno') {
    $turno_id = (int)($_POST['turno_id'] ?? 0);
    if ($turno_id > 0) {
        $sql = "DELETE FROM horario_asignaturas WHERE id=?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $turno_id);
        $response['success'] = $stmt->execute();
        if (!$response['success']) $response['message'] = $stmt->error;
        $stmt->close();
    }
}

echo json_encode($response);
?>