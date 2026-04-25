<?php
session_start();
require_once '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    echo json_encode([]);
    exit();
}

$query = trim($_GET['q'] ?? '');
$tipo = $_GET['tipo'] ?? 'merito';
$resultados = [];

if (strlen($query) >= 1) {
    $buscar = "%$query%";
    
    if ($tipo === 'merito') {
        $sql = "SELECT id, categoria, causa as nombre, meritos as valor 
                FROM meritos 
                WHERE LOWER(causa) LIKE LOWER(?) OR LOWER(categoria) LIKE LOWER(?)
                ORDER BY categoria, causa
                LIMIT 20";
    } else {
        $sql = "SELECT id, categoria, falta as nombre, demeritos_10mo, demeritos_11_12 
                FROM demeritos 
                WHERE LOWER(falta) LIKE LOWER(?) OR LOWER(categoria) LIKE LOWER(?)
                ORDER BY categoria, falta
                LIMIT 20";
    }
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("ss", $buscar, $buscar);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $resultados[] = $row;
    }
    $stmt->close();
}

echo json_encode($resultados);
?>