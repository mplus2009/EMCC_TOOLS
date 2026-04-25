<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$nombre_completo = $data['nombre'] ?? '';
$password = $data['password'] ?? '';

$response = ['success' => false, 'message' => 'Credenciales incorrectas'];

if (empty($nombre_completo) || empty($password)) {
    $response['message'] = 'Todos los campos son obligatorios';
    echo json_encode($response);
    exit;
}

// Separar nombre y apellidos
$partes = explode(' ', trim($nombre_completo));
$nombre = $partes[0] ?? '';
$apellidos = isset($partes[1]) ? implode(' ', array_slice($partes, 1)) : '';

// Buscar en todas las tablas
$tablas = ['estudiante', 'profesor', 'directiva', 'oficial'];

foreach ($tablas as $tabla) {
    $sql = "SELECT id, nombre, apellidos, password, '$tabla' as cargo FROM $tabla 
            WHERE nombre = ? AND apellidos = ? AND activo = 1";
    $stmt = $conexion->prepare($sql);
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
            break;
        }
    }
    $stmt->close();
}

echo json_encode($response);
?>