<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../login.php');
    exit();
}

require_once '../db.php';

$usuario_id = $_SESSION['usuario_id'];
$usuario_cargo = $_SESSION['usuario_cargo'];
$usuario_nombre = $_SESSION['usuario_nombre'];
$usuario_apellidos = $_SESSION['usuario_apellidos'];

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_actual = $_POST['password_actual'] ?? '';
    $password_nueva = $_POST['password_nueva'] ?? '';
    $password_confirmar = $_POST['password_confirmar'] ?? '';
    
    if (empty($password_actual) || empty($password_nueva) || empty($password_confirmar)) {
        $error = 'Todos los campos son obligatorios';
    } elseif ($password_nueva !== $password_confirmar) {
        $error = 'Las contraseñas nuevas no coinciden';
    } elseif (strlen($password_nueva) < 4) {
        $error = 'La contraseña debe tener al menos 4 caracteres';
    } else {
        $sql = "SELECT password FROM $usuario_cargo WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("i", $usuario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        
        $password_valida = false;
        if (password_verify($password_actual, $row['password']) || $password_actual === $row['password']) {
            $password_valida = true;
        }
        
        if (!$password_valida) {
            $error = 'La contraseña actual es incorrecta';
        } else {
            $password_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
            $sql = "UPDATE $usuario_cargo SET password = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("si", $password_hash, $usuario_id);
            
            if ($stmt->execute()) {
                $mensaje = 'Contraseña actualizada correctamente';
            } else {
                $error = 'Error al actualizar la contraseña';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Configuración - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #f0f4f8; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .back-btn {
            background: rgba(255,255,255,0.15);
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            text-decoration: none;
            margin-right: 16px;
        }
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .config-container { padding: 24px 18px; max-width: 500px; margin: 0 auto; }
        .config-card {
            background: white;
            border-radius: 24px;
            padding: 28px 22px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .config-titulo {
            color: #1e3c72;
            font-size: 1.3em;
            font-weight: 700;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mensaje-exito {
            background: #d1fae5;
            color: #065f46;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .mensaje-error {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            color: #2a5298;
            font-size: 0.9em;
            font-weight: 600;
            margin-bottom: 6px;
        }
        .form-control {
            width: 100%;
            padding: 15px 16px;
            font-size: 1em;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            font-family: 'Poppins', sans-serif;
        }
        .form-control:focus { outline: none; border-color: #2a5298; }
        .btn-submit {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            margin-top: 10px;
        }
        .info-usuario {
            background: #f8fafc;
            padding: 16px;
            border-radius: 14px;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .info-usuario i { font-size: 40px; color: #667eea; }
        .info-usuario div { display: flex; flex-direction: column; }
        .info-usuario strong { color: #1e293b; font-size: 1.1em; }
        .info-usuario span { color: #64748b; font-size: 0.9em; }
    </style>
</head>
<body>
    <header class="header">
        <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="header-title">Configuración</h1>
    </header>

    <main class="config-container">
        <div class="config-card">
            <h2 class="config-titulo"><i class="fas fa-lock" style="color: #667eea;"></i> Cambiar Contraseña</h2>
            
            <div class="info-usuario">
                <i class="fas fa-user-circle"></i>
                <div>
                    <strong><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellidos); ?></strong>
                    <span><?php echo ucfirst($usuario_cargo); ?></span>
                </div>
            </div>
            
            <?php if ($mensaje): ?>
            <div class="mensaje-exito"><i class="fas fa-check-circle"></i> <?php echo $mensaje; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="mensaje-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Contraseña Actual</label>
                    <input type="password" name="password_actual" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Nueva Contraseña</label>
                    <input type="password" name="password_nueva" class="form-control" placeholder="••••••••" required>
                </div>
                <div class="form-group">
                    <label>Confirmar Nueva Contraseña</label>
                    <input type="password" name="password_confirmar" class="form-control" placeholder="••••••••" required>
                </div>
                <button type="submit" class="btn-submit"><i class="fas fa-save"></i> Guardar Cambios</button>
            </form>
        </div>
    </main>
</body>
</html>