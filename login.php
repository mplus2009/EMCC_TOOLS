<?php
session_start();

if (isset($_SESSION['usuario_id']) && isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    header('Location: Dashboard/');
    exit();
}

require_once 'db.php';

$error = '';

// Verificar si viene de escaneo QR
if (isset($_GET['qr']) && !empty($_GET['qr'])) {
    $qr_text = $_GET['qr'];
    $usuario_qr = null;
    
    $qr_text = urldecode($qr_text);
    
    try { 
        $json = base64_decode($qr_text);
        $usuario_qr = json_decode($json, true);
    } catch (Exception $e) {}
    
    if (!$usuario_qr) {
        try { 
            $json = base64_decode(str_replace(['-', '_'], ['+', '/'], $qr_text));
            $usuario_qr = json_decode($json, true);
        } catch (Exception $e) {}
    }
    
    if ($usuario_qr && isset($usuario_qr['id']) && isset($usuario_qr['cargo'])) {
        $id = $usuario_qr['id'];
        $cargo = $usuario_qr['cargo'];
        
        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
        
        if (in_array($cargo, $tablas_permitidas)) {
            $sql = "SELECT * FROM $cargo WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows === 1) {
                    $usuario = $resultado->fetch_assoc();
                    
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                    $_SESSION['usuario_ci'] = $usuario['ci'] ?? '';
                    $_SESSION['usuario_cargo'] = $cargo;
                    $_SESSION['logueado'] = true;
                    
                    session_write_close();
                    header('Location: Dashboard/');
                    exit();
                }
                $stmt->close();
            }
        }
    }
    
    if ($usuario_qr && isset($usuario_qr['nombre']) && isset($usuario_qr['apellidos'])) {
        $nombre = strtoupper(eliminarTildes($usuario_qr['nombre']));
        $apellidos = strtoupper(eliminarTildes($usuario_qr['apellidos']));
        $cargo = $usuario_qr['cargo'] ?? 'estudiante';
        
        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
        
        if (in_array($cargo, $tablas_permitidas)) {
            $sql = "SELECT * FROM $cargo WHERE 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ? 
                    AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?";
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $nombre, $apellidos);
                $stmt->execute();
                $resultado = $stmt->get_result();
                if ($resultado->num_rows === 1) {
                    $usuario = $resultado->fetch_assoc();
                    $_SESSION['usuario_id'] = $usuario['id'];
                    $_SESSION['usuario_nombre'] = $usuario['nombre'];
                    $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                    $_SESSION['usuario_ci'] = $usuario['ci'] ?? '';
                    $_SESSION['usuario_cargo'] = $cargo;
                    $_SESSION['logueado'] = true;
                    session_write_close();
                    header('Location: Dashboard/');
                    exit();
                }
                $stmt->close();
            }
        }
    }
    
    $error = 'QR no válido o usuario no encontrado';
}

// Procesar login manual (SIN CI)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtoupper(eliminarTildes($_POST['nombre'] ?? ''));
    $apellidos = strtoupper(eliminarTildes($_POST['apellidos'] ?? ''));
    $password = $_POST['password'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    
    if ($nombre && $apellidos && $password && $cargo) {
        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
        if (in_array($cargo, $tablas_permitidas)) {
            $sql = "SELECT * FROM $cargo WHERE 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ? 
                    AND UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?";
            $stmt = $conexion->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("ss", $nombre, $apellidos);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                // Puede haber múltiples usuarios con el mismo nombre (ej: Juan Pérez)
                // Verificar contraseña para cada uno
                $usuario_encontrado = null;
                while ($row = $resultado->fetch_assoc()) {
                    if (password_verify($password, $row['password']) || $password === $row['password']) {
                        $usuario_encontrado = $row;
                        break;
                    }
                }
                
                if ($usuario_encontrado) {
                    $_SESSION['usuario_id'] = $usuario_encontrado['id'];
                    $_SESSION['usuario_nombre'] = $usuario_encontrado['nombre'];
                    $_SESSION['usuario_apellidos'] = $usuario_encontrado['apellidos'];
                    $_SESSION['usuario_ci'] = $usuario_encontrado['ci'] ?? '';
                    $_SESSION['usuario_cargo'] = $cargo;
                    $_SESSION['logueado'] = true;
                    session_write_close();
                    header('Location: Dashboard/');
                    exit();
                } else {
                    $error = 'Usuario no encontrado o contraseña incorrecta';
                }
                $stmt->close();
            }
        } else {
            $error = 'Cargo no válido';
        }
    } else {
        $error = 'Todos los campos son obligatorios';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <title>Login - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 16px;
        }
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            border-radius: 30px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            padding: 40px 30px;
            width: 100%;
            max-width: 450px;
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp { 
            from { opacity: 0; transform: translateY(30px); } 
            to { opacity: 1; transform: translateY(0); } 
        }
        .login-header { 
            text-align: center; 
            margin-bottom: 35px; 
        }
        .login-header h1 { 
            color: #1e3c72; 
            font-size: 2em; 
            font-weight: 600; 
            margin-bottom: 8px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            gap: 10px; 
        }
        .login-header p { 
            color: #666; 
            font-size: 0.95em; 
        }
        
        /* Sección QR */
        .qr-login-section { 
            text-align: center; 
            margin-bottom: 25px; 
            padding-bottom: 25px; 
            border-bottom: 1px solid #e0e0e0; 
        }
        .btn-qr-login {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            border: none;
            padding: 16px 25px;
            border-radius: 50px;
            font-size: 1.15em;
            font-weight: 600;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            cursor: pointer;
            width: 100%;
            box-shadow: 0 8px 20px rgba(30, 60, 114, 0.3);
            transition: all 0.2s ease;
        }
        .btn-qr-login i { font-size: 1.4em; }
        .btn-qr-login:active { 
            transform: scale(0.97); 
            box-shadow: 0 4px 12px rgba(30, 60, 114, 0.2); 
        }
        
        /* Divisor */
        .qr-divider { 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 20px 0; 
            color: #888; 
            font-size: 0.9em; 
        }
        .qr-divider::before, .qr-divider::after { 
            content: ''; 
            flex: 1; 
            height: 1px; 
            background: #e0e0e0; 
        }
        .qr-divider span { padding: 0 15px; }
        
        /* Formulario */
        .form-group { margin-bottom: 22px; }
        .form-group label {
            display: block;
            color: #2a5298;
            font-size: 0.9em;
            font-weight: 500;
            margin-bottom: 6px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 15px 16px;
            font-size: 1em;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            background: white;
            font-family: 'Poppins', sans-serif;
        }
        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%232a5298' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            background-size: 16px;
            padding-right: 45px;
            -webkit-appearance: none;
            appearance: none;
        }
        .form-group input:focus, .form-group select:focus { 
            outline: none; 
            border-color: #2a5298; 
            box-shadow: 0 0 0 3px rgba(42, 82, 152, 0.1); 
        }
        
        /* Mensaje de error */
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 14px 16px;
            border-radius: 14px;
            margin-bottom: 25px;
            font-size: 0.95em;
            display: <?php echo $error ? 'block' : 'none'; ?>;
            border-left: 4px solid #dc2626;
            animation: shake 0.5s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        
        /* Botón login */
        .btn-login {
            width: 100%;
            padding: 16px;
            font-size: 1.15em;
            font-weight: 600;
            color: white;
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            border: none;
            border-radius: 14px;
            cursor: pointer;
            margin-top: 10px;
            -webkit-tap-highlight-color: transparent;
            transition: all 0.2s ease;
        }
        .btn-login:active { transform: scale(0.98); }
        
        /* Nota informativa */
        .info-note { 
            margin-top: 20px; 
            text-align: center; 
            color: #888; 
            font-size: 0.85em; 
        }
    </style>
</head>
<body>
        <div class="login-container">
        <div class="login-header">
            <h1>
                <i class="fas fa-graduation-cap"></i>
                Acceso
            </h1>
            <p>Sistema de Gestión Escolar</p>
        </div>

        <!-- Sección de Login con QR -->
        <div class="qr-login-section">
            <button class="btn-qr-login" onclick="window.location.href='escaner_qr_login.php'">
                <i class="fas fa-qrcode"></i>
                Escanear QR para Iniciar Sesión
            </button>
        </div>

        <div class="qr-divider">
            <span>o ingresa manualmente</span>
        </div>

        <!-- Mensaje de error -->
        <div class="error-message"><?php echo $error ? htmlspecialchars($error) : ''; ?></div>

        <!-- Formulario de login manual (SIN CI) -->
        <form method="POST" action="">
            <div class="form-group">
                <label><i class="fas fa-user"></i> Nombre</label>
                <input type="text" name="nombre" required 
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                       placeholder="Ingresa tu nombre" 
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label><i class="fas fa-users"></i> Apellidos</label>
                <input type="text" name="apellidos" required 
                       value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>"
                       placeholder="Ingresa tus apellidos" 
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label><i class="fas fa-lock"></i> Contraseña</label>
                <input type="password" name="password" required 
                       placeholder="Ingresa tu contraseña">
            </div>

            <div class="form-group">
                <label><i class="fas fa-user-tag"></i> Cargo</label>
                <select name="cargo" required>
                    <option value="">Selecciona tu cargo</option>
                    <option value="directiva" <?php echo ($_POST['cargo'] ?? '') === 'directiva' ? 'selected' : ''; ?>>Directiva</option>
                    <option value="oficial" <?php echo ($_POST['cargo'] ?? '') === 'oficial' ? 'selected' : ''; ?>>Oficial</option>
                    <option value="profesor" <?php echo ($_POST['cargo'] ?? '') === 'profesor' ? 'selected' : ''; ?>>Profesor</option>
                    <option value="estudiante" <?php echo ($_POST['cargo'] ?? '') === 'estudiante' ? 'selected' : ''; ?>>Estudiante</option>
                </select>
            </div>

            <button type="submit" class="btn-login">Iniciar Sesión</button>
        </form>

        <div class="info-note">
            Ingresa con tu nombre, apellidos y contraseña
        </div>
    </div>
</body>
</html>