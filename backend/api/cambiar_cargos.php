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

// Solo directiva puede acceder
if ($usuario_cargo !== 'directiva') {
    header('Location: ../index.php');
    exit();
}

// Procesar cambio de ocupación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usuario_id']) && isset($_POST['tipo']) && isset($_POST['ocupacion'])) {
    $target_id = (int)$_POST['usuario_id'];
    $tipo = $_POST['tipo'];
    $ocupacion = $_POST['ocupacion'];
    
    $tablas_permitidas = ['estudiante', 'profesor', 'oficial'];
    if (in_array($tipo, $tablas_permitidas)) {
        $sql = "UPDATE $tipo SET ocupacion = ? WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("si", $ocupacion, $target_id);
        $stmt->execute();
        $stmt->close();
        
        // Redirigir para evitar reenvío
        header('Location: cambiar_cargos.php?msg=ok&' . http_build_query($_GET));
        exit();
    }
}

// Obtener listado de ocupaciones
$ocupaciones_estudiante = ['ninguno', 'activista', 'jefe_escuadra', 'politico_peloton', '2do_jefe_peloton', '1er_jefe_peloton', 'politico_compania', '2do_jefe_compania', '1er_jefe_compania', 'sargento_mayor', '2do_jefe_batallon', 'jefe_batallon'];
$ocupaciones_oficial = ['teniente', 'primer_teniente', 'capitan', 'mayor', 'teniente_coronel', 'coronel', 'primer_coronel'];
$ocupaciones_profesor = ['matematicas', 'historia', 'fisica', 'quimica', 'ingles', 'literatura_lengua', 'preparacion_fisica', 'cultura_politica', 'preparacion_ciudadana', 'panorama_cultura_cubana', 'informatica', 'biblioteca', 'biologia', 'geografia', 'secretaria', 'otro'];

// Búsqueda
$busqueda = $_GET['q'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$resultados = [];

if (!empty($busqueda)) {
    $busqueda_param = "%$busqueda%";
    
    $tablas = $filtro_tipo === 'todos' ? ['estudiante', 'profesor', 'oficial'] : [$filtro_tipo];
    
    foreach ($tablas as $tabla) {
        // Verificar si existe el campo activo
        $check_activo = $conexion->query("SHOW COLUMNS FROM $tabla LIKE 'activo'");
        $tiene_activo = ($check_activo && $check_activo->num_rows > 0);
        
        // Construir consulta base
        $sql = "SELECT id, nombre, apellidos, ci, ocupacion, '$tabla' as tipo FROM $tabla WHERE ";
        $sql .= "(nombre LIKE ? OR apellidos LIKE ? OR ci LIKE ?)";
        if ($tiene_activo) {
            $sql .= " AND activo = 1";
        }
        $sql .= " LIMIT 20";
        
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("sss", $busqueda_param, $busqueda_param, $busqueda_param);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $resultados[] = $row;
            }
            $stmt->close();
        }
    }
}

$mensaje = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Cambiar Cargos - Directiva</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; padding-bottom: 30px; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .main-content { padding: 20px 16px; max-width: 700px; margin: 0 auto; }
        .search-section { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .search-box { display: flex; gap: 10px; margin-bottom: 15px; flex-wrap: wrap; }
        .search-box input { flex: 1; min-width: 200px; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 14px; font-family: 'Poppins', sans-serif; }
        .search-box select { padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 14px; font-family: 'Poppins', sans-serif; }
        .search-box button { padding: 14px 20px; background: #1e3c72; color: white; border: none; border-radius: 14px; cursor: pointer; }
        .mensaje { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 15px; }
        .usuario-card { background: #f8fafc; border-radius: 16px; padding: 16px; margin-bottom: 12px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .usuario-info { flex: 1; }
        .usuario-nombre { font-weight: 600; color: #1e293b; margin-bottom: 4px; }
        .usuario-detalles { font-size: 0.85em; color: #64748b; }
        .usuario-tipo { display: inline-block; background: #e2e8f0; padding: 2px 10px; border-radius: 20px; font-size: 0.75em; margin-left: 8px; text-transform: capitalize; }
        .usuario-ocupacion select { padding: 10px 14px; border: 2px solid #e0e0e0; border-radius: 12px; font-family: 'Poppins', sans-serif; min-width: 180px; }
        .btn-guardar { background: #10b981; color: white; border: none; padding: 10px 16px; border-radius: 12px; cursor: pointer; margin-left: 10px; }
        .no-resultados { text-align: center; padding: 40px; color: #94a3b8; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../index.php" class="back-btn">←</a>
        <h1 class="header-title">Cambiar Cargos</h1>
    </header>

    <main class="main-content">
        <?php if ($mensaje === 'ok'): ?>
        <div class="mensaje">✅ Cargo actualizado correctamente</div>
        <?php endif; ?>

        <div class="search-section">
            <form method="GET">
                <div class="search-box">
                    <input type="text" name="q" placeholder="🔍 Buscar por nombre o CI..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <select name="tipo">
                        <option value="todos" <?php echo $filtro_tipo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="estudiante" <?php echo $filtro_tipo === 'estudiante' ? 'selected' : ''; ?>>Estudiantes</option>
                        <option value="profesor" <?php echo $filtro_tipo === 'profesor' ? 'selected' : ''; ?>>Profesores</option>
                        <option value="oficial" <?php echo $filtro_tipo === 'oficial' ? 'selected' : ''; ?>>Oficiales</option>
                    </select>
                    <button type="submit">🔎 Buscar</button>
                </div>
            </form>

            <?php if (!empty($busqueda)): ?>
                <?php if (empty($resultados)): ?>
                <div class="no-resultados">No se encontraron usuarios</div>
                <?php else: ?>
                    <?php foreach ($resultados as $usuario): ?>
                    <div class="usuario-card">
                        <div class="usuario-info">
                            <div class="usuario-nombre">
                                <?php echo htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellidos']); ?>
                                <span class="usuario-tipo"><?php echo ucfirst($usuario['tipo']); ?></span>
                            </div>
                            <div class="usuario-detalles">CI: <?php echo htmlspecialchars($usuario['ci']); ?></div>
                        </div>
                        <div class="usuario-ocupacion">
                            <form method="POST" style="display: flex; align-items: center; gap: 8px;">
                                <input type="hidden" name="usuario_id" value="<?php echo $usuario['id']; ?>">
                                <input type="hidden" name="tipo" value="<?php echo $usuario['tipo']; ?>">
                                <select name="ocupacion">
                                    <?php if ($usuario['tipo'] === 'estudiante'): ?>
                                        <?php foreach ($ocupaciones_estudiante as $ocup): ?>
                                        <option value="<?php echo $ocup; ?>" <?php echo ($usuario['ocupacion'] ?? 'ninguno') === $ocup ? 'selected' : ''; ?>>
                                            <?php echo str_replace('_', ' ', ucfirst($ocup)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php elseif ($usuario['tipo'] === 'oficial'): ?>
                                        <?php foreach ($ocupaciones_oficial as $ocup): ?>
                                        <option value="<?php echo $ocup; ?>" <?php echo ($usuario['ocupacion'] ?? 'teniente') === $ocup ? 'selected' : ''; ?>>
                                            <?php echo str_replace('_', ' ', ucfirst($ocup)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <?php foreach ($ocupaciones_profesor as $ocup): ?>
                                        <option value="<?php echo $ocup; ?>" <?php echo ($usuario['ocupacion'] ?? '') === $ocup ? 'selected' : ''; ?>>
                                            <?php echo str_replace('_', ' ', ucfirst($ocup)); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                                <button type="submit" class="btn-guardar">Guardar</button>
                            </form>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="no-resultados">Ingresa un término de búsqueda</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>