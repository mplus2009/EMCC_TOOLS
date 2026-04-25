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

// Verificar si es secretaria
$es_secretaria = false;
if ($usuario_cargo === 'profesor') {
    $check = $conexion->query("SHOW COLUMNS FROM profesor LIKE 'ocupacion'");
    if ($check && $check->num_rows > 0) {
        $sql = "SELECT ocupacion FROM profesor WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $es_secretaria = ($row['ocupacion'] === 'secretaria');
        }
        $stmt->close();
    }
}

if (!$es_secretaria && $usuario_cargo !== 'directiva') {
    header('Location: ../index.php');
    exit();
}

$mensaje = '';
$error = '';

// Procesar ingreso de nuevo estudiante
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'])) {
    if ($_POST['accion'] === 'ingresar') {
        $nombre = $_POST['nombre'] ?? '';
        $apellidos = $_POST['apellidos'] ?? '';
        $ci = $_POST['ci'] ?? '';
        $grado = $_POST['grado'] ?? '10mo';
        $peloton = (int)($_POST['peloton'] ?? 1);
        $password = $_POST['password'] ?? $ci;
        
        if (!empty($nombre) && !empty($apellidos) && !empty($ci)) {
            // Verificar si ya existe
            $check = $conexion->prepare("SELECT id FROM estudiante WHERE CI = ?");
            $check->bind_param("s", $ci);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $error = "Ya existe un estudiante con ese CI";
            } else {
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                
                // Verificar qué campos existen en la tabla estudiante
                $columns = $conexion->query("SHOW COLUMNS FROM estudiante");
                $campos = [];
                while ($col = $columns->fetch_assoc()) {
                    $campos[] = $col['Field'];
                }
                
                // Construir INSERT dinámico
                $fields = ['nombre', 'apellidos', 'CI', 'password', 'grado', 'peloton'];
                $values = [$nombre, $apellidos, $ci, $password_hash, $grado, $peloton];
                $types = "sssssi";
                
                // Agregar activo si existe
                if (in_array('activo', $campos)) {
                    $fields[] = 'activo';
                    $values[] = 1;
                    $types .= "i";
                }
                
                $placeholders = implode(',', array_fill(0, count($fields), '?'));
                $fields_str = implode(',', $fields);
                
                $sql = "INSERT INTO estudiante ($fields_str) VALUES ($placeholders)";
                $stmt = $conexion->prepare($sql);
                $stmt->bind_param($types, ...$values);
                
                if ($stmt->execute()) {
                    $mensaje = "Estudiante ingresado correctamente";
                } else {
                    $error = "Error al ingresar estudiante";
                }
                $stmt->close();
            }
            $check->close();
        } else {
            $error = "Todos los campos son obligatorios";
        }
    } elseif ($_POST['accion'] === 'baja' && isset($_POST['estudiante_id'])) {
        $id = (int)$_POST['estudiante_id'];
        
        // Verificar si existe el campo activo
        $check = $conexion->query("SHOW COLUMNS FROM estudiante LIKE 'activo'");
        if ($check && $check->num_rows > 0) {
            $sql = "UPDATE estudiante SET activo = 0 WHERE id = ?";
        } else {
            // Si no existe activo, eliminar físicamente
            $sql = "DELETE FROM estudiante WHERE id = ?";
        }
        
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $mensaje = "Estudiante dado de baja";
    } elseif ($_POST['accion'] === 'reactivar' && isset($_POST['estudiante_id'])) {
        $id = (int)$_POST['estudiante_id'];
        
        $check = $conexion->query("SHOW COLUMNS FROM estudiante LIKE 'activo'");
        if ($check && $check->num_rows > 0) {
            $sql = "UPDATE estudiante SET activo = 1 WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
        }
        $mensaje = "Estudiante reactivado";
    }
}

// Búsqueda
$busqueda = $_GET['q'] ?? '';
$mostrar_bajas = isset($_GET['bajas']) && $_GET['bajas'] === '1';
$resultados = [];

if (!empty($busqueda)) {
    $busqueda_param = "%$busqueda%";
    
    // Verificar si existe el campo activo
    $check = $conexion->query("SHOW COLUMNS FROM estudiante LIKE 'activo'");
    $tiene_activo = ($check && $check->num_rows > 0);
    
    if ($tiene_activo) {
        $activo_valor = $mostrar_bajas ? 0 : 1;
        $sql = "SELECT id, nombre, apellidos, CI, grado, peloton, activo 
                FROM estudiante 
                WHERE activo = ? AND (nombre LIKE ? OR apellidos LIKE ? OR CI LIKE ?)
                LIMIT 30";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("isss", $activo_valor, $busqueda_param, $busqueda_param, $busqueda_param);
    } else {
        $sql = "SELECT id, nombre, apellidos, CI, grado, peloton, 1 as activo 
                FROM estudiante 
                WHERE nombre LIKE ? OR apellidos LIKE ? OR CI LIKE ?
                LIMIT 30";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("sss", $busqueda_param, $busqueda_param, $busqueda_param);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $resultados[] = $row;
    }
    $stmt->close();
}

$grados = ['10mo', '11no', '12mo'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Panel de Secretaria</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../iconos_vectoriales.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); border: none; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .main-content { padding: 20px 16px; max-width: 700px; margin: 0 auto; }
        .section-card { background: white; border-radius: 20px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .section-title { color: #1e3c72; font-size: 1.2em; font-weight: 700; margin-bottom: 16px; }
        .mensaje-exito { background: #d1fae5; color: #065f46; padding: 12px; border-radius: 12px; margin-bottom: 15px; }
        .mensaje-error { background: #fee2e2; color: #991b1b; padding: 12px; border-radius: 12px; margin-bottom: 15px; }
        .form-group { margin-bottom: 15px; }
        .form-group input, .form-group select { width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 14px; font-family: 'Poppins', sans-serif; }
        .form-row { display: flex; gap: 10px; }
        .form-row .form-group { flex: 1; }
        .btn-primary { width: 100%; padding: 16px; background: #10b981; color: white; border: none; border-radius: 14px; font-weight: 600; cursor: pointer; }
        .search-box { display: flex; gap: 10px; margin-bottom: 15px; }
        .search-box input { flex: 1; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 14px; }
        .search-box button { padding: 14px 20px; background: #1e3c72; color: white; border: none; border-radius: 14px; cursor: pointer; }
        .estudiante-card { background: #f8fafc; border-radius: 14px; padding: 14px; margin-bottom: 10px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; }
        .estudiante-info { flex: 1; }
        .estudiante-nombre { font-weight: 600; color: #1e293b; }
        .estudiante-detalles { font-size: 0.85em; color: #64748b; }
        .estudiante-baja { opacity: 0.7; background: #fef2f2; }
        .btn-baja { background: #ef4444; color: white; border: none; padding: 8px 14px; border-radius: 10px; cursor: pointer; }
        .btn-reactivar { background: #10b981; color: white; border: none; padding: 8px 14px; border-radius: 10px; cursor: pointer; }
        .toggle-bajas { margin-top: 10px; }
        .toggle-bajas a { color: #1e3c72; text-decoration: none; }
        .empty-state { text-align: center; padding: 30px; color: #94a3b8; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../../index.php" class="back-btn">←</a>
        <h1 class="header-title">Panel de Secretaria</h1>
    </header>

    <main class="main-content">
        <?php if ($mensaje): ?>
        <div class="mensaje-exito">✅ <?php echo $mensaje; ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
        <div class="mensaje-error">❌ <?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Formulario de ingreso -->
        <div class="section-card">
            <h2 class="section-title">➕ Ingresar Nuevo Estudiante</h2>
            <form method="POST">
                <input type="hidden" name="accion" value="ingresar">
                <div class="form-group">
                    <input type="text" name="nombre" placeholder="Nombre" required>
                </div>
                <div class="form-group">
                    <input type="text" name="apellidos" placeholder="Apellidos" required>
                </div>
                <div class="form-group">
                    <input type="text" name="ci" placeholder="Carnet de Identidad (CI)" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <select name="grado" required>
                            <?php foreach ($grados as $g): ?>
                            <option value="<?php echo $g; ?>"><?php echo $g; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <input type="number" name="peloton" placeholder="Pelotón" value="1" min="1" required>
                    </div>
                </div>
                <div class="form-group">
                    <input type="text" name="password" placeholder="Contraseña (por defecto CI)">
                </div>
                <button type="submit" class="btn-primary">Ingresar Estudiante</button>
            </form>
        </div>
        
        <!-- Búsqueda -->
        <div class="section-card">
            <h2 class="section-title">🔍 Buscar Estudiante</h2>
            <form method="GET">
                <div class="search-box">
                    <input type="text" name="q" placeholder="Nombre, apellidos o CI..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit">🔎</button>
                </div>
                <div class="toggle-bajas">
                    <a href="?q=<?php echo urlencode($busqueda); ?>&bajas=<?php echo $mostrar_bajas ? '0' : '1'; ?>">
                        <?php echo $mostrar_bajas ? '📋 Ver estudiantes activos' : '📂 Ver estudiantes dados de baja'; ?>
                    </a>
                </div>
            </form>
            
            <?php if (!empty($busqueda)): ?>
                <?php if (empty($resultados)): ?>
                <div class="empty-state">No se encontraron estudiantes</div>
                <?php else: ?>
                    <?php foreach ($resultados as $est): ?>
                    <div class="estudiante-card <?php echo empty($est['activo']) ? 'estudiante-baja' : ''; ?>">
                        <div class="estudiante-info">
                            <div class="estudiante-nombre"><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellidos']); ?></div>
                            <div class="estudiante-detalles">
                                CI: <?php echo htmlspecialchars($est['CI']); ?> | 
                                <?php echo htmlspecialchars($est['grado']); ?>, Pelotón <?php echo $est['peloton']; ?>
                                <?php if (isset($est['activo']) && !$est['activo']): ?> | <span style="color: #ef4444;">⚠️ Dado de baja</span><?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <?php if (!isset($est['activo']) || $est['activo']): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="accion" value="baja">
                                <input type="hidden" name="estudiante_id" value="<?php echo $est['id']; ?>">
                                <button type="submit" class="btn-baja" onclick="return confirm('¿Dar de baja a este estudiante?')">❌ Dar de baja</button>
                            </form>
                            <?php else: ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="accion" value="reactivar">
                                <input type="hidden" name="estudiante_id" value="<?php echo $est['id']; ?>">
                                <button type="submit" class="btn-reactivar" onclick="return confirm('¿Reactivar a este estudiante?')">✅ Reactivar</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">Ingresa un término de búsqueda</div>
            <?php endif; ?>
        </div>
    </main>
</body>
</html>