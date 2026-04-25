<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit();
}

require_once '../db.php';

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Error al procesar la notificación'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $destinatarios_json = $_POST['destinatarios'] ?? '[]';
    $actividades_json = $_POST['actividades'] ?? '[]';
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $hora = $_POST['hora'] ?? date('H:i');
    $observaciones = $_POST['observaciones'] ?? '';
    $id_star_input = $_POST['id_star'] ?? 0;
    $tipo_notificador = $_POST['tipo_notificador'] ?? 'cuenta';
    
    $destinatarios = json_decode($destinatarios_json, true);
    $actividades = json_decode($actividades_json, true);
    
    if (empty($destinatarios)) {
        $response['message'] = 'No hay destinatarios seleccionados';
        echo json_encode($response);
        exit();
    }
    
    if (empty($actividades)) {
        $response['message'] = 'No hay actividades seleccionadas';
        echo json_encode($response);
        exit();
    }
    
    // ============================================
    // FORMATAR ID_STAR (Quién notifica)
    // ============================================
    $usuario_id_sesion = $_SESSION['usuario_id'] ?? 0;
    $usuario_cargo_sesion = $_SESSION['usuario_cargo'] ?? '';
    
    if ($tipo_notificador === 'cuenta') {
        $id_star = $usuario_cargo_sesion . '_' . $usuario_id_sesion;
    } else {
        $temp_nombre = $_POST['temp_nombre'] ?? 'Temporal';
        $id_star = 'temporal_' . preg_replace('/[^a-zA-Z0-9]/', '_', $temp_nombre);
    }
    
    // ============================================
    // CARGAR CATÁLOGO DE DEMÉRITOS
    // ============================================
    $demeritos_por_id = [];
    $result = $conexion->query("SELECT * FROM demeritos");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $demeritos_por_id[$row['id']] = $row;
        }
    }
    
    // ============================================
    // INSERTAR ACTIVIDADES
    // ============================================
    $insertados = 0;
    $errores = [];
    
    foreach ($destinatarios as $destinatario) {
        $id_dest = $destinatario['id'];
        
        // EVITAR NOTIFICARSE A UNO MISMO
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
        $response = [
            'success' => true,
            'message' => "Se registraron $insertados notificaciones",
            'insertados' => $insertados
        ];
    } else {
        $response['message'] = 'No se insertó ninguna notificación. ' . implode(', ', $errores);
    }
}

echo json_encode($response);
?>