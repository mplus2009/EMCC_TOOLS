<?php
// ============================================
// CHECK_SESSION.PHP - Verificar sesión (con CORS)
// ============================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

$response = [
    'logueado' => false,
    'usuario_id' => null,
    'usuario_nombre' => null,
    'usuario_cargo' => null
];

if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    $response['logueado'] = true;
    $response['usuario_id'] = $_SESSION['usuario_id'] ?? null;
    $response['usuario_nombre'] = $_SESSION['usuario_nombre'] ?? '';
    $response['usuario_cargo'] = $_SESSION['usuario_cargo'] ?? '';
}

echo json_encode($response);
?>
