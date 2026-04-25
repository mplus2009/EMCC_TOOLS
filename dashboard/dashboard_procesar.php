<?php
// ============================================
// DASHBOARD_PROCESAR.PHP - SOLO FORMATO NUEVO (cargo_id)
// ============================================

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_cargo = $_SESSION['usuario_cargo'] ?? 'estudiante';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';
$usuario_apellidos = $_SESSION['usuario_apellidos'] ?? '';
$usuario_ci = $_SESSION['usuario_ci'] ?? '';

// Formatear ID para búsquedas en actividad
$id_formateado = $usuario_cargo . '_' . $usuario_id;

// Si no hay datos en sesión, obtenerlos de la base de datos
if ((empty($usuario_nombre) || empty($usuario_apellidos)) && $usuario_id > 0) {
    $tabla = $usuario_cargo;
    $sql = "SELECT nombre, apellidos, CI FROM $tabla WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $usuario_nombre = $row['nombre'];
            $usuario_apellidos = $row['apellidos'];
            $usuario_ci = $row['CI'] ?? '';
            $_SESSION['usuario_nombre'] = $usuario_nombre;
            $_SESSION['usuario_apellidos'] = $usuario_apellidos;
            $_SESSION['usuario_ci'] = $usuario_ci;
        }
        $stmt->close();
    }
}

// Valores por defecto
if (empty($usuario_nombre)) $usuario_nombre = 'Usuario';
if (empty($usuario_apellidos)) $usuario_apellidos = '';

// Obtener ocupación del usuario
$ocupacion_usuario = '';
if ($usuario_id > 0) {
    $check = $conexion->query("SHOW COLUMNS FROM $usuario_cargo LIKE 'ocupacion'");
    if ($check && $check->num_rows > 0) {
        $sql = "SELECT ocupacion FROM $usuario_cargo WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $ocupacion_usuario = $row['ocupacion'] ?? '';
            }
            $stmt->close();
        }
    }
}

// Verificar si es secretaria
$es_secretaria = ($usuario_cargo === 'profesor' && $ocupacion_usuario === 'secretaria');

// Obtener contraseña para QR
$usuario_password = '';
if ($usuario_id > 0) {
    $sql = "SELECT password FROM $usuario_cargo WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($row = $resultado->fetch_assoc()) {
            $usuario_password = $row['password'];
        }
        $stmt->close();
    }
}

// ============================================
// CALCULAR INICIO DE SEMANA (MIÉRCOLES)
// ============================================
$hoy = date('Y-m-d');
$dia_semana = date('N', strtotime($hoy)); // 1=Lunes, 7=Domingo

// Calcular el miércoles de esta semana
if ($dia_semana >= 3) {
    // Si hoy es miércoles o después, el inicio es este miércoles
    $dias_resta = $dia_semana - 3;
    $inicio_semana_actual = date('Y-m-d', strtotime("-{$dias_resta} days"));
} else {
    // Si hoy es lunes o martes, el inicio es el miércoles anterior
    $dias_resta = $dia_semana + 4; // Lunes: 1+4=5, Martes: 2+4=6
    $inicio_semana_actual = date('Y-m-d', strtotime("-{$dias_resta} days"));
}

// ============================================
// CONTADORES Y ACTIVIDADES (SOLO ESTUDIANTES)
// ============================================
$meritos_count = 0;
$demeritos_count = 0;
$balance = 0;
$meritos_semana = 0;
$demeritos_semana = 0;
$balance_semana = 0;
$nuevas_actividades = 0;
$semana_actual = [];
$semanas_anteriores = [];
$alarma_activa = false;

if ($usuario_cargo === 'estudiante' && $usuario_id > 0) {
    
    // Total méritos (TODAS LAS SEMANAS)
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad WHERE id_end = ? AND tipo = 'merito'";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($row = $resultado->fetch_assoc()) $meritos_count = $row['total'];
        $stmt->close();
    }
    
    // Total deméritos (TODAS LAS SEMANAS)
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad WHERE id_end = ? AND tipo = 'demerito'";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($row = $resultado->fetch_assoc()) $demeritos_count = $row['total'];
        $stmt->close();
    }
    
    // TOTAL MÉRITOS ESTA SEMANA (miércoles a miércoles)
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad 
            WHERE id_end = ? AND tipo = 'merito' AND fecha >= ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $id_formateado, $inicio_semana_actual);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($row = $resultado->fetch_assoc()) $meritos_semana = $row['total'];
        $stmt->close();
    }
    
    // TOTAL DEMÉRITOS ESTA SEMANA (miércoles a miércoles)
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad 
            WHERE id_end = ? AND tipo = 'demerito' AND fecha >= ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ss", $id_formateado, $inicio_semana_actual);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($row = $resultado->fetch_assoc()) $demeritos_semana = $row['total'];
        $stmt->close();
    }
    
    $balance = $meritos_count - $demeritos_count;
    $balance_semana = $meritos_semana - $demeritos_semana;
    
    // Contar notificaciones NUEVAS (NO LEÍDAS)
    $sql = "SELECT COUNT(*) as nuevas FROM actividad WHERE id_end = ? AND leido = 0";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $resultado = $stmt->get_result();
        if ($row = $resultado->fetch_assoc()) $nuevas_actividades = $row['nuevas'];
        $stmt->close();
    }
    
    // Sistema de alarma
    $limites = ['10mo' => 15, '11no' => 11, '12mo' => 10];
    $config_file = __DIR__ . '/funciones/horario/alarma_config.php';
    if (file_exists($config_file)) {
        $config = include $config_file;
        if (is_array($config)) $limites = array_merge($limites, $config);
    }
    
    $grado_estudiante = '10mo';
    $sql = "SELECT grado FROM estudiante WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $grado_estudiante = $row['grado'] ?? '10mo';
        $stmt->close();
    }
    
    $limite_balance = $limites[$grado_estudiante] ?? 15;
    $balance_negativo = abs(min(0, $balance));
    
    if ($balance < 0 && $balance_negativo >= $limite_balance) {
        $alarma_activa = true;
    }
    
    // Obtener actividades por semana
    $check_table = $conexion->query("SHOW TABLES LIKE 'actividad'");
    if ($check_table && $check_table->num_rows > 0) {
        
        // Verificar si existe la columna alegacion
        $check_columna = $conexion->query("SHOW COLUMNS FROM actividad LIKE 'alegacion'");
        $columna_alegacion = ($check_columna && $check_columna->num_rows > 0) ? 'a.alegacion,' : '';
        $check_columna->close();
        
        $sql = "SELECT a.*, $columna_alegacion
                CASE 
                    WHEN a.id_star LIKE 'estudiante_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM estudiante WHERE id = SUBSTRING_INDEX(a.id_star, '_', -1))
                    WHEN a.id_star LIKE 'profesor_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM profesor WHERE id = SUBSTRING_INDEX(a.id_star, '_', -1))
                    WHEN a.id_star LIKE 'oficial_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM oficial WHERE id = SUBSTRING_INDEX(a.id_star, '_', -1))
                    WHEN a.id_star LIKE 'directiva_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM directiva WHERE id = SUBSTRING_INDEX(a.id_star, '_', -1))
                    WHEN a.id_star LIKE 'temporal_%' THEN REPLACE(SUBSTRING_INDEX(a.id_star, '_', -1), '_', ' ')
                    ELSE 'Sistema'
                END as notificador
                FROM actividad a
                WHERE a.id_end = ? 
                ORDER BY a.fecha DESC, a.hora DESC";
        
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $id_formateado);
            $stmt->execute();
            $resultado = $stmt->get_result();
            
            while ($row = $resultado->fetch_assoc()) {
                $fecha = $row['fecha'];
                
                if ($fecha >= $inicio_semana_actual) {
                    $semana_actual[] = $row;
                } else {
                    $dia_semana_fecha = date('N', strtotime($fecha));
                    $dias_hasta_miercoles_fecha = ($dia_semana_fecha >= 3) ? ($dia_semana_fecha - 3) : ($dia_semana_fecha + 4);
                    $semana_inicio = date('Y-m-d', strtotime("-{$dias_hasta_miercoles_fecha} days", strtotime($fecha)));
                    
                    if (!isset($semanas_anteriores[$semana_inicio])) $semanas_anteriores[$semana_inicio] = [];
                    $semanas_anteriores[$semana_inicio][] = $row;
                }
            }
            $stmt->close();
        }
    }
}

function formatearSemana($fecha_inicio) {
    if (empty($fecha_inicio)) return '';
    $inicio = date('d/m', strtotime($fecha_inicio));
    $fin = date('d/m', strtotime($fecha_inicio . ' +6 days'));
    return "$inicio - $fin";
}

// ============================================
// BÚSQUEDA DE ESTUDIANTES
// ============================================
$resultados_busqueda = [];
$busqueda_realizada = false;
$termino_busqueda = '';

if (in_array($usuario_cargo, ['directiva', 'oficial', 'profesor']) || $es_secretaria) {
    if (isset($_GET['buscar']) && !empty(trim($_GET['buscar']))) {
        $busqueda_realizada = true;
        $termino_busqueda = trim($_GET['buscar']);
        $query = "%" . $termino_busqueda . "%";
        
        $sql = "SELECT e.id, e.nombre, e.apellidos, e.CI as ci, e.grado, e.peloton,
                COALESCE((SELECT SUM(cantidad) FROM actividad WHERE id_end = CONCAT('estudiante_', e.id) AND tipo = 'merito'), 0) as meritos,
                COALESCE((SELECT SUM(cantidad) FROM actividad WHERE id_end = CONCAT('estudiante_', e.id) AND tipo = 'demerito'), 0) as demeritos
                FROM estudiante e
                WHERE e.nombre LIKE ? OR e.apellidos LIKE ? OR e.CI LIKE ?
                ORDER BY e.apellidos, e.nombre
                LIMIT 30";
        
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $query, $query, $query);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $row['balance'] = $row['meritos'] - $row['demeritos'];
                $resultados_busqueda[] = $row;
            }
            $stmt->close();
        }
    }
}

// ============================================
// VERIFICAR SI EL USUARIO YA VIO EL TUTORIAL
// ============================================
$tutorial_visto = false;

if ($usuario_id > 0) {
    $check_tabla = $conexion->query("SHOW TABLES LIKE 'tutorial_visto'");
    
    if ($check_tabla && $check_tabla->num_rows > 0) {
        $sql = "SELECT visto FROM tutorial_visto WHERE usuario_id = ? AND usuario_cargo = ?";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("is", $usuario_id, $usuario_cargo);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $tutorial_visto = (bool)$row['visto'];
            }
            $stmt->close();
        }
    } else {
        $sql_create = "CREATE TABLE IF NOT EXISTS tutorial_visto (
            id INT AUTO_INCREMENT PRIMARY KEY,
            usuario_id INT NOT NULL,
            usuario_cargo VARCHAR(20) NOT NULL,
            visto BOOLEAN DEFAULT FALSE,
            fecha_visto TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_usuario (usuario_id, usuario_cargo)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_spanish_ci";
        $conexion->query($sql_create);
        $tutorial_visto = false;
    }
}

$mostrar_tutorial = !$tutorial_visto;

// Datos QR
$qr_data = [
    'id' => $usuario_id,
    'nombre' => $usuario_nombre,
    'apellidos' => $usuario_apellidos,
    'ci' => $usuario_ci,
    'cargo' => $usuario_cargo
];
$qr_json = json_encode($qr_data);
$qr_encriptado = base64_encode($qr_json);
?>