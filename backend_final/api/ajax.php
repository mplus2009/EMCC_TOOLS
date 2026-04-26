<?php
session_start();
require_once '../../../db.php';

header('Content-Type: application/json');

$response = ['actualizacion' => null];

$result = $conexion->query("SELECT ultima_actualizacion FROM horario_config LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $response['actualizacion'] = $row['ultima_actualizacion'];
}

echo json_encode($response);
?>