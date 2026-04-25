<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../db.php';

$usuario_cargo = $_SESSION['usuario_cargo'] ?? '';
$usuario_id = $_SESSION['usuario_id'] ?? 0;

// Solo oficial puede acceder
if ($usuario_cargo !== 'oficial') {
    header('Location: ../index.php');
    exit();
}

// Procesar cambio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['estudiante_id']) && isset($_POST['ocupacion'])) {
    $estudiante_id = (int)$_POST['estudiante_id'];
    $ocupacion = $_POST['ocupacion'];
    
    $sql = "UPDATE estudiante SET ocupacion = ? WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("si", $ocupacion, $estudiante_id);
    $stmt->execute();
    $stmt->close();
    
    header('Location: cambio_mando.php?msg=ok&' . http_build_query($_GET));
    exit();
}

// Grados disponibles
$grados = ['10mo', '11no', '12mo'];
$grado_filtro = $_GET['grado'] ?? '11no';

// Obtener estudiantes del grado seleccionado
$estudiantes = [];
$sql = "SELECT id, nombre, apellidos, ci, ocupacion FROM estudiante WHERE grado = ? ORDER BY apellidos, nombre";
$stmt = $conexion->prepare($sql);
if ($stmt) {
    $stmt->bind_param("s", $grado_filtro);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $estudiantes[] = $row;
    }
    $stmt->close();
}

$ocupaciones_estudiante = ['ninguno', 'activista', 'jefe_escuadra', 'politico_peloton', '2do_jefe_peloton', '1er_jefe_peloton', 'politico_compania', '2do_jefe_compania', '1er_jefe_compania', 'sargento_mayor', '2do_jefe_batallon', 'jefe_batallon'];
$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Cambio de Mando - Oficial</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; padding-bottom: 30px; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; }
        .back-btn { background: rgba(255,255,255,0.15); width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .main-content { padding: 20px 16px; max-width: 700px; margin: 0 auto; }
        .filter-section { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; }
        .filter-row { display: flex; gap: 10px; margin-bottom: 15px; }
        .filter-row select { flex: 1; padding: 14px; border: 2px solid #e0e0e0; border-radius: 14px; font-family: 'Poppins', sans-serif; }
        .filter-row button { padding: 14px 20px; background: #1e3c72; color: white; border: none; border-radius: 14px; cursor: pointer; }
        .mensaje { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 15px; }
        .estudiante-card { background: #f8fafc; border-radius: 16px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .estudiante-info { flex: 1; }
        .estudiante-nombre { font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .estudiante-ocupacion { font-size: 0.85em; color: #64748b; }
        .estudiante-ocupacion span { background: #e2e8f0; padding: 2px 10px; border-radius: 20px; }
        .estudiante-accion select { padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'Poppins', sans-serif; min-width: 180px; }
        .btn-guardar { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 12px; cursor: pointer; margin-left: 10px; }
        .no-estudiantes { text-align: center; padding: 40px; color: #94a3b8; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../index.php" class="back-btn">←</a>
        <h1 class="header-title">Cambio de Mando</h1>
    </header>

    <main class="main-content">
        <?php if ($mensaje === 'ok'): ?>
        <div class="mensaje">✅ Cambio realizado correctamente</div>
        <?php endif; ?>

        <div class="filter-section">
            <form method="GET">
                <div class="filter-row">
                    <select name="grado">
                        <?php foreach ($grados as $g): ?>
                        <option value="<?php echo $g; ?>" <?php echo $grado_filtro === $g ? 'selected' : ''; ?>><?php echo $g; ?> Grado</option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit">🔎 Filtrar</button>
                </div>
            </form>

            <?php if (empty($estudiantes)): ?>
            <div class="no-estudiantes">No hay estudiantes en este grado</div>
            <?php else: ?>
                <?php foreach ($estudiantes as $est): ?>
                <div class="estudiante-card">
                    <div class="estudiante-info">
                        <div class="estudiante-nombre"><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellidos']); ?></div>
                        <div class="estudiante-ocupacion">
                            Ocupación actual: 
                            <span><?php echo str_replace('_', ' ', ucfirst($est['ocupacion'] ?? 'ninguno')); ?></span>
                        </div>
                    </div>
                    <div class="estudiante-accion">
                        <form method="POST" style="display: flex; align-items: center; gap: 8px;">
                            <input type="hidden" name="estudiante_id" value="<?php echo $est['id']; ?>">
                            <select name="ocupacion">
                                <?php foreach ($ocupaciones_estudiante as $ocup): ?>
                                <option value="<?php echo $ocup; ?>" <?php echo ($est['ocupacion'] ?? 'ninguno') === $ocup ? 'selected' : ''; ?>>
                                    <?php echo str_replace('_', ' ', ucfirst($ocup)); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" class="btn-guardar">Guardar</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>