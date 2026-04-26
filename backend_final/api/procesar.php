<?php
// ============================================
// FUNCIONES DE PROCESAMIENTO DE HORARIO
// VERSIÓN CON ALMUERZO Y TURNO TARDE
// ============================================

/**
 * Obtener la configuración actual del horario
 */
function obtenerConfiguracionHorario($conexion) {
    $config = [
        'hora_inicio' => '08:00',
        'duracion_turno_lunes_jueves' => 45,
        'duracion_turno_viernes' => 40,
        'duracion_turno_sabado' => 45,
        'descanso_entre_turnos' => 5,
        'merienda_despues_turno' => 3,
        'duracion_merienda' => 15,
        'almuerzo_despues_turno' => 6,
        'duracion_almuerzo' => 85,
        'viernes_con_merienda' => false,
        'sabado_con_clases' => false,
        'ultima_actualizacion' => ''
    ];
    
    $check = $conexion->query("SHOW TABLES LIKE 'horario_config'");
    if ($check && $check->num_rows > 0) {
        $result = $conexion->query("SELECT * FROM horario_config LIMIT 1");
        if ($result && $row = $result->fetch_assoc()) {
            $config = array_merge($config, $row);
        }
    }
    
    return $config;
}

/**
 * Obtener el horario completo de una semana para un grado y pelotón
 */
function obtenerHorarioSemana($conexion, $grado, $peloton) {
    $horario = [];
    
    $check = $conexion->query("SHOW TABLES LIKE 'pelotones'");
    if (!$check || $check->num_rows == 0) {
        return $horario;
    }
    
    $sql = "SELECT id FROM pelotones WHERE grado = ? AND numero_peloton = ? LIMIT 1";
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return $horario;
    
    $stmt->bind_param("si", $grado, $peloton);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    
    if (!$row) return $horario;
    $peloton_id = $row['id'];
    
    $check = $conexion->query("SHOW TABLES LIKE 'horario_asignaturas'");
    if (!$check || $check->num_rows == 0) {
        return $horario;
    }
    
    $check_asig = $conexion->query("SHOW TABLES LIKE 'asignaturas'");
    $tiene_asignaturas = ($check_asig && $check_asig->num_rows > 0);
    
    if ($tiene_asignaturas) {
        $sql = "SELECT h.*, a.nombre as asignatura, a.abreviatura 
                FROM horario_asignaturas h
                JOIN asignaturas a ON h.asignatura_id = a.id
                WHERE h.peloton_id = ?
                ORDER BY h.dia_semana, h.turno_inicio";
    } else {
        $sql = "SELECT h.*, 'Sin asignatura' as asignatura, '?' as abreviatura 
                FROM horario_asignaturas h
                WHERE h.peloton_id = ?
                ORDER BY h.dia_semana, h.turno_inicio";
    }
    
    $stmt = $conexion->prepare($sql);
    if (!$stmt) return $horario;
    
    $stmt->bind_param("i", $peloton_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $config = obtenerConfiguracionHorario($conexion);
    
    while ($row = $result->fetch_assoc()) {
        $dia = $row['dia_semana'];
        if (!isset($horario[$dia])) {
            $horario[$dia] = [];
        }
        
        $horas = calcularHoraTurno($row['turno_inicio'], $dia, $config, $row['turnos_duracion']);
        $row['hora_inicio'] = $horas['inicio'];
        $row['hora_fin'] = $horas['fin'];
        
        if (count($horario[$dia]) > 0) {
            $ultimo = end($horario[$dia]);
            $row['hora_inicio_anterior'] = $ultimo['hora_fin'];
        }
        
        // Determinar si es turno de mañana o tarde
        $row['es_tarde'] = ($row['turno_inicio'] > $config['almuerzo_despues_turno']);
        
        $horario[$dia][] = $row;
    }
    $stmt->close();
    
    return $horario;
}

/**
 * Calcular la hora de inicio y fin de un turno específico
 */
function calcularHoraTurno($turno, $dia, $config, $turnos_duracion = 1) {
    $hora_base = strtotime($config['hora_inicio']);
    
    // Determinar duración según el día
    if ($dia == 5) {
        $duracion = $config['duracion_turno_viernes'];
        $con_merienda = $config['viernes_con_merienda'];
    } elseif ($dia == 6) {
        $duracion = $config['duracion_turno_sabado'];
        $con_merienda = false;
    } else {
        $duracion = $config['duracion_turno_lunes_jueves'];
        $con_merienda = true;
    }
    
    $minutos_totales = 0;
    
    // Calcular minutos acumulados hasta el turno actual
    for ($t = 1; $t < $turno; $t++) {
        $minutos_totales += $duracion;
        
        // Agregar descanso entre turnos
        if ($t < $turno) {
            $minutos_totales += $config['descanso_entre_turnos'];
        }
        
        // Agregar merienda si corresponde
        if ($con_merienda && $config['merienda_despues_turno'] > 0 && 
            $t == $config['merienda_despues_turno']) {
            $minutos_totales += $config['duracion_merienda'];
        }
        
        // Agregar almuerzo si corresponde
        if ($config['almuerzo_despues_turno'] > 0 && 
            $t == $config['almuerzo_despues_turno']) {
            $minutos_totales += $config['duracion_almuerzo'];
        }
    }
    
    $inicio = $hora_base + ($minutos_totales * 60);
    $fin = $inicio + ($duracion * $turnos_duracion * 60);
    
    // Si ocupa más de un turno, agregar descansos entre ellos
    if ($turnos_duracion > 1) {
        for ($i = 1; $i < $turnos_duracion; $i++) {
            $fin += ($config['descanso_entre_turnos'] * 60);
        }
    }
    
    return [
        'inicio' => date('H:i', $inicio),
        'fin' => date('H:i', $fin)
    ];
}

/**
 * Obtener la asignatura actual según la hora y día
 */
function obtenerAsignaturaActual($conexion, $grado, $peloton, $config) {
    $dia_actual = date('N');
    $hora_actual = date('H:i');
    
    if ($dia_actual == 7) return null;
    if ($dia_actual == 6 && !$config['sabado_con_clases']) return null;
    
    $horario = obtenerHorarioSemana($conexion, $grado, $peloton);
    
    if (isset($horario[$dia_actual])) {
        foreach ($horario[$dia_actual] as $turno) {
            if ($hora_actual >= $turno['hora_inicio'] && $hora_actual <= $turno['hora_fin']) {
                return $turno;
            }
        }
        
        // Verificar si está en merienda
        $merienda = verificarMerienda($dia_actual, $hora_actual, $config);
        if ($merienda) return $merienda;
        
        // Verificar si está en almuerzo
        $almuerzo = verificarAlmuerzo($dia_actual, $hora_actual, $config);
        if ($almuerzo) return $almuerzo;
        
        // Verificar si está en descanso
        $descanso = verificarDescanso($horario[$dia_actual], $hora_actual, $config);
        if ($descanso) return $descanso;
    }
    
    return null;
}

/**
 * Verificar si la hora actual está en merienda
 */
function verificarMerienda($dia, $hora_actual, $config) {
    if ($dia == 5 && !$config['viernes_con_merienda']) return null;
    if ($config['merienda_despues_turno'] <= 0) return null;
    
    $duracion = ($dia == 5) ? $config['duracion_turno_viernes'] : $config['duracion_turno_lunes_jueves'];
    $minutos_totales = 0;
    
    for ($t = 1; $t <= $config['merienda_despues_turno']; $t++) {
        $minutos_totales += $duracion;
        if ($t < $config['merienda_despues_turno']) {
            $minutos_totales += $config['descanso_entre_turnos'];
        }
    }
    
    $hora_base = strtotime($config['hora_inicio']);
    $inicio = $hora_base + ($minutos_totales * 60);
    $fin = $inicio + ($config['duracion_merienda'] * 60);
    
    $hora_inicio = date('H:i', $inicio);
    $hora_fin = date('H:i', $fin);
    
    if ($hora_actual >= $hora_inicio && $hora_actual <= $hora_fin) {
        return [
            'asignatura' => '🍎 MERIENDA',
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
            'tipo_evento' => 'merienda'
        ];
    }
    
    return null;
}

/**
 * Verificar si la hora actual está en almuerzo
 */
function verificarAlmuerzo($dia, $hora_actual, $config) {
    if ($config['almuerzo_despues_turno'] <= 0) return null;
    
    $duracion = ($dia == 5) ? $config['duracion_turno_viernes'] : $config['duracion_turno_lunes_jueves'];
    $minutos_totales = 0;
    
    for ($t = 1; $t <= $config['almuerzo_despues_turno']; $t++) {
        $minutos_totales += $duracion;
        if ($t < $config['almuerzo_despues_turno']) {
            $minutos_totales += $config['descanso_entre_turnos'];
        }
        if ($t == $config['merienda_despues_turno']) {
            $minutos_totales += $config['duracion_merienda'];
        }
    }
    
    $hora_base = strtotime($config['hora_inicio']);
    $inicio = $hora_base + ($minutos_totales * 60);
    $fin = $inicio + ($config['duracion_almuerzo'] * 60);
    
    $hora_inicio = date('H:i', $inicio);
    $hora_fin = date('H:i', $fin);
    
    if ($hora_actual >= $hora_inicio && $hora_actual <= $hora_fin) {
        return [
            'asignatura' => '🍽️ ALMUERZO',
            'hora_inicio' => $hora_inicio,
            'hora_fin' => $hora_fin,
            'tipo_evento' => 'almuerzo'
        ];
    }
    
    return null;
}

/**
 * Verificar si la hora actual está en un período de descanso
 */
function verificarDescanso($turnos_dia, $hora_actual, $config) {
    for ($i = 0; $i < count($turnos_dia) - 1; $i++) {
        $turno_actual = $turnos_dia[$i];
        $turno_siguiente = $turnos_dia[$i + 1];
        
        $fin_actual = $turno_actual['hora_fin'];
        $inicio_siguiente = $turno_siguiente['hora_inicio'];
        
        if ($hora_actual >= $fin_actual && $hora_actual <= $inicio_siguiente) {
            $turno_num = $turno_actual['turno_inicio'] + $turno_actual['turnos_duracion'] - 1;
            $es_merienda = ($turno_num == $config['merienda_despues_turno']);
            $es_almuerzo = ($turno_num == $config['almuerzo_despues_turno']);
            
            if (!$es_merienda && !$es_almuerzo) {
                return [
                    'asignatura' => '⏱️ DESCANSO',
                    'hora_inicio' => $fin_actual,
                    'hora_fin' => $inicio_siguiente,
                    'tipo_evento' => 'descanso',
                    'proximo_turno' => $turno_siguiente['asignatura']
                ];
            }
        }
    }
    
    return null;
}

function obtenerGrados($conexion) {
    return ['10mo', '11no', '12mo'];
}

function obtenerPelotonesPorGrado($conexion, $grado) {
    $pelotones = [];
    $check = $conexion->query("SHOW TABLES LIKE 'pelotones'");
    if ($check && $check->num_rows > 0) {
        $sql = "SELECT DISTINCT numero_peloton FROM pelotones WHERE grado = ? AND activo = 1 ORDER BY numero_peloton";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $grado);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $pelotones[] = $row['numero_peloton'];
            }
            $stmt->close();
        }
    }
    if (empty($pelotones)) $pelotones = [1, 2, 3, 4];
    return $pelotones;
}

function hayClases($dia, $config) {
    if ($dia == 6) return $config['sabado_con_clases'];
    if ($dia == 7) return false;
    return true;
}

function nombreDia($num_dia) {
    $dias = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];
    return $dias[$num_dia] ?? '';
}

function obtenerAsignaturas($conexion) {
    $asignaturas = [];
    $check = $conexion->query("SHOW TABLES LIKE 'asignaturas'");
    if ($check && $check->num_rows > 0) {
        $result = $conexion->query("SELECT id, nombre, abreviatura FROM asignaturas ORDER BY nombre");
        while ($row = $result->fetch_assoc()) {
            $asignaturas[] = $row;
        }
    }
    return $asignaturas;
}
?>