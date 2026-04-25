<?php
// ============================================
// LOGIN_PROCESAR.PHP - Procesar login (con CORS)
// ============================================

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start();

require_once '../config/db.php';

$error = '';

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
                    AND 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u')) = ?";
            
            $stmt = $conexion->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("ss", $nombre, $apellidos);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
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
                    
                    header('Location: ../../frontend/pages/dashboard.html');
                    exit();
                } else {
                    $error = 'Usuario no encontrado o contraseña incorrecta';
                }
                $stmt->close();
            } else {
                $error = 'Error en la consulta: ' . $conexion->error;
            }
        } else {
            $error = 'Cargo no válido';
        }
    } else {
        $error = 'Todos los campos son obligatorios';
    }
}

// Si hay error, devolver JSON
if ($error) {
    http_response_code(401);
    echo json_encode(['error' => $error]);
    exit();
}
?>
