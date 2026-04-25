<?php
// ============================================
// CONFIGURACIÓN DE SESIÓN - MANTENER LOGIN
// ============================================

// Tiempo de vida de la sesión en segundos (8 horas = 28800 segundos)
$tiempo_vida = 28800; // 8 horas

// Configurar el tiempo de vida de la cookie de sesión
ini_set('session.cookie_lifetime', $tiempo_vida);
ini_set('session.gc_maxlifetime', $tiempo_vida);

// Configurar la ruta de guardado de sesiones (para KSWEB en Android)
if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    $session_path = '/data/data/ru.kslabs.ksweb/components/php/session';
    if (is_dir($session_path) && is_writable($session_path)) {
        session_save_path($session_path);
    }
}

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Regenerar ID de sesión cada 30 minutos para mayor seguridad
if (!isset($_SESSION['ultima_regeneracion'])) {
    $_SESSION['ultima_regeneracion'] = time();
} elseif (time() - $_SESSION['ultima_regeneracion'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['ultima_regeneracion'] = time();
}

// Verificar si la sesión ha expirado por inactividad
if (isset($_SESSION['ultimo_acceso'])) {
    $tiempo_inactivo = time() - $_SESSION['ultimo_acceso'];
    if ($tiempo_inactivo > $tiempo_vida) {
        // Sesión expirada
        session_unset();
        session_destroy();
    }
}

// Actualizar tiempo de último acceso
$_SESSION['ultimo_acceso'] = time();
?>