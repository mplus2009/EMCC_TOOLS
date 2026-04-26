<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../db.php';

$usuario_actual_cargo = $_SESSION['usuario_cargo'] ?? '';
$usuario_actual_id = $_SESSION['usuario_id'] ?? 0;

// Verificar permisos (directiva, oficial, profesor, secretaria)
$es_secretaria = false;
if ($usuario_actual_cargo === 'profesor') {
    $sql = "SELECT ocupacion FROM profesor WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $usuario_actual_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $es_secretaria = ($row['ocupacion'] === 'secretaria');
    }
    $stmt->close();
}

$puede_ver = in_array($usuario_actual_cargo, ['directiva', 'oficial', 'profesor']) || $es_secretaria;

if (!$puede_ver) {
    header('Location: index.php');
    exit();
}

// Obtener ID del estudiante
$estudiante_id_raw = (int)($_GET['id'] ?? 0);

if ($estudiante_id_raw <= 0) {
    header('Location: index.php');
    exit();
}

// Formatear ID para el sistema de actividades
$id_formateado = 'estudiante_' . $estudiante_id_raw;

// Obtener datos del estudiante
$estudiante = null;
$sql = "SELECT e.* FROM estudiante e WHERE e.id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $estudiante_id_raw);
$stmt->execute();
$result = $stmt->get_result();
$estudiante = $result->fetch_assoc();
$stmt->close();

if (!$estudiante) {
    header('Location: index.php');
    exit();
}

// Obtener estadísticas
$meritos = 0;
$demeritos = 0;
$balance = 0;

$sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad WHERE id_end = ? AND tipo = 'merito'";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $id_formateado);
$stmt->execute();
$result = $stmt->get_result();
$meritos = $result->fetch_assoc()['total'];
$stmt->close();

$sql = "SELECT COALESCE(SUM(cantidad), 0) as total FROM actividad WHERE id_end = ? AND tipo = 'demerito'";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $id_formateado);
$stmt->execute();
$result = $stmt->get_result();
$demeritos = $result->fetch_assoc()['total'];
$stmt->close();

$balance = $meritos - $demeritos;

// Obtener últimas actividades
$actividades = [];
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
        LIMIT 20";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $id_formateado);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $actividades[] = $row;
}
$stmt->close();

// Obtener méritos por categoría
$meritos_cat = [];
$sql = "SELECT categoria, SUM(cantidad) as total FROM actividad 
        WHERE id_end = ? AND tipo = 'merito' 
        GROUP BY categoria ORDER BY total DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $id_formateado);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $meritos_cat[] = $row;
}
$stmt->close();

// Obtener deméritos por categoría
$demeritos_cat = [];
$sql = "SELECT categoria, SUM(cantidad) as total FROM actividad 
        WHERE id_end = ? AND tipo = 'demerito' 
        GROUP BY categoria ORDER BY total DESC";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("s", $id_formateado);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $demeritos_cat[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Perfil de Estudiante</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="iconos_vectoriales.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; padding-bottom: 30px; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); border: none; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .main-content { padding: 20px 16px; max-width: 600px; margin: 0 auto; }
        
        .perfil-card { background: white; border-radius: 20px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); text-align: center; }
        .perfil-avatar { width: 90px; height: 90px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 45px; }
        .perfil-nombre { font-size: 1.5em; font-weight: 700; color: #1e3c72; margin-bottom: 5px; }
        .perfil-detalles { color: #64748b; margin-bottom: 20px; }
        .perfil-detalles span { display: inline-block; background: #e2e8f0; padding: 4px 12px; border-radius: 20px; margin: 3px; font-size: 0.9em; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-top: 20px; }
        .stat-box { background: #f8fafc; border-radius: 16px; padding: 16px 10px; text-align: center; }
        .stat-box .icon { font-size: 28px; margin-bottom: 8px; }
        .stat-box.merito .icon { color: #10b981; }
        .stat-box.demerito .icon { color: #ef4444; }
        .stat-box.balance .icon { color: #667eea; }
        .stat-valor { font-size: 1.8em; font-weight: 800; color: #1e3c72; }
        .stat-label { font-size: 0.8em; color: #64748b; text-transform: uppercase; }
        
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
        
        .btn-reportar { display: block; background: #1e3c72; color: white; text-align: center; padding: 16px; border-radius: 16px; text-decoration: none; font-weight: 600; margin-top: 10px; }
        .empty-state { text-align: center; padding: 30px; color: #94a3b8; }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="back-btn">←</a>
        <h1 class="header-title">Perfil del Estudiante</h1>
    </header>

    <main class="main-content">
        <div class="perfil-card">
            <div class="perfil-avatar"><span class="icon-user"></span></div>
            <h2 class="perfil-nombre"><?php echo htmlspecialchars($estudiante['nombre'] . ' ' . $estudiante['apellidos']); ?></h2>
            <div class="perfil-detalles">
                <span>CI: <?php echo htmlspecialchars($estudiante['CI']); ?></span>
                <span><?php echo htmlspecialchars($estudiante['grado']); ?></span>
                <span>Pelotón <?php echo $estudiante['peloton'] ?? '?'; ?></span>
            </div>
            
            <div class="stats-grid">
                <div class="stat-box merito">
                    <div class="icon"><span class="icon-trophy"></span></div>
                    <div class="stat-valor"><?php echo $meritos; ?></div>
                    <div class="stat-label">Méritos</div>
                </div>
                <div class="stat-box demerito">
                    <div class="icon"><span class="icon-warning"></span></div>
                    <div class="stat-valor"><?php echo $demeritos; ?></div>
                    <div class="stat-label">Deméritos</div>
                </div>
                <div class="stat-box balance">
                    <div class="icon"><span class="icon-balance"></span></div>
                    <div class="stat-valor" style="color: <?php echo $balance >= 0 ? '#10b981' : '#ef4444'; ?>"><?php echo $balance; ?></div>
                    <div class="stat-label">Balance</div>
                </div>
            </div>
            
            <a href="notificar.php?destinatario=<?php echo $estudiante_id_raw; ?>" class="btn-reportar">
                <span class="icon-plus"></span> Reportar a este estudiante
            </a>
        </div>
        
        <div class="seccion-card">
            <h3 class="seccion-titulo"><span class="icon-trophy" style="color: #10b981;"></span> Méritos por Categoría</h3>
            <?php if (empty($meritos_cat)): ?>
            <div class="empty-state">No hay méritos registrados</div>
            <?php else: ?>
                <?php foreach ($meritos_cat as $cat): ?>
                <div class="categoria-item">
                    <span class="categoria-nombre"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                    <span class="categoria-total merito">+<?php echo $cat['total']; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="seccion-card">
            <h3 class="seccion-titulo"><span class="icon-warning" style="color: #ef4444;"></span> Deméritos por Categoría</h3>
            <?php if (empty($demeritos_cat)): ?>
            <div class="empty-state">No hay deméritos registrados</div>
            <?php else: ?>
                <?php foreach ($demeritos_cat as $cat): ?>
                <div class="categoria-item">
                    <span class="categoria-nombre"><?php echo htmlspecialchars($cat['categoria']); ?></span>
                    <span class="categoria-total demerito">-<?php echo $cat['total']; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <div class="seccion-card">
            <h3 class="seccion-titulo"><span class="icon-history"></span> Últimas Actividades</h3>
            <?php if (empty($actividades)): ?>
            <div class="empty-state">No hay actividades recientes</div>
            <?php else: ?>
                <?php foreach ($actividades as $act): ?>
                    <?php $esMerito = ($act['tipo'] === 'merito'); ?>
                    <div class="actividad-mini <?php echo $esMerito ? 'merito' : 'demerito'; ?>">
                        <div class="actividad-mini-info">
                            <div class="actividad-mini-nombre"><?php echo htmlspecialchars($act['falta_causa']); ?></div>
                            <div class="actividad-mini-fecha">
                                <?php echo date('d/m/Y', strtotime($act['fecha'])); ?> - 
                                Notificado por: <?php echo htmlspecialchars($act['notificador']); ?>
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