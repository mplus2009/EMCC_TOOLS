<?php
require_once 'config_sesion.php';

if (isset($_SESSION['logueado']) && $_SESSION['logueado'] === true) {
    header('Location: Dashboard/');
    exit();
} else {
    header('Location: login.php');
    exit();
}
?>