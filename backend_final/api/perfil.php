<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../db.php';

$usuario_id = $_SESSION['usuario_id'] ?? 0;
$usuario_cargo = $_SESSION['usuario_cargo'] ?? 'estudiante';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';
$usuario_apellidos = $_SESSION['usuario_apellidos'] ?? '';
$usuario_ci = $_SESSION['usuario_ci'] ?? '';

// Formatear ID para el nuevo sistema
$id_formateado = $usuario_cargo . '_' . $usuario_id;

// Obtener ocupación del usuario si existe
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

// Obtener estadísticas
$meritos_count = 0;
$demeritos_count = 0;
$balance = 0;
$meritos_por_categoria = [];
$demeritos_por_categoria = [];
$ultimas_actividades = [];

if ($usuario_cargo === 'estudiante' && $usuario_id > 0) {
    
    // Total méritos
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad WHERE id_end = ? AND tipo = 'merito'";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $meritos_count = $row['total'];
        $stmt->close();
    }
    
    // Total deméritos
    $sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad WHERE id_end = ? AND tipo = 'demerito'";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) $demeritos_count = $row['total'];
        $stmt->close();
    }
    
    $balance = $meritos_count - $demeritos_count;
    
    // Méritos por categoría
    $sql = "SELECT categoria, SUM(cantidad) as total FROM actividad 
            WHERE id_end = ? AND tipo = 'merito' 
            GROUP BY categoria ORDER BY total DESC";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $meritos_por_categoria[] = $row;
        }
        $stmt->close();
    }
    
    // Deméritos por categoría
    $sql = "SELECT categoria, SUM(cantidad) as total FROM actividad 
            WHERE id_end = ? AND tipo = 'demerito' 
            GROUP BY categoria ORDER BY total DESC";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $demeritos_por_categoria[] = $row;
        }
        $stmt->close();
    }
    
    // Últimas actividades (con nombre del notificador)
    $sql = "SELECT a.*, 
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
            ORDER BY a.fecha DESC, a.hora DESC 
            LIMIT 10";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $id_formateado);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $ultimas_actividades[] = $row;
        }
        $stmt->close();
    }
}

// Obtener grado y pelotón del estudiante
$grado_estudiante = '';
$peloton_estudiante = '';
if ($usuario_cargo === 'estudiante' && $usuario_id > 0) {
    $sql = "SELECT grado, peloton FROM estudiante WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $grado_estudiante = $row['grado'] ?? '';
            $peloton_estudiante = $row['peloton'] ?? '';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Mi Perfil - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="iconos_vectoriales.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; padding-bottom: 30px; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); border: none; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .main-content { padding: 20px 16px; max-width: 600px; margin: 0 auto; }
        
        .perfil-card { background: white; border-radius: 24px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); text-align: center; }
        .perfil-avatar { width: 90px; height: 90px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 45px; }
        .perfil-nombre { font-size: 1.5em; font-weight: 700; color: #1e3c72; margin-bottom: 5px; }
        .perfil-cargo { display: inline-block; background: #e2e8f0; padding: 5px 15px; border-radius: 20px; font-size: 0.9em; color: #64748b; margin-bottom: 15px; }
        .perfil-detalles { display: flex; justify-content: center; gap: 20px; margin-top: 10px; color: #64748b; font-size: 0.9em; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
        .stat-box { background: #f8fafc; border-radius: 16px; padding: 16px 10px; text-align: center; }
        .stat-box .icon { font-size: 28px; margin-bottom: 8px; }
        .stat-box.merito .icon { color: #10b981; }
        .stat-box.demerito .icon { color: #ef4444; }
        .stat-box.balance .icon { color: #667eea; }
        .stat-valor { font-size: 1.8em; font-weight: 800; color: #1e3c72; }
        .stat-label { font-size: 0.8em; color: #64748b; text-transform: uppercase; }
        
        .mensaje-estado { padding: 20px; border-radius: 20px; text-align: center; margin-top: 20px; color: white; }
        .mensaje-estado.excelente { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .mensaje-estado.atencion { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); }
        .mensaje-estado.equilibrado { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .mensaje-estado i { font-size: 40px; margin-bottom: 10px; }
        .mensaje-estado h3 { font-size: 1.3em; margin-bottom: 5px; }
        
        .seccion-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .seccion-titulo { color: #1e3c72; font-size: 1.2em; font-weight: 700; margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
        
        .categoria-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
        .categoria-item:last-child { border-bottom: none; }
        .categoria-nombre { color: #1e293b; }
        .categoria-total { font-weight: 700; }
        .categoria-total.merito { color: #10b981; }
        .categoria-total.demerito { color: #ef4444; }
        
        .actividad-mini { padding: 12px; border-radius: 12px; margin-bottom: 8px; display: flex; align-items: center; gap: 12px; }
        .actividad-mini.merito { background: #d1fae5; }
        .actividad-mini.demerito { background: #fee2e2; }
        .actividad-mini-info { flex: 1; }
        .actividad-mini-nombre { font-weight: 600; color: #1e293b; font-size: 0.95em; }
        .actividad-mini-fecha { font-size: 0.75em; color: #64748b; }
        .actividad-mini-valor { font-weight: 700; font-size: 1.1em; }
        
        .empty-state { text-align: center; padding: 30px; color: #94a3b8; }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="back-btn"><span class="icon-arrow-left"></span></a>
        <h1 class="header-title"><span class="icon-user"></span> Mi Perfil</h1>
    </header>

    <main class="main-content">
        <div class="perfil-card">
            <div class="perfil-avatar"><span class="icon-user"></span></div>
            <h2 class="perfil-nombre"><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellidos); ?></h2>
            <span class="perfil-cargo">
                <?php echo ucfirst($usuario_cargo); ?>
                <?php if (!empty($ocupacion_usuario) && $ocupacion_usuario !== 'ninguno'): ?>
                - <?php echo str_replace('_', ' ', ucfirst($ocupacion_usuario)); ?>
                <?php endif; ?>
            </span>
            
            <?php if ($usuario_cargo === 'estudiante'): ?>
            <div class="perfil-detalles">
                <span><span class="icon-graduation"></span> <?php echo htmlspecialchars($grado_estudiante); ?></span>
                <span><span class="icon-users"></span> Pelotón <?php echo $peloton_estudiante; ?></span>
                <span><span class="icon-id"></span> CI: <?php echo htmlspecialchars($usuario_ci); ?></span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box merito">
                    <div class="icon"><span class="icon-trophy"></span></div>
                    <div class="stat-valor"><?php echo $meritos_count; ?></div>
                    <div class="stat-label">Méritos</div>
                </div>
                <div class="stat-box demerito">
                    <div class="icon"><span class="icon-warning"></span></div>
                    <div class="stat-valor"><?php echo $demeritos_count; ?></div>
                    <div class="stat-label">Deméritos</div>
                </div>
                <div class="stat-box balance">
                    <div class="icon"><span class="icon-balance"></span></div>
                    <div class="stat-valor" style="color: <?php echo $balance >= 0 ? '#10b981' : '#ef4444'; ?>"><?php echo $balance; ?></div>
                    <div class="stat-label">Balance</div>
                </div>
            </div>
            
            <?php if ($balance > 0): ?>
            <div class="mensaje-estado excelente">
                <span class="icon-trophy"></span>
                <h3>¡Excelente!</h3>
                <p>Tienes más méritos que deméritos. ¡Sigue así!</p>
            </div>
            <?php elseif ($balance < 0): ?>
            <div class="mensaje-estado atencion">
                <span class="icon-warning"></span>
                <h3>Atención</h3>
                <p>Tienes más deméritos que méritos. ¡Esfuérzate más!</p>
            </div>
            <?php else: ?>
            <div class="mensaje-estado equilibrado">
                <span class="icon-balance"></span>
                <h3>Equilibrado</h3>
                <p>Tus méritos y deméritos están igualados.</p>
            </div>
            <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($usuario_cargo === 'estudiante'): ?>
        <div class="seccion-card">
            <h3 class="seccion-titulo"><span class="icon-trophy" style="color: #10b981;"></span> Méritos por Categoría</h3>
            <?php if (empty($meritos_por_categoria)): ?>
            <div class="empty-state">No hay méritos registrados</div>
            <?php else: ?>
                <?php foreach ($meritos_por_categoria as $cat): ?>
                <div class="categoria-item">
                    <span class="categoria-nombre"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                    <span class="categoria-total merito">+<?php echo $cat['total']; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="seccion-card">
            <h3 class="seccion-titulo"><span class="icon-warning" style="color: #ef4444;"></span> Deméritos por Categoría</h3>
            <?php if (empty($demeritos_por_categoria)): ?>
            <div class="empty-state">No hay deméritos registrados</div>
            <?php else: ?>
                <?php foreach ($demeritos_por_categoria as $cat): ?>
                <div class="categoria-item">
                    <span class="categoria-nombre"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                    <span class="categoria-total demerito">-<?php echo $cat['total']; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="seccion-card">
            <h3 class="seccion-titulo"><span class="icon-history"></span> Últimas Actividades</h3>
            <?php if (empty($ultimas_actividades)): ?>
            <div class="empty-state">No hay actividades recientes</div>
            <?php else: ?>
                <?php foreach ($ultimas_actividades as $act): ?>
                    <?php $esMerito = ($act['tipo'] === 'merito'); ?>
                    <div class="actividad-mini <?php echo $esMerito ? 'merito' : 'demerito'; ?>">
                        <div class="actividad-mini-info">
                            <div class="actividad-mini-nombre"><?php echo htmlspecialchars($act['falta_causa']); ?></div>
                            <div class="actividad-mini-fecha">
                                <?php echo date('d/m/Y', strtotime($act['fecha'])); ?> - 
                                Notificado por: <?php echo htmlspecialchars($act['notificador'] ?? 'Sistema'); ?>
                            </div>
                        </div>
                        <div class="actividad-mini-valor" style="color: <?php echo $esMerito ? '#065f46' : '#991b1b'; ?>">
                            <?php echo $esMerito ? '+' : '-'; ?><?php echo $act['cantidad']; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>