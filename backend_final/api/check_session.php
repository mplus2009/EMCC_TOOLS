<?php
session_start(); // Activa el uso de sesiones

// Preguntamos: "¿Existe la pulsera de acceso en el sistema?"
if (!isset($_SESSION['usuario_id'])) {
    // Si NO existe, lo mandamos de vuelta a la página de login
    header("Location: index.html"); 
    exit();
}
?>
