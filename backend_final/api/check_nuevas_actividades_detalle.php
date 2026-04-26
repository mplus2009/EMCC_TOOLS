<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$usuario_id = $_GET['id'] ?? 0;
$ultimo_id = $_GET['ultimo'] ?? 0;

$nuevas = 0;
$actividad = '';
$ultimo_id_nuevo = $ultimo_id;

if ($usuario_id > 0) {
    $sql = "SELECT COUNT(*) as total, MAX(id) as max_id FROM actividad WHERE id_end = ? AND leido = 0 AND id > ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ii", $usuario_id, $ultimo_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $nuevas = $row['total'] ?? 0;
        $ultimo_id_nuevo = $row['max_id'] ?? $ultimo_id;
        $stmt->close();
    }
    
    if ($nuevas > 0) {
        $sql = "SELECT falta_causa FROM actividad WHERE id_end = ? AND leido = 0 ORDER BY id DESC LIMIT 1";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $actividad = $row['falta_causa'] ?? '';
            $stmt->close();
        }
    }
}

echo json_encode([
    'nuevas' => $nuevas,
    'cantidad' => $nuevas,
    'actividad' => $actividad,
    'ultimo_id' => $ultimo_id_nuevo
]);
?>