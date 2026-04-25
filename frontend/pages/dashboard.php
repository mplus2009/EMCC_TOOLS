<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../login.php');
    exit();
}

// Incluir la conexión a la base de datos
require_once '../db.php';

// Incluir el procesador
require_once 'dashboard_procesar.php';

// Incluir la vista
require_once 'dashboard_vista.php';
?>