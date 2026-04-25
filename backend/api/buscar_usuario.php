<?php
session_start();

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode([]);
    exit();
}

require_once '../db.php';

header('Content-Type: application/json; charset=utf-8');

$q = isset($_GET['q']) ? trim($_GET['q']) : '';

if (strlen($q) < 2) {
    echo json_encode([]);
    exit();
}

$query = "%$q%";

$sql = "SELECT id, nombre, apellidos, CI, grado 
        FROM estudiante 
        WHERE nombre LIKE ? OR apellidos LIKE ? OR CI LIKE ? OR CAST(id AS CHAR) LIKE ?
        ORDER BY apellidos, nombre 
        LIMIT 15";

$stmt = $conexion->prepare($sql);
$stmt->bind_param("ssss", $query, $query, $query, $query);
$stmt->execute();
$result = $stmt->get_result();

$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = [
        'id' => (int)$row['id'],
        'nombre' => $row['nombre'],
        'apellidos' => $row['apellidos'],
        'ci' => $row['CI'],
        'grado' => $row['grado'] ? $row['grado'] : '10mo'
    ];
}
$stmt->close();

echo json_encode($usuarios);
?>