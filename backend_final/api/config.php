<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../../../login.php');
    exit();
}

require_once '../../../db.php';

$usuario_cargo = $_SESSION['usuario_cargo'] ?? '';

if ($usuario_cargo !== 'directiva') {
    header('Location: ../../index.php');
    exit();
}

// Obtener configuración
$config = [
    'hora_inicio' => '08:00',
    'duracion_turno_lunes_jueves' => 45,
    'duracion_turno_viernes' => 40,
    'descanso_entre_turnos' => 5,
    'merienda_despues_turno' => 3,
    'duracion_merienda' => 15,
    'viernes_con_merienda' => false,
    'sabado_con_clases' => false
];

$result = $conexion->query("SELECT * FROM horario_config LIMIT 1");
if ($result && $row = $result->fetch_assoc()) {
    $config = array_merge($config, $row);
}

// Obtener grados
$grados = ['10mo', '11no', '12mo'];

// Obtener pelotones
$pelotones_por_grado = [];
$result = $conexion->query("SELECT id, grado, numero_peloton FROM pelotones WHERE activo = 1 ORDER BY grado, numero_peloton");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $g = $row['grado'];
        if (!isset($pelotones_por_grado[$g])) {
            $pelotones_por_grado[$g] = [];
        }
        $pelotones_por_grado[$g][] = [
            'id' => $row['id'],
            'numero' => $row['numero_peloton']
        ];
    }
}

foreach ($grados as $g) {
    if (empty($pelotones_por_grado[$g])) {
        $pelotones_por_grado[$g] = [
            ['id' => 0, 'numero' => 1],
            ['id' => 0, 'numero' => 2],
            ['id' => 0, 'numero' => 3],
            ['id' => 0, 'numero' => 4]
        ];
    }
}

// Obtener asignaturas
$asignaturas = [];
$result = $conexion->query("SELECT * FROM asignaturas ORDER BY nombre");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $asignaturas[] = $row;
    }
}

// Parámetros
$grado_sel = $_GET['grado'] ?? '11no';
$peloton_sel = (int)($_GET['peloton'] ?? 1);
$semana_sel = $_GET['semana'] ?? 'esta';
$dia_sel = (int)($_GET['dia'] ?? 1);

// Obtener peloton_id
$peloton_id = 0;
foreach ($pelotones_por_grado[$grado_sel] as $p) {
    if ($p['numero'] == $peloton_sel) {
        $peloton_id = $p['id'];
        break;
    }
}

// Obtener horario
$horario_actual = [];
if ($peloton_id > 0) {
    $sql = "SELECT h.*, a.nombre as asignatura_nombre, a.abreviatura 
            FROM horario_asignaturas h
            JOIN asignaturas a ON h.asignatura_id = a.id
            WHERE h.peloton_id = ? AND h.semana = ?
            ORDER BY h.dia_semana, h.turno_inicio";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $peloton_id, $semana_sel);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $dia = $row['dia_semana'];
            if (!isset($horario_actual[$dia])) {
                $horario_actual[$dia] = [];
            }
            $horario_actual[$dia][$row['turno_inicio']] = $row;
        }
        $stmt->close();
    }
}

$dias_semana = ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
$tipos_evento = [
    'asignatura' => 'Asignatura', 
    'tcp' => 'TCP', 
    'prueba_final' => 'Prueba Final', 
    'evento_especial' => 'Evento Especial'
];

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Editar Horario - Directiva</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../iconos_vectoriales.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; padding-bottom: 30px; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); border: none; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .back-btn span { font-size: 22px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .main-content { padding: 20px 16px; max-width: 700px; margin: 0 auto; }
        
        .tabs { display: flex; gap: 5px; margin-bottom: 20px; background: white; border-radius: 16px; padding: 5px; }
        .tab { flex: 1; padding: 12px; text-align: center; border-radius: 12px; font-weight: 600; cursor: pointer; color: #64748b; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .tab.active { background: #1e3c72; color: white; }
        
        .config-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .section-title { color: #1e3c72; font-size: 1.2em; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        
        .selector-row { display: flex; gap: 10px; margin-bottom: 20px; flex-wrap: wrap; }
        .selector-group { flex: 1; min-width: 120px; }
        .selector-group label { display: block; font-size: 0.85em; color: #64748b; margin-bottom: 5px; }
        .selector-group select { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'Poppins', sans-serif; }
        
        .semana-selector { display: flex; gap: 10px; margin-bottom: 20px; }
        .semana-btn { flex: 1; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; background: white; font-weight: 600; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 6px; }
        .semana-btn.active { background: #1e3c72; border-color: #1e3c72; color: white; }
        
        .dias-tabs { display: flex; gap: 5px; margin-bottom: 20px; overflow-x: auto; padding-bottom: 5px; }
        .dia-tab { padding: 10px 15px; background: #f0f4f8; border-radius: 25px; font-size: 0.9em; font-weight: 500; cursor: pointer; white-space: nowrap; }
        .dia-tab.active { background: #1e3c72; color: white; }
        
        .turnos-container { margin-top: 20px; }
        .turno-item { background: #f8fafc; border-radius: 16px; padding: 16px; margin-bottom: 12px; border-left: 4px solid #cbd5e0; }
        .turno-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .turno-numero { font-weight: 700; color: #1e3c72; }
        .turno-hora { font-size: 0.85em; color: #64748b; }
        .turno-edit { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .turno-edit select, .turno-edit input { padding: 10px; border: 2px solid #e0e0e0; border-radius: 10px; font-family: 'Poppins', sans-serif; }
        .turno-edit select { flex: 2; min-width: 140px; }
        .turno-edit input { width: 70px; }
        .btn-save-turno { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        .btn-delete-turno { background: #ef4444; color: white; border: none; padding: 10px 16px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 5px; }
        
        .add-turno-section { margin-top: 20px; padding: 16px; background: #f0fdf4; border-radius: 16px; border: 1px dashed #10b981; }
        .add-turno-form { display: flex; gap: 10px; flex-wrap: wrap; align-items: flex-end; }
        .add-turno-form .form-group { flex: 1; min-width: 100px; }
        .add-turno-form label { font-size: 0.8em; color: #64748b; display: block; margin-bottom: 4px; }
        .add-turno-form select, .add-turno-form input { width: 100%; padding: 10px; border: 2px solid #e0e0e0; border-radius: 10px; }
        .btn-add-turno { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 5px; }
        
        .btn-guardar { width: 100%; padding: 16px; background: #1e3c72; color: white; border: none; border-radius: 16px; font-weight: 600; cursor: pointer; margin-top: 20px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .mensaje { background: #d1fae5; color: #065f46; padding: 14px; border-radius: 14px; margin-bottom: 20px; display: flex; align-items: center; gap: 8px; }
        .empty-turnos { text-align: center; padding: 30px; color: #94a3b8; }
        
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; font-size: 0.9em; color: #64748b; margin-bottom: 5px; }
        .form-group input { width: 100%; padding: 12px; border: 2px solid #e0e0e0; border-radius: 12px; }
        .form-row { display: flex; gap: 10px; }
        .form-row .form-group { flex: 1; }
        .checkbox-group { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }
        .checkbox-group input { width: 20px; height: 20px; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../../index.php" class="back-btn"><span class="icon-arrow-left"></span></a>
        <h1 class="header-title"><span class="icon-calendar"></span> Editar Horario</h1>
    </header>

    <main class="main-content">
        <?php if ($mensaje === 'ok'): ?>
        <div class="mensaje"><span class="icon-check-circle"></span> Horario guardado correctamente</div>
        <?php elseif ($mensaje === 'config_ok'): ?>
        <div class="mensaje"><span class="icon-check-circle"></span> Configuración guardada</div>
        <?php endif; ?>
        
        <div class="tabs">
            <div class="tab active" data-tab="horario"><span class="icon-calendar"></span> Horario</div>
            <div class="tab" data-tab="config"><span class="icon-settings"></span> Configuración</div>
        </div>
        
        <!-- TAB HORARIO -->
        <div id="tab-horario">
            <div class="config-card">
                <h2 class="section-title"><span class="icon-edit"></span> Editar Horario</h2>
                
                <div class="selector-row">
                    <div class="selector-group">
                        <label><span class="icon-graduation"></span> Grado</label>
                        <select id="selectGrado">
                            <?php foreach ($grados as $g): ?>
                            <option value="<?php echo $g; ?>" <?php echo $grado_sel === $g ? 'selected' : ''; ?>><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="selector-group">
                        <label><span class="icon-users"></span> Pelotón</label>
                        <select id="selectPeloton">
                            <?php foreach ($pelotones_por_grado[$grado_sel] as $p): ?>
                            <option value="<?php echo $p['numero']; ?>" <?php echo $peloton_sel == $p['numero'] ? 'selected' : ''; ?>>Pelotón <?php echo $p['numero']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="semana-selector">
                    <button class="semana-btn <?php echo $semana_sel === 'esta' ? 'active' : ''; ?>" data-semana="esta">
                        <span class="icon-calendar"></span> Esta Semana
                    </button>
                    <button class="semana-btn <?php echo $semana_sel === 'proxima' ? 'active' : ''; ?>" data-semana="proxima">
                        <span class="icon-calendar"></span> Próxima Semana
                    </button>
                </div>
                
                <div class="dias-tabs">
                    <?php foreach ($dias_semana as $idx => $dia): ?>
                        <?php $num = $idx + 1; ?>
                        <div class="dia-tab <?php echo $dia_sel === $num ? 'active' : ''; ?>" data-dia="<?php echo $num; ?>">
                            <?php echo substr($dia, 0, 3); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="turnos-container">
                    <?php
                    $turnos_dia = $horario_actual[$dia_sel] ?? [];
                    ksort($turnos_dia);
                    ?>
                    
                    <?php if (empty($turnos_dia)): ?>
                    <div class="empty-turnos"><span class="icon-info"></span> No hay turnos para este día</div>
                    <?php else: ?>
                        <?php foreach ($turnos_dia as $turno => $data): ?>
                        <div class="turno-item" data-turno="<?php echo $turno; ?>" data-id="<?php echo $data['id']; ?>">
                            <div class="turno-header">
                                <span class="turno-numero">Turno <?php echo $turno; ?></span>
                                <span class="turno-hora"><?php echo $data['turnos_duracion']; ?> turno(s)</span>
                            </div>
                            <div class="turno-edit">
                                <select class="turno-asignatura">
                                    <option value="">Seleccionar</option>
                                    <?php foreach ($asignaturas as $asig): ?>
                                    <option value="<?php echo $asig['id']; ?>" <?php echo $data['asignatura_id'] == $asig['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($asig['nombre']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" class="turno-duracion" value="<?php echo $data['turnos_duracion']; ?>" min="1" max="8">
                                <select class="turno-tipo">
                                    <?php foreach ($tipos_evento as $val => $label): ?>
                                    <option value="<?php echo $val; ?>" <?php echo $data['tipo_evento'] == $val ? 'selected' : ''; ?>><?php echo $label; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn-save-turno" onclick="guardarTurno(this)"><span class="icon-save"></span> Guardar</button>
                                <button class="btn-delete-turno" onclick="eliminarTurno(this)"><span class="icon-delete"></span> Eliminar</button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="add-turno-section">
                    <h3 style="margin-bottom: 15px; color: #065f46; display: flex; align-items: center; gap: 6px;">
                        <span class="icon-plus-circle"></span> Agregar Nuevo Turno
                    </h3>
                    <div class="add-turno-form">
                        <div class="form-group">
                            <label>Turno #</label>
                            <input type="number" id="nuevoTurno" min="1" max="12" value="1">
                        </div>
                        <div class="form-group">
                            <label>Asignatura</label>
                            <select id="nuevaAsignatura">
                                <option value="">Seleccionar</option>
                                <?php foreach ($asignaturas as $asig): ?>
                                <option value="<?php echo $asig['id']; ?>"><?php echo htmlspecialchars($asig['nombre']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Duración</label>
                            <input type="number" id="nuevaDuracion" min="1" max="8" value="1">
                        </div>
                        <div class="form-group">
                            <label>Tipo</label>
                            <select id="nuevoTipo">
                                <?php foreach ($tipos_evento as $val => $label): ?>
                                <option value="<?php echo $val; ?>"><?php echo $label; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button class="btn-add-turno" onclick="agregarTurno()"><span class="icon-plus"></span> Agregar</button>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- TAB CONFIGURACIÓN -->
        <div id="tab-config" style="display: none;">
            <div class="config-card">
                <h2 class="section-title"><span class="icon-settings"></span> Configuración General</h2>
                <form id="configForm">
                    <div class="form-group">
                        <label><span class="icon-clock"></span> Hora de inicio</label>
                        <input type="time" name="hora_inicio" value="<?php echo $config['hora_inicio']; ?>">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Duración L-J (min)</label>
                            <input type="number" name="duracion_lunes_jueves" value="<?php echo $config['duracion_turno_lunes_jueves']; ?>" min="30" max="60">
                        </div>
                        <div class="form-group">
                            <label>Duración Viernes (min)</label>
                            <input type="number" name="duracion_viernes" value="<?php echo $config['duracion_turno_viernes']; ?>" min="30" max="60">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descanso entre turnos (min)</label>
                        <input type="number" name="descanso" value="<?php echo $config['descanso_entre_turnos']; ?>" min="0" max="15">
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Merienda después del turno</label>
                            <input type="number" name="merienda_turno" value="<?php echo $config['merienda_despues_turno']; ?>" min="0" max="10">
                        </div>
                        <div class="form-group">
                            <label>Duración merienda (min)</label>
                            <input type="number" name="merienda_duracion" value="<?php echo $config['duracion_merienda']; ?>" min="0" max="30">
                        </div>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="viernes_merienda" <?php echo $config['viernes_con_merienda'] ? 'checked' : ''; ?>>
                        <label>Viernes con merienda</label>
                    </div>
                    <div class="checkbox-group">
                        <input type="checkbox" name="sabado_clases" <?php echo $config['sabado_con_clases'] ? 'checked' : ''; ?>>
                        <label>Sábado con clases</label>
                    </div>
                    <button type="submit" class="btn-guardar"><span class="icon-save"></span> Guardar Configuración</button>
                </form>
            </div>
        </div>
    </main>

    <script>
        const pelotonesPorGrado = <?php echo json_encode($pelotones_por_grado); ?>;
        const gradoActual = '<?php echo $grado_sel; ?>';
        const pelotonActual = <?php echo $peloton_sel; ?>;
        const diaActual = <?php echo $dia_sel; ?>;
        const semanaActual = '<?php echo $semana_sel; ?>';
        let pelotonId = <?php echo $peloton_id; ?>;
        
        // Tabs
        document.querySelectorAll('.tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                document.getElementById('tab-horario').style.display = this.dataset.tab === 'horario' ? 'block' : 'none';
                document.getElementById('tab-config').style.display = this.dataset.tab === 'config' ? 'block' : 'none';
            });
        });
        
        // Selector grado
        document.getElementById('selectGrado').addEventListener('change', function() {
            const grado = this.value;
            const pelotones = pelotonesPorGrado[grado] || [];
            const select = document.getElementById('selectPeloton');
            select.innerHTML = '';
            pelotones.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p.numero;
                opt.textContent = 'Pelotón ' + p.numero;
                select.appendChild(opt);
            });
            if (pelotones.length > 0) {
                pelotonId = pelotones[0].id;
            }
            actualizarURL();
        });
        
        document.getElementById('selectPeloton').addEventListener('change', function() {
            const grado = document.getElementById('selectGrado').value;
            const peloton = parseInt(this.value);
            const pelotones = pelotonesPorGrado[grado] || [];
            const selected = pelotones.find(p => p.numero === peloton);
            if (selected) pelotonId = selected.id;
            actualizarURL();
        });
        
        document.querySelectorAll('.semana-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.semana-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                actualizarURL();
            });
        });
        
        document.querySelectorAll('.dia-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                document.querySelectorAll('.dia-tab').forEach(t => t.classList.remove('active'));
                this.classList.add('active');
                actualizarURL();
            });
        });
        
        function actualizarURL() {
            const grado = document.getElementById('selectGrado').value;
            const peloton = document.getElementById('selectPeloton').value;
            const semana = document.querySelector('.semana-btn.active').dataset.semana;
            const dia = document.querySelector('.dia-tab.active').dataset.dia;
            window.location.href = `?grado=${grado}&peloton=${peloton}&semana=${semana}&dia=${dia}`;
        }
        
        function guardarTurno(btn) {
            const item = btn.closest('.turno-item');
            const turnoId = item.dataset.id;
            const turno = item.dataset.turno;
            const asignatura = item.querySelector('.turno-asignatura').value;
            const duracion = item.querySelector('.turno-duracion').value;
            const tipo = item.querySelector('.turno-tipo').value;
            
            if (!asignatura) { alert('Selecciona una asignatura'); return; }
            
            const fd = new FormData();
            fd.append('accion', 'guardar_turno');
            fd.append('turno_id', turnoId);
            fd.append('asignatura_id', asignatura);
            fd.append('duracion', duracion);
            fd.append('tipo', tipo);
            
            fetch('ajax_guardar.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { if (d.success) location.reload(); else alert('Error: ' + d.message); });
        }
        
        function eliminarTurno(btn) {
            if (!confirm('¿Eliminar este turno?')) return;
            const turnoId = btn.closest('.turno-item').dataset.id;
            const fd = new FormData();
            fd.append('accion', 'eliminar_turno');
            fd.append('turno_id', turnoId);
            
            fetch('ajax_guardar.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { if (d.success) location.reload(); else alert('Error: ' + d.message); });
        }
        
        function agregarTurno() {
            const turno = document.getElementById('nuevoTurno').value;
            const asignatura = document.getElementById('nuevaAsignatura').value;
            const duracion = document.getElementById('nuevaDuracion').value;
            const tipo = document.getElementById('nuevoTipo').value;
            
            if (!asignatura) { alert('Selecciona una asignatura'); return; }
            if (pelotonId === 0) { alert('No se pudo identificar el pelotón'); return; }
            
            const fd = new FormData();
            fd.append('accion', 'agregar_turno');
            fd.append('peloton_id', pelotonId);
            fd.append('dia', diaActual);
            fd.append('turno', turno);
            fd.append('asignatura_id', asignatura);
            fd.append('duracion', duracion);
            fd.append('tipo', tipo);
            fd.append('semana', semanaActual);
            
            fetch('ajax_guardar.php', { method: 'POST', body: fd })
                .then(r => r.json())
                .then(d => { if (d.success) location.reload(); else alert('Error: ' + d.message); });
        }
        
        document.getElementById('configForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const fd = new FormData(e.target);
            fd.append('accion', 'guardar_config');
            const r = await fetch('ajax_guardar.php', { method: 'POST', body: fd });
            const d = await r.json();
            if (d.success) window.location.href = 'config.php?msg=config_ok';
            else alert('Error: ' + d.message);
        });
    </script>
</body>
</html>