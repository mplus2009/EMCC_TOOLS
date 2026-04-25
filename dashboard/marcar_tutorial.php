<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $usuario_id = $_SESSION['usuario_id'] ?? 0;
    $usuario_cargo = $_SESSION['usuario_cargo'] ?? '';
    
    if ($usuario_id > 0) {
        // Verificar si la tabla existe
        $check = $conexion->query("SHOW TABLES LIKE 'tutorial_visto'");
        if ($check && $check->num_rows == 0) {
            $conexion->query("CREATE TABLE tutorial_visto (
                id INT AUTO_INCREMENT PRIMARY KEY,
                usuario_id INT NOT NULL,
                usuario_cargo VARCHAR(20) NOT NULL,
                visto BOOLEAN DEFAULT FALSE,
                fecha_visto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_usuario (usuario_id, usuario_cargo)
            )");
        }
        
        $sql = "INSERT INTO tutorial_visto (usuario_id, usuario_cargo, visto) 
                VALUES (?, ?, TRUE) 
                ON DUPLICATE KEY UPDATE visto = TRUE, fecha_visto = NOW()";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("is", $usuario_id, $usuario_cargo);
        $response['success'] = $stmt->execute();
        $stmt->close();
    }
}

echo json_encode($response);
?>