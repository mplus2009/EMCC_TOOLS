<?php
/**
 * API de Notificaciones
 * Maneja el registro de méritos y deméritos
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

$accion = $_GET['accion'] ?? '';

switch ($accion) {
    case 'registrar':
        registrarNotificacion();
        break;
    case 'obtener_catalogo':
        obtenerCatalogo();
        break;
    default:
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'Acción no válida',
            'code' => 'INVALID_ACTION'
        ], 400);
}

function registrarNotificacion() {
    session_start();
    if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No autorizado'], 401);
    }
    
    $conexion = obtenerConexion();
    
    $destinatarios_json = $_POST['destinatarios'] ?? '[]';
    $actividades_json = $_POST['actividades'] ?? '[]';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? date('H:i');
    $observaciones = $_POST['observaciones'] ?? '';
    $tipo_notificador = $_POST['tipo_notificador'] ?? 'cuenta';
    $temp_nombre = $_POST['temp_nombre'] ?? 'Temporal';
    
    $destinatarios = json_decode($destinatarios_json, true);
    $actividades = json_decode($actividades_json, true);
    
    if (empty($destinatarios)) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No hay destinatarios seleccionados'], 400);
    }
    
    if (empty($actividades)) {
        enviarRespuestaJSON(['success' => false, 'message' => 'No hay actividades seleccionadas'], 400);
    }
    
    // Formatar ID_STAR (Quién notifica)
    $usuario_id_sesion = $_SESSION['usuario_id'] ?? 0;
    $usuario_cargo_sesion = $_SESSION['usuario_cargo'] ?? '';
    
    if ($tipo_notificador === 'cuenta') {
        $id_star = $usuario_cargo_sesion . '_' . $usuario_id_sesion;
    } else {
        $id_star = 'temporal_' . preg_replace('/[^a-zA-Z0-9]/', '_', $temp_nombre);
    }
    
    // Cargar catálogo de deméritos
    $demeritos_por_id = [];
    $result = $conexion->query("SELECT * FROM demeritos");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $demeritos_por_id[$row['id']] = $row;
        }
    }
    
    // Insertar actividades
    $insertados = 0;
    $errores = [];
    
    foreach ($destinatarios as $destinatario) {
        $id_dest = $destinatario['id'];
        
        // Evitar notificarse a uno mismo
        if ($tipo_notificador === 'cuenta' && $id_dest == $usuario_id_sesion) {
            continue;
        }
        
        $id_end = 'estudiante_' . $id_dest;
        $grado_destinatario = $destinatario['grado'] ?? '10mo';
        
        foreach ($actividades as $actividad) {
            $tipo = $actividad['tipo'];
            $categoria = $actividad['categoria'];
            $falta_causa = $actividad['nombre'];
            
            // Calcular cantidad según grado
            if ($tipo === 'demerito' && isset($demeritos_por_id[$actividad['actividad_id']])) {
                $demerito_data = $demeritos_por_id[$actividad['actividad_id']];
                
                if ($grado_destinatario === '10mo') {
                    $cantidad = (int)($demerito_data['demeritos_10mo'] ?? $actividad['cantidad']);
                } else {
                    $cantidad = (int)($demerito_data['demeritos_11_12'] ?? $demerito_data['demeritos_10mo'] ?? $actividad['cantidad']);
                }
            } else {
                $cantidad = (int)$actividad['cantidad'];
            }
            
            $sql = "INSERT INTO actividad (id_star, id_end, tipo, categoria, falta_causa, cantidad, fecha, hora, observaciones, leido)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)";
            
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("sssssisss",
                    $id_star,
                    $id_end,
                    $tipo,
                    $categoria,
                    $falta_causa,
                    $cantidad,
                    $fecha,
                    $hora,
                    $observaciones
                );
                
                if ($stmt->execute()) {
                    $insertados++;
                } else {
                    $errores[] = "Error: " . $stmt->error;
                }
                $stmt->close();
            }
        }
    }
    
    if ($insertados > 0) {
        enviarRespuestaJSON([
            'success' => true,
            'message' => "Se registraron $insertados notificaciones",
            'data' => ['insertados' => $insertados]
        ]);
    } else {
        enviarRespuestaJSON([
            'success' => false,
            'message' => 'No se insertó ninguna notificación. ' . implode(', ', $errores)
        ], 500);
    }
}

function obtenerCatalogo() {
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
?>
