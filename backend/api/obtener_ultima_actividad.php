<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$usuario_id = $_GET['id'] ?? 0;
$ultimo_id = 0;

if ($usuario_id > 0) {
    $sql = "SELECT MAX(id) as ultimo_id FROM actividad WHERE id_end = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $ultimo_id = $row['ultimo_id'] ?? 0;
        $stmt->close();
    }
}

echo json_encode(['ultimo_id' => $ultimo_id]);
?>