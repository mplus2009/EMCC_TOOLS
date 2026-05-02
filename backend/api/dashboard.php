<?php
/**
 * API de Dashboard
 * Maneja las operaciones del dashboard: actividades, perfil, notificaciones
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
    case 'obtener_datos':
        obtenerDatosDashboard();
        break;
    case 'obtener_actividades':
        obtenerActividades();
        break;
    case 'buscar_usuario':
        buscarUsuario();
        break;
    case 'buscar_actividad':
        buscarActividad();
        break;
    case 'marcar_leido':
        marcarLeido();
        break;
    case 'guardar_alegacion':
        guardarAlegacion();
        break;
    case 'obtener_perfil':
        obtenerPerfil();
        break;
    default:
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Acción no válida',
            'code' => 'INVALID_ACTION'
        ], 400);
}

function obtenerDatosDashboard() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'No autorizado',
            'code' => 'UNAUTHORIZED'
        ], 401);
    }
    
    $conexion = obtenerConexion();
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_cargo = $_SESSION['usuario_cargo'];
    
    // Obtener estadísticas según el cargo
    $datos = [
        'usuario_nombre' => $_SESSION['usuario_nombre'],
        'usuario_apellidos' => $_SESSION['usuario_apellidos'],
        'usuario_cargo' => $usuario_cargo
    ];
    
    if ($usuario_cargo === 'estudiante') {
        $id_end = 'estudiante_' . $usuario_id;
        
        // Balance
        $sql_balance = "SELECT 
                        SUM(CASE WHEN tipo = 'merito' THEN cantidad ELSE 0 END) as total_meritos,
                        SUM(CASE WHEN tipo = 'demerito' THEN cantidad ELSE 0 END) as total_demeritos
                        FROM actividad WHERE id_end = ?";
        $stmt = $conexion->prepare($sql_balance);
        $stmt->bind_param("s", $id_end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $datos['balance'] = [
            'meritos' => (int)($result['total_meritos'] ?? 0),
            'demeritos' => (int)($result['total_demeritos'] ?? 0)
        ];
        
        // Actividades recientes
        $sql_actividades = "SELECT * FROM actividad WHERE id_end = ? ORDER BY fecha DESC, hora DESC LIMIT 50";
        $stmt = $conexion->prepare($sql_actividades);
        $stmt->bind_param("s", $id_end);
        $stmt->execute();
        $result = $stmt->get_result();
        $actividades = [];
        while ($row = $result->fetch_assoc()) {
            $actividades[] = $row;
        }
        $stmt->close();
        
        $datos['actividades'] = $actividades;
        
        // Contar no leídos
        $sql_no_leidos = "SELECT COUNT(*) as count FROM actividad WHERE id_end = ? AND leido = 0";
        $stmt = $conexion->prepare($sql_no_leidos);
        $stmt->bind_param("s", $id_end);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $datos['no_leidos'] = (int)$result['count'];
    }
    
    enviarRespuestaJSON([
        'success' => true,
        'data' => $datos
    ]);
}

function obtenerActividades() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_cargo = $_SESSION['usuario_cargo'];
    
    if ($usuario_cargo === 'estudiante') {
        $id_end = 'estudiante_' . $usuario_id;
        $sql = "SELECT * FROM actividad WHERE id_end = ? ORDER BY fecha DESC, hora DESC";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("s", $id_end);
    } else {
        $id_star_prefix = $usuario_cargo . '_';
        $sql = "SELECT * FROM actividad WHERE id_star LIKE ? ORDER BY fecha DESC, hora DESC";
        $stmt = $conexion->prepare($sql);
        $like_param = $id_star_prefix . '%';
        $stmt->bind_param("s", $like_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $actividades = [];
    while ($row = $result->fetch_assoc()) {
        $actividades[] = $row;
    }
    $stmt->close();
    
    enviarRespuestaJSON([
        'success' => true,
        'data' => ['actividades' => $actividades]
    ]);
}

function buscarUsuario() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    $busqueda = $_GET['q'] ?? '';
    $cargo_filtro = $_GET['cargo'] ?? 'estudiante';
    
    if (empty($busqueda)) {
        enviarRespuestaJSON(['success' => false, 'message' => 'Término de búsqueda requerido'], 400);
    }
    
    $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
    if (!in_array($cargo_filtro, $tablas_permitidas)) {
        enviarRespuestaJSON(['success' => false, 'message' => 'Cargo no válido'], 400);
    }
    
    $busqueda_upper = strtoupper(eliminarTildes($busqueda));
    
    $sql = "SELECT id, nombre, apellidos, ci, grado, grupo FROM $cargo_filtro 
            WHERE UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) LIKE ? 
            OR UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) LIKE ?
            OR ci LIKE ?
            LIMIT 20";
    
    $like_param = '%' . $busqueda_upper . '%';
    $ci_like = '%' . $busqueda . '%';
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("sss", $like_param, $like_param, $ci_like);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $usuarios = [];
    while ($row = $result->fetch_assoc()) {
        $usuarios[] = $row;
    }
    $stmt->close();
    
    enviarRespuestaJSON([
        'success' => true,
        'data' => ['usuarios' => $usuarios]
    ]);
}

function buscarActividad() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    $tipo = $_GET['tipo'] ?? 'merito';
    
    if ($tipo === 'merito') {
        $sql = "SELECT * FROM meritos ORDER BY nombre";
    } else {
        $sql = "SELECT * FROM demeritos ORDER BY nombre";
    }
    
    $result = $conexion->query($sql);
    $actividades = [];
    while ($row = $result->fetch_assoc()) {
        $actividades[] = $row;
    }
    
    enviarRespuestaJSON([
        'success' => true,
        'data' => ['actividades' => $actividades]
    ]);
}

function marcarLeido() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    $actividad_id = $_POST['actividad_id'] ?? 0;
    
    if (!$actividad_id) {
        enviarRespuestaJSON(['success' => false, 'message' => 'ID de actividad requerido'], 400);
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    $id_end = 'estudiante_' . $usuario_id;
    
    $sql = "UPDATE actividad SET leido = 1 WHERE id = ? AND id_end = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("is", $actividad_id, $id_end);
    
    if ($stmt->execute()) {
        enviarRespuestaJSON(['success' => true, 'message' => 'Actividad marcada como leída']);
    } else {
        enviarRespuestaJSON(['success' => false, 'message' => 'Error al actualizar'], 500);
    }
}

function guardarAlegacion() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    $actividad_id = $_POST['actividad_id'] ?? 0;
    $texto_alegacion = $_POST['texto_alegacion'] ?? '';
    
    if (!$actividad_id || empty($texto_alegacion)) {
        enviarRespuestaJSON(['success' => false, 'message' => 'Datos incompletos'], 400);
    }
    
    $usuario_id = $_SESSION['usuario_id'];
    $id_end = 'estudiante_' . $usuario_id;
    
    // Verificar que la actividad pertenece al usuario
    $sql_check = "SELECT id FROM actividad WHERE id = ? AND id_end = ?";
    $stmt = $conexion->prepare($sql_check);
    $stmt->bind_param("is", $actividad_id, $id_end);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        enviarRespuestaJSON(['success' => false, 'message' => 'Actividad no encontrada'], 404);
    }
    $stmt->close();
    
    // Insertar o actualizar alegación
    $sql = "INSERT INTO alegaciones (actividad_id, texto, fecha_creacion) 
            VALUES (?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE texto = ?, fecha_creacion = NOW()";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("iss", $actividad_id, $texto_alegacion, $texto_alegacion);
    
    if ($stmt->execute()) {
        enviarRespuestaJSON(['success' => true, 'message' => 'Alegación guardada correctamente']);
    } else {
        enviarRespuestaJSON(['success' => false, 'message' => 'Error al guardar alegación'], 500);
    }
}

function obtenerPerfil() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    $usuario_id = $_SESSION['usuario_id'];
    $usuario_cargo = $_SESSION['usuario_cargo'];
    
    $sql = "SELECT * FROM $usuario_cargo WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $perfil = $result->fetch_assoc();
        enviarRespuestaJSON([
            'success' => true,
            'data' => ['perfil' => $perfil]
        ]);
    } else {
        enviarRespuestaJSON(['success' => false, 'message' => 'Perfil no encontrado'], 404);
    }
}
?>
