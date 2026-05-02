<?php
/**
 * API de Autenticación
 * Backend - Sistema Escolar EMCC Digital
 */

require_once '../config/database.php';

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

switch ($accion) {
    case 'login':
        login();
        break;
    case 'login_qr':
        loginQR();
        break;
    case 'logout':
        logout();
        break;
    case 'verificar_sesion':
        verificarSesionAPI();
        break;
    case 'registrar_dispositivo':
        registrarDispositivo();
        break;
    default:
        jsonResponse(false, null, 'Acción no válida', 400);
}

/**
 * Iniciar sesión con usuario y contraseña
 */
function login() {
    $ci = $_POST['ci'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($ci) || empty($password)) {
        jsonResponse(false, null, 'CI y contraseña requeridos', 400);
    }
    
    $pdo = getDBConnection();
    
    // Buscar usuario por CI
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE CI = ? AND estado = 'activo' LIMIT 1");
    $stmt->execute([$ci]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, null, 'Usuario no encontrado o inactivo', 404);
    }
    
    // Verificar contraseña
    if (!password_verify($password, $usuario['password'])) {
        // Si no usa hash, verificar directamente (para compatibilidad)
        if ($password !== $usuario['password']) {
            jsonResponse(false, null, 'Contraseña incorrecta', 401);
        }
    }
    
    // Generar token de sesión
    $token = generarToken($usuario['id']);
    
    // Obtener datos adicionales según el rol
    $datos_adicionales = [];
    
    if ($usuario['rol'] === 'estudiante') {
        $stmt = $pdo->prepare("SELECT grado, peloton FROM estudiante WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        $estudiante = $stmt->fetch();
        if ($estudiante) {
            $datos_adicionales = $estudiante;
        }
    } elseif ($usuario['rol'] === 'profesor') {
        $stmt = $pdo->prepare("SELECT ocupacion FROM profesores WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        $profesor = $stmt->fetch();
        if ($profesor) {
            $datos_adicionales = ['ocupacion' => $profesor['ocupacion'] ?? 'ninguno'];
        }
    }
    
    // Preparar respuesta
    $respuesta = [
        'token' => $token,
        'usuario' => [
            'id' => $usuario['id'],
            'ci' => $usuario['CI'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellidos']),
            'rol' => $usuario['rol'],
            'email' => $usuario['email'] ?? '',
            ...$datos_adicionales
        ],
        'qr_data' => encriptarQR([
            'id' => $usuario['id'],
            'ci' => $usuario['CI'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'rol' => $usuario['rol']
        ])
    ];
    
    jsonResponse(true, $respuesta, 'Inicio de sesión exitoso');
}

/**
 * Iniciar sesión con código QR
 */
function loginQR() {
    $qr_data = $_POST['qr_data'] ?? '';
    
    if (empty($qr_data)) {
        jsonResponse(false, null, 'Datos QR requeridos', 400);
    }
    
    $datos = desencriptarQR($qr_data);
    
    if (!$datos || !isset($datos['id']) || !isset($datos['ci'])) {
        jsonResponse(false, null, 'Datos QR inválidos', 400);
    }
    
    $pdo = getDBConnection();
    
    // Buscar usuario por ID y CI
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ? AND CI = ? AND estado = 'activo' LIMIT 1");
    $stmt->execute([$datos['id'], $datos['ci']]);
    $usuario = $stmt->fetch();
    
    if (!$usuario) {
        jsonResponse(false, null, 'Usuario no encontrado o inactivo', 404);
    }
    
    // Generar token de sesión
    $token = generarToken($usuario['id']);
    
    // Obtener datos adicionales
    $datos_adicionales = [];
    
    if ($usuario['rol'] === 'estudiante') {
        $stmt = $pdo->prepare("SELECT grado, peloton FROM estudiante WHERE id = ?");
        $stmt->execute([$usuario['id']]);
        $estudiante = $stmt->fetch();
        if ($estudiante) {
            $datos_adicionales = $estudiante;
        }
    }
    
    $respuesta = [
        'token' => $token,
        'usuario' => [
            'id' => $usuario['id'],
            'ci' => $usuario['CI'],
            'nombre' => $usuario['nombre'],
            'apellidos' => $usuario['apellidos'],
            'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellidos']),
            'rol' => $usuario['rol'],
            ...$datos_adicionales
        ]
    ];
    
    jsonResponse(true, $respuesta, 'Inicio de sesión con QR exitoso');
}

/**
 * Cerrar sesión
 */
function logout() {
    $headers = getallheaders();
    $authHeader = isset($headers['Authorization']) ? $headers['Authorization'] : '';
    
    if (str_starts_with($authHeader, 'Bearer ')) {
        $token = substr($authHeader, 7);
        $pdo = getDBConnection();
        $stmt = $pdo->prepare("DELETE FROM sesiones WHERE token = ?");
        $stmt->execute([$token]);
    }
    
    jsonResponse(true, null, 'Sesión cerrada correctamente');
}

/**
 * Verificar sesión activa
 */
function verificarSesionAPI() {
    try {
        $usuario = verificarSesion();
        
        $pdo = getDBConnection();
        $datos_adicionales = [];
        
        if ($usuario['rol'] === 'estudiante') {
            $stmt = $pdo->prepare("SELECT grado, peloton FROM estudiante WHERE id = ?");
            $stmt->execute([$usuario['id']]);
            $estudiante = $stmt->fetch();
            if ($estudiante) {
                $datos_adicionales = $estudiante;
            }
        }
        
        $respuesta = [
            'usuario' => [
                'id' => $usuario['id'],
                'ci' => $usuario['CI'],
                'nombre' => $usuario['nombre'],
                'apellidos' => $usuario['apellidos'],
                'nombre_completo' => trim($usuario['nombre'] . ' ' . $usuario['apellidos']),
                'rol' => $usuario['rol'],
                ...$datos_adicionales
            ],
            'sesion_activa' => true
        ];
        
        jsonResponse(true, $respuesta);
    } catch (Exception $e) {
        jsonResponse(false, null, 'Sesión no válida', 401);
    }
}

/**
 * Registrar dispositivo para notificaciones push
 */
function registrarDispositivo() {
    $usuario = verificarSesion();
    $device_token = $_POST['device_token'] ?? '';
    $platform = $_POST['platform'] ?? 'flutter';
    
    if (empty($device_token)) {
        jsonResponse(false, null, 'Token de dispositivo requerido', 400);
    }
    
    $pdo = getDBConnection();
    
    // Verificar si ya existe
    $stmt = $pdo->prepare("SELECT id FROM dispositivos WHERE usuario_id = ? AND device_token = ?");
    $stmt->execute([$usuario['id'], $device_token]);
    
    if ($stmt->fetch()) {
        // Actualizar última conexión
        $stmt = $pdo->prepare("UPDATE dispositivos SET ultima_conexion = NOW() WHERE usuario_id = ? AND device_token = ?");
        $stmt->execute([$usuario['id'], $device_token]);
    } else {
        // Insertar nuevo dispositivo
        $stmt = $pdo->prepare("INSERT INTO dispositivos (usuario_id, device_token, platform, ultima_conexion) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$usuario['id'], $device_token, $platform]);
    }
    
    jsonResponse(true, null, 'Dispositivo registrado correctamente');
}
?>
