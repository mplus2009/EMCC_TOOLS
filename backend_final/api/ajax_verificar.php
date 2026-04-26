<?php
session_start();
require_once '../../../db.php';
header('Content-Type: application/json');
$result = $conexion->query("SELECT ultima_actualizacion FROM horario_config LIMIT 1");
$row = $result->fetch_assoc();
echo json_encode(['actualizacion' => $row['ultima_actualizacion'] ?? '']);
?>