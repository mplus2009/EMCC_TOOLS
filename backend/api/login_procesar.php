<?php
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtoupper(eliminarTildes($_POST['nombre'] ?? ''));
    $apellidos = strtoupper(eliminarTildes($_POST['apellidos'] ?? ''));
    $ci = strtoupper(eliminarTildes($_POST['ci'] ?? ''));
    $password = $_POST['password'] ?? '';
    $cargo = $_POST['cargo'] ?? '';
    
    if ($nombre && $apellidos && $ci && $password && $cargo) {
        
        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];
        
        if (in_array($cargo, $tablas_permitidas)) {
            
            $sql = "SELECT * FROM $cargo WHERE 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(nombre, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u'), 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U')) = ? 
                    AND 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(apellidos, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u'), 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U')) = ?
                    AND 
                    UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(ci, 'á','a'), 'é','e'), 'í','i'), 'ó','o'), 'ú','u'), 'Á','A'), 'É','E'), 'Í','I'), 'Ó','O'), 'Ú','U')) = ?";
            
            $stmt = $conexion->prepare($sql);
            
            if ($stmt) {
                $stmt->bind_param("sss", $nombre, $apellidos, $ci);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if ($resultado->num_rows === 1) {
                    $usuario = $resultado->fetch_assoc();
                    
                    $password_valida = false;
                    
                    if (password_verify($password, $usuario['password'])) {
                        $password_valida = true;
                    } elseif ($password === $usuario['password']) {
                        $password_valida = true;
                    }
                    
                    if ($password_valida) {
                        $_SESSION['usuario_id'] = $usuario['id'];
                        $_SESSION['usuario_nombre'] = $usuario['nombre'];
                        $_SESSION['usuario_apellidos'] = $usuario['apellidos'];
                        $_SESSION['usuario_cargo'] = $cargo;
                        $_SESSION['logueado'] = true;
                        
                        session_write_close();
                        
                        header('Location: Dashboard/index.php');
                        exit();
                    } else {
                        $error = 'Contraseña incorrecta';
                    }
                } else {
                    $error = 'Usuario no encontrado';
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
?>