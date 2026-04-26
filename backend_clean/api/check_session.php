<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

$response = ['logged_in' => false];

if (isset($_SESSION['usuario_id']) && isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    $response['logged_in'] = true;
    $response['usuario'] = [
        'id' => $_SESSION['usuario_id'],
        'nombre' => $_SESSION['usuario_nombre'],
        'apellidos' => $_SESSION['usuario_apellidos'],
        'ci' => $_SESSION['usuario_ci'] ?? '',
        'cargo' => $_SESSION['usuario_cargo']
    ];
}

echo json_encode($response);
?>
