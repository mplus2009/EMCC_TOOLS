<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$nombre_completo = trim($data['nombre'] ?? '');
$password = $data['password'] ?? '';

if (empty($nombre_completo) || empty($password)) {
    echo json_encode(['success' => false, 'message' => 'Todos los campos son obligatorios']);
    exit;
}

$partes = explode(' ', $nombre_completo);
$nombre = $partes[0] ?? '';
$apellidos = isset($partes[1]) ? implode(' ', array_slice($partes, 1)) : '';

// BUSCAR EN TODAS LAS TABLAS
$tablas = ['directiva', 'oficial', 'profesor', 'estudiante'];
$encontrado = false;
$response = ['success' => false, 'message' => 'Credenciales incorrectas'];

foreach ($tablas as $tabla) {
    $sql = "SELECT id, nombre, apellidos, password, '$tabla' as cargo FROM $tabla WHERE nombre = ? AND apellidos = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $nombre, $apellidos);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            if (password_verify($password, $row['password']) || $password === $row['password']) {
                $response = [
                    'success' => true,
                    'id' => $row['id'],
                    'nombre' => $row['nombre'] . ' ' . $row['apellidos'],
                    'cargo' => $row['cargo']
                ];
                $encontrado = true;
                break;
            }
        }
        $stmt->close();
    }
    if ($encontrado) break;
}

echo json_encode($response);
?>