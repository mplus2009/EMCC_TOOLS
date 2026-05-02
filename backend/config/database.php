<?php
/**
 * Configuración de Base de Datos
 * Backend API - Sistema Escolar EMCC Digital
 */

// Configuración de la base de datos
define('DB_HOST', 'sql211.infinityfree.com');
define('DB_NAME', 'if0_41266869_usuarios_escuela');
define('DB_USER', 'if0_41266869');
define('DB_PASS', 'mplus2009');
define('DB_CHARSET', 'utf8mb4');

// Configuración del sistema
define('SYSTEM_NAME', 'EMCC DIGITAL');
define('API_VERSION', '1.0.0');

// Configuración de CORS para Flutter
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Max-Age: 86400');
header('Content-Type: application/json; charset=utf-8');

// Manejar preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

/**
 * Obtener conexión PDO a la base de datos
 */
function getDBConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        return new PDO($dsn, DB_USER, DB_PASS, $options);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
        exit();
    }
}

/**
 * Obtener conexión MySQLi (para compatibilidad)
 */
function getMySQLiConnection() {
    $conexion = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conexion->connect_error) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => 'Error de conexión a la base de datos']);
        exit();
    }
    $conexion->set_charset(DB_CHARSET);
    return $conexion;
}

/**
 * Respuesta JSON estándar
 */
function jsonResponse($success, $data = null, $message = '', $code = 200) {
    http_response_code($code);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit();
}

/**
 * Verificar token de sesión
 */
function verificarSesion() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
        jsonResponse(false, null, 'Token no proporcionado', 401);
    }
    
    $token = substr($authHeader, 7);
    
    // Verificar token en la base de datos
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT u.*, s.token FROM usuarios u 
                          INNER JOIN sesiones s ON u.id = s.usuario_id 
                          WHERE s.token = ? AND s.expira > NOW()");
    $stmt->execute([$token]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, null, 'Token inválido o expirado', 401);
    }
    
    return $usuario;
}

/**
 * Generar token de sesión
 */
function generarToken($usuario_id) {
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+7 days'));
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("INSERT INTO sesiones (usuario_id, token, expira) VALUES (?, ?, ?)");
    $stmt->execute([$usuario_id, $token, $expira]);
    
    return $token;
}

/**
 * Encriptar datos para QR
 */
function encriptarQR($datos) {
    return base64_encode(json_encode($datos));
}

/**
 * Desencriptar datos de QR
 */
function desencriptarQR($datos) {
    return json_decode(base64_decode($datos), true);
}
?>
