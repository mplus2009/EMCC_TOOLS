<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <title>Login - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="login_estilos.css">
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

        <?php if ($error): ?>
        <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label>📛 Nombre</label>
                <input type="text" name="nombre" required 
                       value="<?php echo htmlspecialchars($_POST['nombre'] ?? ''); ?>"
                       placeholder="Ingresa tu nombre"
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label>👥 Apellidos</label>
                <input type="text" name="apellidos" required 
                       value="<?php echo htmlspecialchars($_POST['apellidos'] ?? ''); ?>"
                       placeholder="Ingresa tus apellidos"
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label>🆔 Carnet de Identidad</label>
                <input type="text" name="ci" required 
                       value="<?php echo htmlspecialchars($_POST['ci'] ?? ''); ?>"
                       placeholder="Ingresa tu CI"
                       autocomplete="off">
            </div>

            <div class="form-group">
                <label>🔒 Contraseña</label>
                <input type="password" name="password" required 
                       placeholder="Ingresa tu contraseña">
            </div>

            <div class="form-group">
                <label>👤 Cargo</label>
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
            Los datos serán validados automáticamente
        </div>
    </div>
</body>
</html>