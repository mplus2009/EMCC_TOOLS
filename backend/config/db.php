<?php
$host = 'sql211.infinityfree.com';
$usuario_db = 'if0_41266869';
$password_db = 'mplus2009';
$nombre_db = 'if0_41266869_usuario_use';

$conexion = new mysqli($host, $usuario_db, $password_db, $nombre_db);

if ($conexion->connect_error) {
    die('Error de conexión: ' . $conexion->connect_error);
}

$conexion->set_charset('utf8');

function eliminarTildes($texto) {
    $noTildes = [
        'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
        'ñ' => 'n', 'Ñ' => 'N'
    ];
    return strtr($texto, $noTildes);
}
?>