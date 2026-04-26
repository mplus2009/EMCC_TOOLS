<?php
// 1. Configuración de la Base de Datos
$host     = 'localhost';
$db_name  = 'if0_41266869_usuarios_escuela';
$db_user  = 'if0_41266869';
$db_pass  = 'mplus2009';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// 2. Iniciar sesión para mantener al usuario conectado
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ci = $_POST['ci'] ?? '';
    $password_ingresada = $_POST['password'] ?? '';

    if (!empty($ci) && !empty($password_ingresada)) {
        
        // Buscamos al usuario por su Carnet de Identidad (CI)
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE ci = ? LIMIT 1");
        $stmt->execute([$ci]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario) {
            $password_db = $usuario['password']; // La que está en la DB
            $login_exitoso = false;

            // Verificamos si es un hash de PHP o texto plano
            if (password_verify($password_ingresada, $password_db)) {
                $login_exitoso = true;
            } elseif ($password_ingresada === $password_db) {
                $login_exitoso = true;
            }

            if ($login_exitoso) {
                // Guardamos datos importantes en la sesión
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['nombre']     = $usuario['nombre'];
                $_SESSION['cargo']      = $usuario['cargo'];
                $_SESSION['ultimo_acceso'] = time();

                // Redirigir al Dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                echo "<script>alert('Contraseña incorrecta'); window.history.back();</script>";
            }
        } else {
            echo "<script>alert('El usuario no existe'); window.history.back();</script>";
        }
    }
}
?>
