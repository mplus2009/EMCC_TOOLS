<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

$response = ['success' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id_end = $data['id_end'] ?? '';
    
    if (!empty($id_end)) {
        $sql = "UPDATE actividad SET leido = 1 WHERE id_end = ? AND leido = 0";
        $stmt = $conexion->prepare($sql);
        
        if ($stmt) {
            $stmt->bind_param("s", $id_end);
            if ($stmt->execute()) {
                $response = [
                    'success' => true, 
                    'afectadas' => $stmt->affected_rows
                ];
            }
            $stmt->close();
        }
    }
}

echo json_encode($response);
?>