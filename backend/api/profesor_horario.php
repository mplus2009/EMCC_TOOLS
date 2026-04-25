<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_cargo = $_SESSION['usuario_cargo'] ?? '';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';

// Solo para profesores
if ($usuario_cargo !== 'profesor') {
    header('Location: ../../index.php');
    exit();
}

// Obtener la ocupación (asignatura) del profesor
$asignatura_profesor = '';
$sql = "SELECT ocupacion FROM profesor WHERE id = ?";
$stmt = $conexion->prepare($sql);
if ($stmt) {
    $stmt->bind_param("i", $usuario_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $asignatura_profesor = $row['ocupacion'] ?? '';
    }
    $stmt->close();
}

// Mapear ocupación a nombre de asignatura
$mapa_asignaturas = [
    'matematicas' => 'Matemática',
    'historia' => 'Historia de Cuba',
    'fisica' => 'Física',
    'quimica' => 'Química',
    'ingles' => 'Inglés',
    'literatura_lengua' => 'Literatura y Lengua',
    'preparacion_fisica' => 'Preparación Física',
    'cultura_politica' => 'Cultura Política',
    'preparacion_ciudadana' => 'Preparación Ciudadana',
    'panorama_cultura_cubana' => 'Panorama de la Cultura Cubana',
    'informatica' => 'Informática',
    'biblioteca' => 'Biblioteca',
    'biologia' => 'Biología',
    'geografia' => 'Geografía'
];
$nombre_asignatura = $mapa_asignaturas[$asignatura_profesor] ?? $asignatura_profesor;

// Obtener ID de la asignatura en la tabla asignaturas
$asignatura_id = 0;
$sql = "SELECT id FROM asignaturas WHERE nombre = ? OR abreviatura = ? LIMIT 1";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("ss", $nombre_asignatura, $asignatura_profesor);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $asignatura_id = $row['id'];
}
$stmt->close();

// Grados disponibles
$grados = ['10mo', '11no', '12mo'];
$grado_sel = $_GET['grado'] ?? '';

// Obtener horario del profesor
$horario_semana = [];
$turnos_hoy = [];
$dia_actual = date('N'); // 1=Lunes ... 6=Sábado
$hora_actual = date('H:i');

if ($asignatura_id > 0) {
    $sql = "SELECT h.*, a.nombre as asignatura_nombre, p.grado, p.numero_peloton
            FROM horario_asignaturas h
            JOIN asignaturas a ON h.asignatura_id = a.id
            JOIN pelotones p ON h.peloton_id = p.id
            WHERE h.asignatura_id = ? AND h.semana = 'esta'";
    
    if (!empty($grado_sel)) {
        $sql .= " AND p.grado = ?";
    }
    
    $sql .= " ORDER BY h.dia_semana, h.turno_inicio";
    
    $stmt = $conexion->prepare($sql);
    if (!empty($grado_sel)) {
        $stmt->bind_param("is", $asignatura_id, $grado_sel);
    } else {
        $stmt->bind_param("i", $asignatura_id);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $dia = $row['dia_semana'];
        if (!isset($horario_semana[$dia])) {
            $horario_semana[$dia] = [];
        }
        $horario_semana[$dia][] = $row;
        
        // Verificar si es hoy
        if ($dia == $dia_actual) {
            $turnos_hoy[] = $row;
        }
    }
    $stmt->close();
}

// Configuración para calcular horas
$config = [
    'hora_inicio' => '08:00',
    'duracion_turno_lunes_jueves' => 45,
    'duracion_turno_viernes' => 40,
    'descanso_entre_turnos' => 5,
    'merienda_despues_turno' => 3,
    'duracion_merienda' => 15
];

$result = $conexion->query("SELECT * FROM horario_config LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $config = array_merge($config, $row);
}

// Función para calcular hora de un turno
function calcularHoraTurno($turno, $dia, $config) {
    $hora_base = strtotime($config['hora_inicio']);
    $duracion = ($dia == 5) ? $config['duracion_turno_viernes'] : $config['duracion_turno_lunes_jueves'];
    
    $minutos = 0;
    for ($t = 1; $t < $turno; $t++) {
        $minutos += $duracion + $config['descanso_entre_turnos'];
        if ($t == $config['merienda_despues_turno']) {
            $minutos += $config['duracion_merienda'];
        }
    }
    
    $inicio = $hora_base + ($minutos * 60);
    $fin = $inicio + ($duracion * 60);
    
    return date('H:i', $inicio) . ' - ' . date('H:i', $fin);
}

$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Mi Horario - Profesor</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../iconos_vectoriales.css">
    <style>
    
    
    /* ============================================ */
/* ICONO DE FLECHA IZQUIERDA (IR ATRÁS)         */
/* ============================================ */

.icon-arrow-left::before {
    content: "";
    display: inline-block;
    width: 1em;
    height: 1em;
    background-color: currentColor;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'/%3E%3C/svg%3E");
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}


        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; padding-bottom: 30px; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); border: none; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.3em; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .main-content { padding: 20px 16px; max-width: 650px; margin: 0 auto; }
        
        .profesor-info { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .profesor-nombre { font-size: 1.3em; font-weight: 700; color: #1e3c72; margin-bottom: 5px; }
        .profesor-asignatura { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 6px 16px; border-radius: 25px; font-size: 0.9em; font-weight: 600; }
        
        .selector-grado { margin-bottom: 20px; }
        .selector-grado select { width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 16px; font-family: 'Poppins', sans-serif; font-size: 1em; background: white; }
        
        .hoy-card { background: linear-gradient(135deg, #10b981 0%, #059669 100%); border-radius: 20px; padding: 20px; margin-bottom: 20px; color: white; box-shadow: 0 8px 20px rgba(16,185,129,0.2); }
        .hoy-title { font-size: 1.2em; font-weight: 600; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .hoy-turnos { display: flex; flex-direction: column; gap: 12px; }
        .hoy-turno { background: rgba(255,255,255,0.15); border-radius: 16px; padding: 15px; backdrop-filter: blur(5px); }
        .hoy-turno-hora { font-size: 1.1em; font-weight: 600; margin-bottom: 5px; }
        .hoy-turno-detalle { font-size: 0.95em; opacity: 0.95; }
        .hoy-vacio { text-align: center; padding: 15px; opacity: 0.9; }
        
        .semana-title { color: #1e3c72; font-size: 1.2em; font-weight: 700; margin-bottom: 15px; display: flex; align-items: center; gap: 8px; }
        .semana-container { display: flex; flex-direction: column; gap: 15px; }
        .dia-card { background: white; border-radius: 20px; padding: 18px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .dia-card.hoy { border: 2px solid #10b981; }
        .dia-header { display: flex; align-items: center; gap: 8px; margin-bottom: 12px; }
        .dia-nombre { font-weight: 700; color: #1e3c72; font-size: 1.1em; }
        .hoy-badge { background: #10b981; color: white; padding: 2px 10px; border-radius: 20px; font-size: 0.7em; font-weight: 600; margin-left: 8px; }
        .turno-profesor { background: #f8fafc; border-radius: 14px; padding: 14px; margin-bottom: 8px; border-left: 4px solid #667eea; }
        .turno-hora { font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .turno-info { font-size: 0.9em; color: #64748b; display: flex; align-items: center; gap: 15px; flex-wrap: wrap; }
        .turno-grado { background: #e2e8f0; padding: 2px 10px; border-radius: 20px; font-size: 0.85em; }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #94a3b8; }
        .empty-state .icon-calendar { font-size: 50px; margin-bottom: 15px; color: #cbd5e1; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../../index.php" class="back-btn"><span class="icon-arrow-left"></span></a>
        <h1 class="header-title"><span class="icon-calendar"></span> Mi Horario</h1>
    </header>

    <main class="main-content">
        <!-- Info del profesor -->
        <div class="profesor-info">
            <div class="profesor-nombre"><?php echo htmlspecialchars($usuario_nombre); ?></div>
            <span class="profesor-asignatura"><span class="icon-book"></span> <?php echo htmlspecialchars($nombre_asignatura); ?></span>
        </div>
        
        <!-- Selector de grado -->
        <div class="selector-grado">
            <select id="selectGrado" onchange="cambiarGrado(this.value)">
                <option value="">📚 Todos los grados</option>
                <?php foreach ($grados as $g): ?>
                <option value="<?php echo $g; ?>" <?php echo $grado_sel === $g ? 'selected' : ''; ?>><?php echo $g; ?> Grado</option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <!-- HOY -->
        <div class="hoy-card">
            <h2 class="hoy-title"><span class="icon-clock"></span> Hoy - <?php echo $dias_semana[$dia_actual-1]; ?></h2>
            <div class="hoy-turnos">
                <?php if (empty($turnos_hoy)): ?>
                <div class="hoy-vacio"><span class="icon-check-circle"></span> No tienes clases hoy</div>
                <?php else: ?>
                    <?php foreach ($turnos_hoy as $turno): ?>
                    <div class="hoy-turno">
                        <div class="hoy-turno-hora"><?php echo calcularHoraTurno($turno['turno_inicio'], $dia_actual, $config); ?></div>
                        <div class="hoy-turno-detalle">
                            <?php echo htmlspecialchars($turno['grado']); ?>, Pelotón <?php echo $turno['numero_peloton']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- SEMANA COMPLETA -->
        <h2 class="semana-title"><span class="icon-calendar"></span> Toda la Semana</h2>
        <div class="semana-container">
            <?php if (empty($horario_semana)): ?>
            <div class="empty-state">
                <span class="icon-calendar"></span>
                <h3>No hay horario asignado</h3>
                <p>No tienes clases programadas para esta semana</p>
            </div>
            <?php else: ?>
                <?php foreach ($dias_semana as $idx => $dia): ?>
                    <?php $num_dia = $idx + 1; ?>
                    <?php if (isset($horario_semana[$num_dia]) && !empty($horario_semana[$num_dia])): ?>
                    <div class="dia-card <?php echo $num_dia == $dia_actual ? 'hoy' : ''; ?>">
                        <div class="dia-header">
                            <span class="icon-calendar"></span>
                            <span class="dia-nombre"><?php echo $dia; ?></span>
                            <?php if ($num_dia == $dia_actual): ?>
                            <span class="hoy-badge">HOY</span>
                            <?php endif; ?>
                        </div>
                        <?php foreach ($horario_semana[$num_dia] as $turno): ?>
                        <div class="turno-profesor">
                            <div class="turno-hora">
                                Turno <?php echo $turno['turno_inicio']; ?> 
                                <?php if ($turno['turnos_duracion'] > 1): ?>
                                - <?php echo $turno['turno_inicio'] + $turno['turnos_duracion'] - 1; ?>
                                <?php endif; ?>
                                <span style="font-weight: 400; margin-left: 10px; font-size: 0.9em; color: #64748b;">
                                    <?php echo calcularHoraTurno($turno['turno_inicio'], $num_dia, $config); ?>
                                </span>
                            </div>
                            <div class="turno-info">
                                <span><span class="icon-graduation"></span> <?php echo htmlspecialchars($turno['grado']); ?></span>
                                <span><span class="icon-users"></span> Pelotón <?php echo $turno['numero_peloton']; ?></span>
                                <?php if ($turno['tipo_evento'] !== 'asignatura'): ?>
                                <span class="turno-grado">📝 <?php echo strtoupper(str_replace('_', ' ', $turno['tipo_evento'])); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>

    <script>
        function cambiarGrado(grado) {
            if (grado) {
                window.location.href = '?grado=' + grado;
            } else {
                window.location.href = window.location.pathname;
            }
        }
    </script>
</body>
</html>