<?php
header('Content-Type: application/json');
// Configuración CORS - IMPORTANTE: Cambia esto por tu dominio de GitHub Pages
header('Access-Control-Allow-Origin: *'); // En producción, cambia '*' por 'https://tu-usuario.github.io'
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
header('Access-Control-Allow-Credentials: true');

// Manejar preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/db.php';

session_start();

$response = ['success' => false, 'message' => '', 'redirect' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = strtoupper(eliminarTildes($_POST['nombre'] ?? ''));
    $apellidos = strtoupper(eliminarTildes($_POST['apellidos'] ?? ''));
    $ci = strtoupper(eliminarTildes($_POST['ci'] ?? ''));
    $password = $_POST['password'] ?? '';
    $cargo = $_POST['cargo'] ?? '';

    if ($nombre && $apellidos && $ci && $password && $cargo) {

        $tablas_permitidas = ['directiva', 'oficial', 'profesor', 'estudiante'];

        if (in_array($cargo, $tablas_permitidas)) {

            $conexion = getConexion();
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
                        $_SESSION['usuario_ci'] = $usuario['ci'] ?? '';
                        $_SESSION['usuario_cargo'] = $cargo;
                        $_SESSION['logueado'] = true;

                        session_write_close();

                        $response['success'] = true;
                        $response['message'] = 'Login exitoso';
                        $response['redirect'] = 'dashboard.html';
                        $response['usuario'] = [
                            'id' => $usuario['id'],
                            'nombre' => $usuario['nombre'],
                            'apellidos' => $usuario['apellidos'],
                            'ci' => $usuario['ci'] ?? '',
                            'cargo' => $cargo
                        ];
                    } else {
                        $response['message'] = 'Contraseña incorrecta';
                    }
                } else {
                    $response['message'] = 'Usuario no encontrado';
                }
                $stmt->close();
            } else {
                $response['message'] = 'Error en la consulta';
            }
        } else {
            $response['message'] = 'Cargo no válido';
        }
    } else {
        $response['message'] = 'Todos los campos son obligatorios';
    }
} else {
    $response['message'] = 'Método no permitido';
}

echo json_encode($response);
?>
