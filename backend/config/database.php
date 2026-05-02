<?php
/**
 * Configuración de la base de datos
 * Este archivo debe ser configurado con las credenciales del servidor
 */

$host = 'sql211.infinityfree.com';
$usuario_db = 'if0_41266869';
$password_db = 'mplus2009';
$nombre_db = 'if0_41266869_usuario_use';

function obtenerConexion() {
    global $host, $usuario_db, $password_db, $nombre_db;
    
    $conexion = new mysqli($host, $usuario_db, $password_db, $nombre_db);
    
    if ($conexion->connect_error) {
        die('Error de conexión: ' . $conexion->connect_error);
    }
    
    $conexion->set_charset('utf8');
    return $conexion;
}

function eliminarTildes($texto) {
    $noTildes = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
        'ñ' => 'n', 'Ñ' => 'N'
    ];
    return strtr($texto, $noTildes);
}

function enviarRespuestaJSON($datos, $codigo = 200) {
    http_response_code($codigo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE);
    exit();
}

function validarSesion() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'No autorizado. Debe iniciar sesión.',
            'code' => 'UNAUTHORIZED'
        ], 401);
    }
}
?>
