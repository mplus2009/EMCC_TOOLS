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

// Solo directiva puede acceder
if ($usuario_cargo !== 'directiva') {
    header('Location: ../index.php');
    exit();
}

// Valores por defecto
$limites = [
    '10mo' => 15,
    '11no' => 11,
    '12mo' => 10
];

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $limite_10mo = (int)($_POST['limite_10mo'] ?? 15);
    $limite_11no = (int)($_POST['limite_11no'] ?? 11);
    $limite_12mo = (int)($_POST['limite_12mo'] ?? 10);
    
    // Guardar en archivo de configuración o base de datos
    $config = "<?php\nreturn [" . 
              "'limite_10mo' => $limite_10mo, " .
              "'limite_11no' => $limite_11no, " .
              "'limite_12mo' => $limite_12mo" .
              "];\n?>";
    file_put_contents('alarma_config.php', $config);
    
    $mensaje = 'Configuración guardada correctamente';
    $limites = ['10mo' => $limite_10mo, '11no' => $limite_11no, '12mo' => $limite_12mo];
} elseif (file_exists('alarma_config.php')) {
    $limites = include 'alarma_config.php';
}

$mensaje = $mensaje ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Editar Reglas - Directiva</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .header { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); padding: 16px 20px; display: flex; align-items: center; position: sticky; top: 0; z-index: 100; }
        .back-btn { background: rgba(255,255,255,0.15); border: none; width: 44px; height: 44px; border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white; font-size: 22px; text-decoration: none; margin-right: 16px; }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .main-content { padding: 20px 16px; max-width: 500px; margin: 0 auto; }
        .form-card { background: white; border-radius: 20px; padding: 24px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); }
        .form-title { color: #1e3c72; font-size: 1.2em; font-weight: 700; margin-bottom: 20px; }
        .mensaje-exito { background: #d1fae5; color: #065f46; padding: 14px; border-radius: 14px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; color: #2a5298; font-weight: 600; margin-bottom: 8px; }
        .form-group input { width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 14px; font-size: 1.1em; font-family: 'Poppins', sans-serif; }
        .form-group small { display: block; color: #64748b; margin-top: 5px; font-size: 0.85em; }
        .btn-submit { width: 100%; padding: 16px; background: #10b981; color: white; border: none; border-radius: 14px; font-size: 1.1em; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .info-card { background: #f8fafc; border-radius: 14px; padding: 16px; margin-top: 20px; }
        .info-card p { color: #64748b; font-size: 0.9em; }
    </style>
</head>
<body>
    <header class="header">
        <a href="../index.php" class="back-btn">←</a>
        <h1 class="header-title">Editar Reglas</h1>
    </header>

    <main class="main-content">
        <div class="form-card">
            <h2 class="form-title">⚙️ Límites de Alarma por Grado</h2>
            
            <?php if ($mensaje): ?>
            <div class="mensaje-exito">✅ <?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>10mo Grado</label>
                    <input type="number" name="limite_10mo" value="<?php echo $limites['10mo']; ?>" min="1" max="50">
                    <small>La alarma se activa cuando el balance negativo alcanza este valor</small>
                </div>
                <div class="form-group">
                    <label>11no Grado</label>
                    <input type="number" name="limite_11no" value="<?php echo $limites['11no']; ?>" min="1" max="50">
                    <small>La alarma se activa cuando el balance negativo alcanza este valor</small>
                </div>
                <div class="form-group">
                    <label>12mo Grado</label>
                    <input type="number" name="limite_12mo" value="<?php echo $limites['12mo']; ?>" min="1" max="50">
                    <small>La alarma se activa cuando el balance negativo alcanza este valor</small>
                </div>
                
                <button type="submit" class="btn-submit">💾 Guardar Cambios</button>
            </form>
            
            <div class="info-card">
                <p><strong>📌 ¿Cómo funciona?</strong></p>
                <p>Cuando un estudiante tiene más deméritos que méritos (balance negativo) y ese valor alcanza o supera el límite de su grado, se activa el modo alerta en su dashboard.</p>
            </div>
        </div>
    </main>
</body>
</html>