<?php
/**
 * API de Autenticación
 * Maneja el login, logout y validación de sesiones
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'login':
        login();
        break;
    case 'logout':
        logout();
        break;
    case 'verificar_sesion':
        verificarSesion();
        break;
    case 'login_qr':
        loginQR();
        break;
    default:
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Acción no válida',
            'code' => 'INVALID_ACTION'
        ], 400);
}

function login() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Método no permitido',
            'code' => 'METHOD_NOT_ALLOWED'
        ], 405);
    }
    
    session_start();
    $conexion = obtenerConexion();
    
    $nombre = strtoupper(eliminarTildes($_POST['nombre'] ?? ''));
    $apellidos = strtoupper(eliminarTildes($_POST['apellidos'] ?? ''));
    $password = $_POST['password'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    
    if (!$nombre || !$apellidos || !$password || !$cargo) {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Todos los campos son obligatorios',
            'code' => 'MISSING_FIELDS'
        ], 400);
    }
    
    $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
    if (!in_array($cargo, $tablas_permitidas)) {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Cargo no válido',
            'code' => 'INVALID_CARGO'
        ], 400);
    }
    
    $sql = "SELECT * FROM $cargo WHERE 
            UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ? 
            AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?";
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Error en la consulta',
            'code' => 'DATABASE_ERROR'
        ], 500);
    }
    
    $stmt->bind_param("ss", $nombre, $apellidos);
    $stmt->execute();
    $resultado = $stmt->get_result();
    
    $usuario_encontrado = null;
    while ($row = $resultado->fetch_assoc()) {
        if (password_verify($password, $row['password']) || $password === $row['password']) {
            $usuario_encontrado = $row;
            break;
        }
    }
    
    if ($usuario_encontrado) {
        $_SESSION['usuario_id'] = $usuario_encontrado['id'];
        $_SESSION['usuario_nombre'] = $usuario_encontrado['nombre'];
        $_SESSION['usuario_apellidos'] = $usuario_encontrado['apellidos'];
        $_SESSION['usuario_ci'] = $usuario_encontrado['ci'] ?? '';
        $_SESSION['usuario_cargo'] = $cargo;
        $_SESSION['logueado'] = true;
        
        enviarRespuestaJSON([
            'success' => true,
            'message' => 'Login exitoso',
            'data' => [
                'usuario_id' => $_SESSION['usuario_id'],
                'usuario_nombre' => $_SESSION['usuario_nombre'],
                'usuario_apellidos' => $_SESSION['usuario_apellidos'],
                'usuario_ci' => $_SESSION['usuario_ci'],
                'usuario_cargo' => $_SESSION['usuario_cargo']
            ]
        ]);
    } else {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Usuario no encontrado o contraseña incorrecta',
            'code' => 'INVALID_CREDENTIALS'
        ], 401);
    }
}

function loginQR() {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Método no permitido',
            'code' => 'METHOD_NOT_ALLOWED'
        ], 405);
    }
    
    session_start();
    $conexion = obtenerConexion();
    
    $qr_text = $_POST['qr_text'] ?? '';
    $usuario_qr = null;
    
    $qr_text = urldecode($qr_text);
    
    try { 
        $json = base64_decode($qr_text);
        $usuario_qr = json_decode($json, true);
    } catch (Exception $e) {}
    
    if (!$usuario_qr) {
        try { 
            $json = base64_decode(str_replace(['-', '_'], ['+', '/'], $qr_text));
            $usuario_qr = json_decode($json, true);
        } catch (Exception $e) {}
    }
    
    if ($usuario_qr && isset($usuario_qr['id']) && isset($usuario_qr['cargo'])) {
        $id = $usuario_qr['id'];
        $cargo = $usuario_qr['cargo'];
        
        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
        
        if (in_array($cargo, $tablas_permitidas)) {
            $sql = "SELECT * FROM $cargo WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows === 1) {
                    $usuario = $resultado->fetch_assoc();
                    
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                    $_SESSION['usuario_ci'] = $usuario['ci'] ?? '';
                    $_SESSION['usuario_cargo'] = $cargo;
                    $_SESSION['logueado'] = true;
                    
                    enviarRespuestaJSON([
                        'success' => true,
                        'message' => 'Login con QR exitoso',
                        'data' => [
                            'usuario_id' => $_SESSION['usuario_id'],
                            'usuario_nombre' => $_SESSION['usuario_nombre'],
                            'usuario_apellidos' => $_SESSION['usuario_apellidos'],
                            'usuario_ci' => $_SESSION['usuario_ci'],
                            'usuario_cargo' => $_SESSION['usuario_cargo']
                        ]
                    ]);
                }
                $stmt->close();
            }
        }
    }
    
    if ($usuario_qr && isset($usuario_qr['nombre']) && isset($usuario_qr['apellidos'])) {
        $nombre = strtoupper(eliminarTildes($usuario_qr['nombre']));
        $apellidos = strtoupper(eliminarTildes($usuario_qr['apellidos']));
        $cargo = $usuario_qr['cargo'] ?? 'estudiante';
        
        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
        
        if (in_array($cargo, $tablas_permitidas)) {
            $sql = "SELECT * FROM $cargo WHERE 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ? 
                    AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?";
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $nombre, $apellidos);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado->num_rows === 1) {
                    $usuario = $resultado->fetch_assoc();
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                    $_SESSION['usuario_ci'] = $usuario['ci'] ?? '';
                    $_SESSION['usuario_cargo'] = $cargo;
                    $_SESSION['logueado'] = true;
                    
                    enviarRespuestaJSON([
                        'success' => true,
                        'message' => 'Login con QR exitoso',
                        'data' => [
                            'usuario_id' => $_SESSION['usuario_id'],
                            'usuario_nombre' => $_SESSION['usuario_nombre'],
                            'usuario_apellidos' => $_SESSION['usuario_apellidos'],
                            'usuario_ci' => $_SESSION['usuario_ci'],
                            'usuario_cargo' => $_SESSION['usuario_cargo']
                        ]
                    ]);
                }
                $stmt->close();
            }
        }
    }
    
    enviarRespuestaJSON([
        'success' => false,
        'message' => 'QR no válido o usuario no encontrado',
        'code' => 'INVALID_QR'
    ], 401);
}

function logout() {
    session_start();
    session_destroy();
    
    enviarRespuestaJSON([
        'success' => true,
        'message' => 'Sesión cerrada correctamente'
    ]);
}

function verificarSesion() {
    session_start();
    
    if (isset($_SESSION['usuario_id']) && isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
        enviarRespuestaJSON([
            'success' => true,
            'data' => [
                'usuario_id' => $_SESSION['usuario_id'],
                'usuario_nombre' => $_SESSION['usuario_nombre'],
                'usuario_apellidos' => $_SESSION['usuario_apellidos'],
                'usuario_ci' => $_SESSION['usuario_ci'] ?? '',
                'usuario_cargo' => $_SESSION['usuario_cargo']
            ]
        ]);
    } else {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'No hay sesión activa',
            'code' => 'NO_SESSION'
        ], 401);
    }
}
?>
