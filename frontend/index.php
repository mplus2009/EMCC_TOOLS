<?php
// Frontend - Redireccionamiento inicial
// Este archivo verifica la sesión y redirige al login o dashboard

session_start();

if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    header('Location: pages/dashboard.php');
    exit();
} else {
    header('Location: pages/login.php');
    exit();
}
?>