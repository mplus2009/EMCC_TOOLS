<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../db.php';
require_once 'procesar.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_cargo = $_SESSION['usuario_cargo'] ?? '';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';

// Obtener datos del estudiante si aplica
$grado_estudiante = '';
$peloton_estudiante = 0;

if ($usuario_cargo === 'estudiante') {
    $sql = "SELECT grado, peloton FROM estudiante WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $grado_estudiante = $row['grado'] ?? '10mo';
            $peloton_estudiante = (int)($row['peloton'] ?? 1);
        }
        $stmt->close();
    }
}

// Obtener configuración del horario
$config = obtenerConfiguracionHorario($conexion);

// Obtener grados disponibles (desde la tabla pelotones o estudiante)
$grados_disponibles = [];
$check_pelotones = $conexion->query("SHOW TABLES LIKE 'pelotones'");
if ($check_pelotones && $check_pelotones->num_rows > 0) {
    $result = $conexion->query("SELECT DISTINCT grado FROM pelotones WHERE activo = 1 ORDER BY grado");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $grados_disponibles[] = $row['grado'];
        }
    }
}
if (empty($grados_disponibles)) {
    $grados_disponibles = ['10mo', '11no', '12mo'];
}

// Obtener pelotones disponibles por grado (dinámico)
$pelotones_por_grado = [];
foreach ($grados_disponibles as $grado) {
    $pelotones_por_grado[$grado] = [];
    
    if ($check_pelotones && $check_pelotones->num_rows > 0) {
        $sql = "SELECT numero_peloton FROM pelotones WHERE grado = ? AND activo = 1 ORDER BY numero_peloton";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $grado);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $pelotones_por_grado[$grado][] = $row['numero_peloton'];
            }
            $stmt->close();
        }
    }
    
    // Si no hay pelotones en la tabla, obtenerlos de estudiante
    if (empty($pelotones_por_grado[$grado])) {
        $sql = "SELECT DISTINCT peloton FROM estudiante WHERE grado = ? ORDER BY peloton";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("s", $grado);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                if (!empty($row['peloton'])) {
                    $pelotones_por_grado[$grado][] = (int)$row['peloton'];
                }
            }
            $stmt->close();
        }
    }
    
    // Si aún no hay, poner valores por defecto
    if (empty($pelotones_por_grado[$grado])) {
        $pelotones_por_grado[$grado] = [1, 2, 3, 4];
    }
}

// Determinar grado y pelotón a mostrar
$grado_sel = $_GET['grado'] ?? ($grado_estudiante ?: $grados_disponibles[0]);
$peloton_sel = (int)($_GET['peloton'] ?? ($peloton_estudiante ?: $pelotones_por_grado[$grado_sel][0]));

// Obtener horario de la semana
$horario_semana = [];
$asignatura_actual = null;

if (!empty($grado_sel) && $peloton_sel > 0) {
    $horario_semana = obtenerHorarioSemana($conexion, $grado_sel, $peloton_sel);
    if ($usuario_cargo === 'estudiante') {
        $asignatura_actual = obtenerAsignaturaActual($conexion, $grado_sel, $peloton_sel, $config);
    }
}

$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$hora_actual = date('H:i');
$dia_actual = date('N');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Horario - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../iconos_vectoriales.css">
    <link rel="stylesheet" href="estilos.css">
    <style>
        
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
        
        
    </style>
</head>
<body>
    <header class="header">
        <a href="../../index.php" class="back-btn"><span class="icon-arrow-left"></span></a>
        <h1 class="header-title"><span class="icon-calendar"></span> Horario</h1>
    </header>

    <main class="main-content">
        <?php if ($usuario_cargo === 'estudiante'): ?>
        <!-- Info del estudiante -->
        <div class="info-card">
            <div class="info-row">
                <span class="info-label"><span class="icon-graduation"></span> Grado:</span>
                <span class="info-value"><?php echo htmlspecialchars($grado_estudiante); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label"><span class="icon-users"></span> Pelotón:</span>
                <span class="info-value"><?php echo $peloton_estudiante; ?></span>
            </div>
        </div>

        <!-- Asignatura actual -->
        <?php if ($asignatura_actual): ?>
        <div class="asignatura-actual">
            <div class="actual-label"><span class="icon-clock"></span> AHORA</div>
            <div class="actual-asignatura"><?php echo htmlspecialchars($asignatura_actual['asignatura']); ?></div>
            <div class="actual-horario"><?php echo $asignatura_actual['hora_inicio'] . ' - ' . $asignatura_actual['hora_fin']; ?></div>
            <?php if ($asignatura_actual['tipo_evento'] !== 'asignatura'): ?>
            <div class="actual-tipo"><span class="icon-info"></span> <?php echo strtoupper(str_replace('_', ' ', $asignatura_actual['tipo_evento'])); ?></div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>

        <?php if ($usuario_cargo !== 'estudiante'): ?>
        <!-- Selector dinámico para otros cargos -->
        <div class="selector-card">
            <h2 class="section-title"><span class="icon-search"></span> Seleccionar Horario</h2>
            <form method="GET" class="selector-form">
                <div class="selector-group">
                    <label><span class="icon-graduation"></span> Grado</label>
                    <select name="grado" id="selectGrado" class="selector-select">
                        <?php foreach ($grados_disponibles as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo $grado_sel === $g ? 'selected' : ''; ?>><?php echo $g; ?> Grado</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="selector-group">
                    <label><span class="icon-users"></span> Pelotón</label>
                    <select name="peloton" id="selectPeloton" class="selector-select">
                        <?php foreach ($pelotones_por_grado[$grado_sel] as $p): ?>
                        <option value="<?php echo $p; ?>" <?php echo $peloton_sel === $p ? 'selected' : ''; ?>>Pelotón <?php echo $p; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="selector-btn"><span class="icon-eye"></span> Ver Horario</button>
            </form>
        </div>
        <?php endif; ?>

        <!-- Horario Semanal -->
        <?php if (!empty($horario_semana)): ?>
        <div class="horario-container">
            <?php foreach ($dias_semana as $idx => $dia): ?>
                <?php $num_dia = $idx + 1; ?>
                <?php if (isset($horario_semana[$num_dia]) && !empty($horario_semana[$num_dia])): ?>
                <div class="dia-card <?php echo ($num_dia == $dia_actual && $usuario_cargo === 'estudiante') ? 'dia-actual' : ''; ?>">
                    <h3 class="dia-titulo">
                        <span class="icon-calendar"></span> <?php echo $dia; ?>
                        <?php if ($num_dia == $dia_actual && $usuario_cargo === 'estudiante'): ?>
                        <span class="hoy-badge">HOY</span>
                        <?php endif; ?>
                    </h3>
                    <div class="turnos-list">
                        <?php 
                        $turno_anterior = 0;
                        foreach ($horario_semana[$num_dia] as $turno): 
                            // Verificar si hay merienda
                            if ($config['merienda_despues_turno'] > 0 && 
                                $turno_anterior == $config['merienda_despues_turno'] && 
                                $turno['turno_inicio'] > $config['merienda_despues_turno']) {
                                $hora_merienda_inicio = $turno['hora_inicio_anterior'] ?? '';
                                $hora_merienda_fin = $turno['hora_inicio'];
                                if ($hora_merienda_inicio && $hora_merienda_fin) {
                        ?>
                        <div class="merienda-item">
                            <div class="merienda-icon"><span class="icon-cafe"></span></div>
                            <div class="merienda-info">
                                <span class="merienda-label">MERIENDA</span>
                                <span class="merienda-hora"><?php echo $hora_merienda_inicio . ' - ' . $hora_merienda_fin; ?></span>
                            </div>
                        </div>
                        <?php 
                                }
                            }
                            
                            $es_actual = ($usuario_cargo === 'estudiante' && 
                                        $num_dia == $dia_actual && 
                                        $hora_actual >= $turno['hora_inicio'] && 
                                        $hora_actual <= $turno['hora_fin']);
                            
                            $turnos_texto = $turno['turnos_duracion'] > 1 ? 
                                "Turnos {$turno['turno_inicio']}-" . ($turno['turno_inicio'] + $turno['turnos_duracion'] - 1) : 
                                "Turno {$turno['turno_inicio']}";
                        ?>
                        <div class="turno-item <?php echo $es_actual ? 'turno-actual' : ''; ?> <?php echo $turno['tipo_evento'] !== 'asignatura' ? 'turno-especial' : ''; ?>">
                            <div class="turno-header">
                                <span class="turno-numero"><?php echo $turnos_texto; ?></span>
                                <span class="turno-hora"><span class="icon-clock"></span> <?php echo $turno['hora_inicio'] . ' - ' . $turno['hora_fin']; ?></span>
                            </div>
                            <div class="turno-asignatura">
                                <?php echo htmlspecialchars($turno['asignatura']); ?>
                                <?php if ($turno['tipo_evento'] !== 'asignatura'): ?>
                                <span class="turno-tipo"><span class="icon-info"></span> <?php echo strtoupper(str_replace('_', ' ', $turno['tipo_evento'])); ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if ($es_actual): ?>
                            <div class="turno-actual-indicador"><span class="icon-clock"></span> EN CURSO</div>
                            <?php endif; ?>
                        </div>
                        <?php 
                            $turno_anterior = $turno['turno_inicio'] + $turno['turnos_duracion'] - 1;
                        endforeach; 
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
        <?php elseif ($usuario_cargo === 'estudiante'): ?>
        <div class="empty-state">
            <span class="icon-calendar"></span>
            <p>No hay horario configurado para tu grado y pelotón</p>
        </div>
        <?php endif; ?>

        <!-- Leyenda -->
        <div class="leyenda-card">
            <h3 class="leyenda-titulo"><span class="icon-info"></span> Información</h3>
            <div class="leyenda-item">
                <span class="leyenda-color" style="background: #10b981;"></span>
                <span>Turno actual</span>
            </div>
            <div class="leyenda-item">
                <span class="leyenda-color" style="background: #fbbf24;"></span>
                <span>TCP / Prueba Final</span>
            </div>
            <div class="leyenda-item">
                <span><span class="icon-cafe"></span> Merienda (<?php echo $config['duracion_merienda'] ?? 15; ?> min)</span>
            </div>
            <div class="leyenda-item">
                <span><span class="icon-clock"></span> Descanso entre turnos (<?php echo $config['descanso_entre_turnos'] ?? 5; ?> min)</span>
            </div>
            <div class="leyenda-item">
                <span><span class="icon-calendar"></span> Viernes: turnos de <?php echo $config['duracion_turno_viernes'] ?? 40; ?> min</span>
            </div>
            <?php if ($usuario_cargo === 'directiva'): ?>
            <div class="leyenda-item" style="margin-top: 15px;">
                <a href="config.php" style="color: #1e3c72; text-decoration: none; font-weight: 600; display: flex; align-items: center; gap: 6px;">
                    <span class="icon-settings"></span> Configurar Horario
                </a>
            </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
        // Datos de pelotones por grado (dinámico desde PHP)
        const pelotonesPorGrado = <?php echo json_encode($pelotones_por_grado); ?>;
        const gradoActual = '<?php echo $grado_sel; ?>';
        
        // Selector dinámico de pelotones
        const selectGrado = document.getElementById('selectGrado');
        const selectPeloton = document.getElementById('selectPeloton');
        
        if (selectGrado && selectPeloton) {
            selectGrado.addEventListener('change', function() {
                const grado = this.value;
                const pelotones = pelotonesPorGrado[grado] || [1, 2, 3, 4];
                
                selectPeloton.innerHTML = '';
                pelotones.forEach(p => {
                    const option = document.createElement('option');
                    option.value = p;
                    option.textContent = 'Pelotón ' + p;
                    selectPeloton.appendChild(option);
                });
            });
        }
        
        // Verificar actualizaciones periódicamente
        let ultimaActualizacion = '<?php echo $config['ultima_actualizacion'] ?? ''; ?>';
        
        async function verificarActualizaciones() {
            try {
                const response = await fetch('ajax_verificar.php');
                const data = await response.json();
                if (data.actualizacion && data.actualizacion !== ultimaActualizacion) {
                    location.reload();
                }
            } catch (e) {
                console.error('Error verificando:', e);
            }
        }
        
        <?php if ($usuario_cargo === 'estudiante'): ?>
        setInterval(verificarActualizaciones, 30000);
        <?php endif; ?>
    </script>
</body>
</html>