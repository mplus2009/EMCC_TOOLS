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

// ============================================
// RECIBIR DESTINATARIO POR URL
// ============================================
$destinatario_preseleccionado = null;

if (isset($_GET['destinatario']) && !empty($_GET['destinatario'])) {
    $destinatario_id = (int)$_GET['destinatario'];
    
    $sql = "SELECT id, nombre, apellidos, CI, grado FROM estudiante WHERE id = ?";
    $stmt = $conexion->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("i", $destinatario_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $row['ci'] = $row['CI'] ?? '';
            $destinatario_preseleccionado = $row;
        }
        $stmt->close();
    }
}

// ============================================
// OBTENER CATÁLOGOS
// ============================================
$catalogo_meritos = [];
$catalogo_demeritos = [];

$tabla_meritos = $conexion->query("SHOW TABLES LIKE 'meritos'");
if ($tabla_meritos && $tabla_meritos->num_rows > 0) {
    $result = $conexion->query("SELECT * FROM meritos ORDER BY categoria, causa");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $catalogo_meritos[] = $row;
        }
    }
}

$tabla_demeritos = $conexion->query("SHOW TABLES LIKE 'demeritos'");
if ($tabla_demeritos && $tabla_demeritos->num_rows > 0) {
    $result = $conexion->query("SELECT * FROM demeritos ORDER BY categoria, falta");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $catalogo_demeritos[] = $row;
        }
    }
}

// ============================================
// USUARIOS ESCANEADOS
// ============================================
$usuarios_escaneados = [];
if (isset($_GET['escaneados']) && !empty($_GET['escaneados'])) {
    $decodificado = base64_decode($_GET['escaneados']);
    $usuarios_escaneados = json_decode($decodificado, true);
    if (!is_array($usuarios_escaneados)) {
        $usuarios_escaneados = [];
    }
}

if ($destinatario_preseleccionado && empty($usuarios_escaneados)) {
    $usuarios_escaneados[] = $destinatario_preseleccionado;
}

require_once 'notificar_vista.php';
?>