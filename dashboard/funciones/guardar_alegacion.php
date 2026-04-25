<?php
session_start();

// Incluir conexión
require_once __DIR__ . '/../../db.php';

header('Content-Type: application/json; charset=utf-8');

// Verificar sesión
if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_cargo = $_SESSION['usuario_cargo'];
$id_formateado = $usuario_cargo . '_' . $usuario_id;

// Verificar datos recibidos
if (!isset($_POST['actividad_id']) || !isset($_POST['alegacion'])) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit();
}

$actividad_id = (int)$_POST['actividad_id'];
$alegacion = trim($_POST['alegacion']);

if (empty($alegacion)) {
    echo json_encode(['success' => false, 'message' => 'La alegación no puede estar vacía']);
    exit();
}

// Verificar que la actividad pertenezca a este usuario y sea demérito
$sql = "SELECT id FROM actividad WHERE id = ? AND id_end = ? AND tipo = 'demerito'";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error en la base de datos']);
    exit();
}

$stmt->bind_param("is", $actividad_id, $id_formateado);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Actividad no encontrada o no autorizada']);
    $stmt->close();
    exit();
}
$stmt->close();

// Guardar alegación
$sql = "UPDATE actividad SET alegacion = ? WHERE id = ? AND id_end = ?";
$stmt = $conexion->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error al preparar la consulta']);
    exit();
}

$stmt->bind_param("sis", $alegacion, $actividad_id, $id_formateado);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Alegación guardada correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al guardar: ' . $conexion->error]);
}

$stmt->close();
$conexion->close();
?>