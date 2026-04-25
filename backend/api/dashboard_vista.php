<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Dashboard - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="iconos_vectoriales.css">
    <link rel="stylesheet" href="dashboard_estilos.css">
    <style>
        /* ============================================ */
        /* ESTILOS DE ALARMA INCORPORADOS               */
        /* ============================================ */
        
        /* Banner de alarma */
        .alarma-banner {
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            box-shadow: 0 4px 15px rgba(220, 38, 38, 0.3);
            border-left: 5px solid #fecaca;
            animation: alarmaParpadeo 1.5s infinite;
        }
        
        .alarma-banner .icon-warning {
            font-size: 24px;
            animation: alarmaPulso 1s infinite;
        }
        
        .alarma-banner-content {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        
        .alarma-banner-content strong {
            font-size: 1em;
        }
        
        .alarma-banner-content span {
            font-size: 0.85em;
            opacity: 0.95;
        }
        
        /* Toast de alarma */
        .alarma-toast {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            background: linear-gradient(135deg, #dc2626 0%, #991b1b 100%);
            color: white;
            padding: 15px 20px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 9998;
            box-shadow: 0 8px 25px rgba(220, 38, 38, 0.5);
            border-left: 5px solid #fecaca;
            max-width: 500px;
            margin: 0 auto;
            animation: toastSlideUp 0.4s ease;
        }
        
        @keyframes toastSlideUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .alarma-toast .icon-warning {
            font-size: 24px;
            animation: alarmaPulso 1s infinite;
        }
        
        .alarma-toast-content {
            flex: 1;
        }
        
        .alarma-toast-title {
            font-weight: 700;
            font-size: 1em;
            margin-bottom: 4px;
        }
        
        .alarma-toast-text {
            font-size: 0.85em;
            opacity: 0.95;
        }
        
        .alarma-toast-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 8px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s;
        }
        
        .alarma-toast-close:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        
        /* Badge de notificaciones */
        .notification-badge {
            background: #ef4444;
            color: white;
            font-size: 0.7em;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 20px;
            margin-left: auto;
            min-width: 20px;
            text-align: center;
        }
        
        /* Animaciones */
        @keyframes alarmaPulso {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
            50% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(239, 68, 68, 0.3); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
        }
        
        @keyframes alarmaParpadeo {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.85; }
        }
        
        /* Modo alerta en body */
        body.modo-alerta {
            position: relative;
        }
        
        body.modo-alerta::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #ef4444 0%, #dc2626 50%, #ef4444 100%);
            z-index: 99999;
            animation: alarmaParpadeo 1s infinite;
            box-shadow: 0 0 15px #ef4444;
        }
        
        body.modo-alerta .header {
            border-bottom: 2px solid #ef4444;
            box-shadow: 0 3px 12px rgba(239, 68, 68, 0.3);
        }
        
        body.modo-alerta .balance-card {
            animation: alarmaPulso 2s infinite;
            border: 2px solid #ef4444;
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
        }
        
        body.modo-alerta .balance-card .stat-icon {
            background: rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }
        
        body.modo-alerta .balance-card .stat-value {
            color: #dc2626 !important;
            font-weight: 900;
        }
        
        /* Botón Alegar */
        .btn-alegar {
            background: #f59e0b;
            color: white;
            border: none;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: background 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        
        .btn-alegar:active {
            background: #d97706;
        }
        
        .alegacion-badge {
            background: #dbeafe;
            color: #1e40af;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            cursor: pointer;
            transition: background 0.2s;
        }
        
        .alegacion-badge:active {
            background: #bfdbfe;
        }
        
        /* Modal Alegación */
        .modal-overlay-alegar {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            z-index: 10001;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .modal-alegar-content {
            background: white;
            border-radius: 24px;
            max-width: 450px;
            width: 100%;
            overflow: hidden;
            animation: tutorialSlideUp 0.3s ease;
        }
        
        .modal-alegar-header {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
            padding: 18px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-alegar-header h2 {
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-alegar-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            width: 36px;
            height: 36px;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .modal-alegar-body {
            padding: 20px;
        }
        
        .alegacion-motivo {
            margin-bottom: 15px;
            color: #475569;
            font-size: 0.9em;
        }
        
        .alegacion-textarea {
            width: 100%;
            padding: 14px;
            border: 2px solid #e2e8f0;
            border-radius: 14px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9em;
            resize: vertical;
            min-height: 100px;
        }
        
        .alegacion-textarea:focus {
            outline: none;
            border-color: #f59e0b;
        }
        
        .btn-alegar-enviar {
            width: 100%;
            padding: 14px;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 14px;
            font-weight: 600;
            font-size: 1em;
            cursor: pointer;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: background 0.2s;
        }
        
        .btn-alegar-enviar:active {
            background: #d97706;
        }
        
        .btn-alegar-enviar:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        /* Toast de éxito */
        .toast-exito {
            position: fixed;
            top: 20px;
            left: 50%;
            transform: translateX(-50%) translateY(-120px);
            background: white;
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 12px;
            z-index: 99999;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-left: 5px solid #10b981;
            max-width: 400px;
            width: 90%;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .toast-exito.show {
            transform: translateX(-50%) translateY(0);
        }
        
        .toast-exito-icon {
            width: 40px;
            height: 40px;
            background: #d1fae5;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
            color: #065f46;
            flex-shrink: 0;
        }
        
        .toast-exito-content {
            flex: 1;
        }
        
        .toast-exito-title {
            font-weight: 700;
            color: #065f46;
            font-size: 0.95em;
            margin-bottom: 2px;
        }
        
        .toast-exito-text {
            color: #64748b;
            font-size: 0.8em;
        }
        
        .toast-exito-close {
            background: #f1f5f9;
            border: none;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            color: #64748b;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        /* Alegación existente */
        .alegacion-existente-texto {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 14px;
            margin-bottom: 12px;
            color: #475569;
            font-size: 0.9em;
            line-height: 1.5;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .alegacion-ya-enviada {
            color: #10b981;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }
        
        /* Responsive */
        @media (max-width: 480px) {
            .alarma-banner {
                padding: 12px 14px;
            }
            
            .alarma-banner .icon-warning {
                font-size: 20px;
            }
            
            .alarma-banner-content strong {
                font-size: 0.9em;
            }
            
            .alarma-banner-content span {
                font-size: 0.75em;
            }
            
            .alarma-toast {
                padding: 12px 15px;
                bottom: 15px;
                left: 15px;
                right: 15px;
            }
            
            .alarma-toast .icon-warning {
                font-size: 20px;
            }
            
            .alarma-toast-title {
                font-size: 0.9em;
            }
            
            .alarma-toast-text {
                font-size: 0.75em;
            }
            
            .alarma-toast-close {
                width: 28px;
                height: 28px;
            }
            
            body.modo-alerta .balance-card {
                animation: alarmaPulso 1.5s infinite;
            }
            
            .notification-badge {
                font-size: 0.65em;
                padding: 2px 6px;
            }
            
            .toast-exito {
                padding: 12px 14px;
                top: 15px;
            }
        }
        
        @media (max-width: 360px) {
            .alarma-banner {
                padding: 10px 12px;
            }
            
            .alarma-banner .icon-warning {
                font-size: 18px;
            }
            
            .alarma-banner-content strong {
                font-size: 0.85em;
            }
            
            .alarma-banner-content span {
                font-size: 0.7em;
            }
            
            .alarma-toast {
                padding: 10px 12px;
                bottom: 10px;
                left: 10px;
                right: 10px;
            }
            
            .alarma-toast .icon-warning {
                font-size: 18px;
            }
            
            .alarma-toast-title {
                font-size: 0.85em;
            }
            
            .alarma-toast-text {
                font-size: 0.7em;
            }
        }
    </style>
</head>
<body class="<?php echo $usuario_cargo === 'estudiante' ? 'con-stats' : ''; ?>">
    <div class="overlay" id="overlay"></div>

    <?php if ($mostrar_tutorial): ?>
    <!-- TUTORIAL PARA PRIMERA VEZ -->
    <div class="tutorial-overlay" id="tutorialOverlay">
        <div class="tutorial-container">
            <div class="tutorial-header">
                <h2><span class="icon-info"></span> ¡Bienvenido al Sistema!</h2>
                <p>Te guiaremos por las funciones principales</p>
            </div>
            
            <div class="tutorial-steps">
                <div class="tutorial-step active" data-step="1">
                    <div class="tutorial-step-icon"><span class="icon-qr"></span></div>
                    <h3>Tu Código QR</h3>
                    <p>Toca el botón <span class="icon-qr"></span> para ver tu código QR personal. Puedes usarlo para iniciar sesión rápidamente.</p>
                </div>
                
                <div class="tutorial-step" data-step="2">
                    <div class="tutorial-step-icon"><span class="icon-bell"></span></div>
                    <h3>Notificar Actividades</h3>
                    <p>Usa el botón <strong>"Notificar"</strong> para registrar méritos o deméritos a los estudiantes.</p>
                </div>
                
                <div class="tutorial-step" data-step="3">
                    <div class="tutorial-step-icon"><span class="icon-user"></span></div>
                    <h3>Menú de Usuario</h3>
                    <p>Toca tu avatar <span class="icon-user"></span> para acceder a Mi Perfil, Configuración, Horario y más.</p>
                </div>
                
                <?php if ($usuario_cargo === 'estudiante'): ?>
                <div class="tutorial-step" data-step="4">
                    <div class="tutorial-step-icon"><span class="icon-balance"></span></div>
                    <h3>Tus Estadísticas</h3>
                    <p>Aquí verás tu acumulado de Méritos, Deméritos y Balance.</p>
                </div>
                
                <div class="tutorial-step" data-step="5">
                    <div class="tutorial-step-icon"><span class="icon-calendar"></span></div>
                    <h3>Actividades de la Semana</h3>
                    <p>Aquí aparecen todos los méritos y deméritos que has recibido.</p>
                </div>
                <?php else: ?>
                <div class="tutorial-step" data-step="4">
                    <div class="tutorial-step-icon"><span class="icon-search"></span></div>
                    <h3>Buscador de Estudiantes</h3>
                    <p>Busca estudiantes por nombre, apellidos o CI. Puedes reportar o ver su perfil.</p>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="tutorial-footer">
                <div class="tutorial-progress">
                    <span class="tutorial-dot active" data-step="1"></span>
                    <span class="tutorial-dot" data-step="2"></span>
                    <span class="tutorial-dot" data-step="3"></span>
                    <?php if ($usuario_cargo === 'estudiante'): ?>
                    <span class="tutorial-dot" data-step="4"></span>
                    <span class="tutorial-dot" data-step="5"></span>
                    <?php else: ?>
                    <span class="tutorial-dot" data-step="4"></span>
                    <?php endif; ?>
                </div>
                <div class="tutorial-buttons">
                    <button class="tutorial-btn-prev" id="tutorialPrev" disabled><span class="icon-arrow-left"></span> Anterior</button>
                    <button class="tutorial-btn-skip" id="tutorialSkip">Saltar Tutorial</button>
                    <button class="tutorial-btn-next" id="tutorialNext">Siguiente <span class="icon-arrow-right"></span></button>
                    <button class="tutorial-btn-finish" id="tutorialFinish" style="display: none;">¡Entendido! <span class="icon-check-circle"></span></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <header class="header">
        <div class="header-left">
            <button class="qr-btn" id="qrBtn"><span class="icon-qr"></span></button>
            <?php if ($usuario_cargo !== 'estudiante' || ($usuario_cargo === 'estudiante' && $ocupacion_usuario !== 'ninguno' && $ocupacion_usuario !== '')): ?>
            <a href="notificar.php" class="notificar-btn">
                <span class="icon-bell"></span> Notificar
            </a>
            <?php endif; ?>
        </div>
        <div class="header-title">
            <span class="greeting">Hola, <?php echo htmlspecialchars($usuario_nombre); ?></span>
        </div>
        <div class="header-right">
            <button class="account-btn" id="accountBtn">
                <span class="icon-user"></span>
                <span class="icon-chevron-down"></span>
            </button>
            <div class="account-dropdown" id="accountDropdown">
                <div class="dropdown-header">
                    <div class="dropdown-avatar"><span class="icon-user"></span></div>
                    <h3><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellidos); ?></h3>
                    <p>
                        <?php echo ucfirst($usuario_cargo); ?>
                        <?php if (!empty($ocupacion_usuario) && $ocupacion_usuario !== 'ninguno'): ?>
                        <br><small><?php echo str_replace('_', ' ', ucfirst($ocupacion_usuario)); ?></small>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="dropdown-menu">
                    <a href="perfil.php" class="dropdown-item"><span class="icon-user"></span> Mi Perfil</a>
                    <a href="configuracion.php" class="dropdown-item"><span class="icon-settings"></span> Configuración</a>
                    
                    <?php 
                    $mostrar_notificaciones = true;
                    if ($usuario_cargo === 'estudiante' && (empty($ocupacion_usuario) || $ocupacion_usuario === 'ninguno')) {
                        $mostrar_notificaciones = false;
                    }
                    if ($mostrar_notificaciones): 
                    ?>
                    <a href="funciones/mis_notificaciones.php" class="dropdown-item">
                        <span class="icon-bell"></span> Mis Notificaciones
                        <?php if (isset($nuevas_actividades) && $nuevas_actividades > 0): ?>
                        <span class="notification-badge"><?php echo $nuevas_actividades; ?></span>
                        <?php endif; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($usuario_cargo === 'profesor'): ?>
                    <a href="funciones/horario/profesor_horario.php" class="dropdown-item"><span class="icon-calendar"></span> Mi Horario</a>
                    <?php else: ?>
                    <a href="funciones/horario/" class="dropdown-item"><span class="icon-calendar"></span> Horario</a>
                    <?php endif; ?>
                    
                    <?php if ($usuario_cargo === 'directiva'): ?>
                    <a href="funciones/horario/config.php" class="dropdown-item"><span class="icon-edit-horario"></span> Editar Horario</a>
                    <a href="funciones/editar_reglas.php" class="dropdown-item"><span class="icon-edit-reglas"></span> Editar Reglas</a>
                    <a href="funciones/cambiar_cargos.php" class="dropdown-item"><span class="icon-cambiar-cargos"></span> Cambiar Cargos</a>
                    <?php endif; ?>
                    
                    <?php if ($usuario_cargo === 'oficial'): ?>
                    <a href="funciones/cambio_mando.php" class="dropdown-item"><span class="icon-cambio-mando"></span> Cambio de Mando</a>
                    <?php endif; ?>
                    
                    <?php if ($es_secretaria || $usuario_cargo === 'directiva'): ?>
                    <a href="funciones/secretaria_panel.php" class="dropdown-item"><span class="icon-panel-secretaria"></span> Panel Secretaria</a>
                    <?php endif; ?>
                    
                    <a href="funciones/tabla_meritos_demeritos.php" class="dropdown-item"><span class="icon-tabla-meritos"></span> Tabla méritos y deméritos</a>
                    
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item logout"><span class="icon-logout"></span> Cerrar Sesión</a>
                </div>
            </div>
        </div>
    </header>

    <?php if ($usuario_cargo === 'estudiante'): ?>
    <div class="stats-wrapper">
        <div class="stats-container">
            <div class="stat-card merito-card">
                <div class="stat-icon"><span class="icon-trophy"></span></div>
                <div class="stat-info"><span class="stat-label">Mér. Sem</span><span class="stat-value"><?php echo $meritos_semana; ?></span></div>
            </div>
            <div class="stat-card demerito-card">
                <div class="stat-icon"><span class="icon-warning"></span></div>
                <div class="stat-info"><span class="stat-label">Dem. Sem</span><span class="stat-value"><?php echo $demeritos_semana; ?></span></div>
            </div>
            <div class="stat-card balance-card <?php echo $alarma_activa ? 'alarma-activa' : ''; ?>">
                <div class="stat-icon"><span class="icon-balance"></span></div>
                <div class="stat-info"><span class="stat-label">Bal. Sem</span><span class="stat-value <?php echo $balance_semana >= 0 ? 'positive' : 'negative'; ?>"><?php echo $balance_semana; ?></span></div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <main class="main-content">
        <div class="welcome-section">
            <div class="welcome-header">
                <h1><span class="wave">👋</span> ¡Bienvenido!</h1>
                <span class="user-badge">
                    <span class="icon-user"></span> 
                    <?php echo ucfirst($usuario_cargo); ?>
                    <?php if (!empty($ocupacion_usuario) && $ocupacion_usuario !== 'ninguno'): ?>
                    (<?php echo str_replace('_', ' ', ucfirst($ocupacion_usuario)); ?>)
                    <?php endif; ?>
                </span>
            </div>
            <p class="welcome-text">Panel de control del sistema escolar</p>
            <p class="last-update"><span class="icon-clock"></span> Actualizado: <?php echo date('d/m/Y H:i:s'); ?></p>
        </div>

        <?php if ($alarma_activa && $usuario_cargo === 'estudiante'): ?>
        <div class="alarma-banner">
            <span class="icon-warning"></span>
            <div class="alarma-banner-content">
                <strong>¡ALARMA ACTIVADA!</strong>
                <span>Has alcanzado el límite de deméritos. Contacta a tu profesor guía.</span>
            </div>
        </div>
        <?php endif; ?>

        <?php if (in_array($usuario_cargo, ['directiva', 'oficial', 'profesor']) || $es_secretaria): ?>
        <!-- BUSCADOR DE ESTUDIANTES -->
        <div class="ds-buscador">
            <div class="ds-buscador-header">
                <h2 class="section-title"><span class="icon-search"></span> Buscar Estudiante</h2>
                <p class="ds-buscador-descripcion">Busca por nombre, apellidos o CI para ver su información</p>
            </div>
            
            <form method="GET" class="ds-search-form">
                <div class="ds-search-input-wrapper">
                    <span class="icon-search"></span>
                    <input type="text" name="buscar" placeholder="Ej: Juan Pérez o 12345678..." 
                           value="<?php echo htmlspecialchars($termino_busqueda); ?>" autocomplete="off">
                </div>
                <button type="submit" class="ds-search-btn">Buscar</button>
            </form>
            
            <?php if ($busqueda_realizada): ?>
            <div class="ds-resultados-container">
                <div class="ds-resultados-header">
                    <span class="ds-resultados-count"><?php echo count($resultados_busqueda); ?> resultado(s)</span>
                    <?php if (!empty($resultados_busqueda)): ?>
                    <button class="ds-btn-limpiar" onclick="window.location.href='index.php'">Limpiar</button>
                    <?php endif; ?>
                </div>
                
                <?php if (empty($resultados_busqueda)): ?>
                <div class="ds-empty-results">
                    <span class="icon-user-search"></span>
                    <h3>No se encontraron estudiantes</h3>
                    <p>Intenta con otro nombre, apellido o CI</p>
                </div>
                <?php else: ?>
                <div class="ds-resultados-list">
                    <?php foreach ($resultados_busqueda as $est): ?>
                    <div class="ds-estudiante-card">
                        <div class="ds-estudiante-avatar">
                            <span class="icon-user"></span>
                        </div>
                        <div class="ds-estudiante-content">
                            <div class="ds-estudiante-header">
                                <h3 class="ds-estudiante-nombre"><?php echo htmlspecialchars($est['nombre'] . ' ' . $est['apellidos']); ?></h3>
                                <span class="ds-estudiante-ci">CI: <?php echo htmlspecialchars($est['ci']); ?></span>
                            </div>
                            <div class="ds-estudiante-detalles">
                                <span class="ds-detalle-item">
                                    <span class="icon-graduation"></span> <?php echo htmlspecialchars($est['grado']); ?>
                                </span>
                                <span class="ds-detalle-item">
                                    <span class="icon-users"></span> Pelotón <?php echo $est['peloton'] ?? '?'; ?>
                                </span>
                            </div>
                            <div class="ds-estudiante-stats">
                                <div class="ds-stat-item merito">
                                    <span class="ds-stat-icon"><span class="icon-trophy"></span></span>
                                    <span class="ds-stat-value"><?php echo $est['meritos']; ?></span>
                                    <span class="ds-stat-label">Méritos</span>
                                </div>
                                <div class="ds-stat-item demerito">
                                    <span class="ds-stat-icon"><span class="icon-warning"></span></span>
                                    <span class="ds-stat-value"><?php echo $est['demeritos']; ?></span>
                                    <span class="ds-stat-label">Deméritos</span>
                                </div>
                                <div class="ds-stat-item balance <?php echo $est['balance'] >= 0 ? 'positive' : 'negative'; ?>">
                                    <span class="ds-stat-icon"><span class="icon-balance"></span></span>
                                    <span class="ds-stat-value"><?php echo $est['balance']; ?></span>
                                    <span class="ds-stat-label">Balance</span>
                                </div>
                            </div>
                            <div class="ds-estudiante-actions">
                                <a href="notificar.php?destinatario=<?php echo $est['id']; ?>" class="ds-action-btn primary">
                                    <span class="icon-plus"></span> Reportar
                                </a>
                                <a href="perfil_estudiante.php?id=<?php echo $est['id']; ?>" class="ds-action-btn secondary">
                                    <span class="icon-eye"></span> Ver Perfil
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="ds-buscador-placeholder">
                <span class="icon-search-large"></span>
                <p>Ingresa un término de búsqueda para encontrar estudiantes</p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($usuario_cargo === 'estudiante'): ?>
        <div id="notificationContainer"></div>

        <div class="semana-section">
            <h2 class="section-title">
                <span class="icon-calendar"></span> Esta Semana
                <span class="semana-fecha"><?php echo formatearSemana($inicio_semana_actual); ?></span>
            </h2>
            <div class="actividad-list">
                <?php if (empty($semana_actual)): ?>
                <div class="empty-state-small"><span class="icon-check-circle"></span><p>Sin actividades esta semana</p></div>
                <?php else: ?>
                    <?php foreach ($semana_actual as $act): ?>
                        <?php $esMerito = ($act['tipo'] === 'merito'); ?>
                        <div class="actividad-item <?php echo $esMerito ? 'merito' : 'demerito'; ?>">
                            <div class="actividad-icon"><span class="<?php echo $esMerito ? 'icon-trophy' : 'icon-warning'; ?>"></span></div>
                            <div class="actividad-info">
                                <div class="actividad-titulo"><?php echo htmlspecialchars($act['falta_causa'] ?? 'Sin descripción'); ?></div>
                                <div class="actividad-subtitulo"><?php echo htmlspecialchars($act['categoria'] ?? ''); ?></div>
                                <div class="actividad-notificador">
                                    <span class="icon-user"></span>
                                    <span>Notificado por: <strong><?php echo !empty($act['notificador']) ? htmlspecialchars($act['notificador']) : 'Sistema'; ?></strong></span>
                                </div>
                                <div class="actividad-meta">
                                    <span class="actividad-valor <?php echo $esMerito ? 'merito' : 'demerito'; ?>">
                                        <span class="<?php echo $esMerito ? 'icon-plus' : 'icon-minus'; ?>"></span>
                                        <?php echo $esMerito ? '+' : '-'; ?><?php echo $act['cantidad']; ?>
                                    </span>
                                    <span class="actividad-fecha"><span class="icon-calendar"></span> <?php echo date('d/m/Y', strtotime($act['fecha'])); ?></span>
                                    <span class="actividad-hora"><span class="icon-clock"></span> <?php echo date('H:i', strtotime($act['hora'])); ?></span>
                                    <?php if (!$esMerito): ?>
                                        <?php if (!empty($act['alegacion'])): ?>
                                        <span class="alegacion-badge" onclick="abrirAlegacion(<?php echo $act['id']; ?>, '<?php echo htmlspecialchars($act['falta_causa'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($act['alegacion'], ENT_QUOTES); ?>')" title="Ver alegación">
                                            <span class="icon-document"></span> Alegado
                                        </span>
                                        <?php else: ?>
                                        <button class="btn-alegar" onclick="abrirAlegacion(<?php echo $act['id']; ?>, '<?php echo htmlspecialchars($act['falta_causa'] ?? '', ENT_QUOTES); ?>', '')" title="Alegar">
                                            <span class="icon-edit"></span> Alegar
                                        </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!empty($semanas_anteriores)): ?>
            <?php foreach ($semanas_anteriores as $fecha => $acts): ?>
            <div class="semana-section" style="opacity: 0.9;">
                <h2 class="section-title"><span class="icon-history"></span> Semana <?php echo formatearSemana($fecha); ?></h2>
                <div class="actividad-list">
                    <?php foreach ($acts as $act): ?>
                        <?php $esMerito = ($act['tipo'] === 'merito'); ?>
                        <div class="actividad-item <?php echo $esMerito ? 'merito' : 'demerito'; ?>">
                            <div class="actividad-icon"><span class="<?php echo $esMerito ? 'icon-trophy' : 'icon-warning'; ?>"></span></div>
                            <div class="actividad-info">
                                <div class="actividad-titulo"><?php echo htmlspecialchars($act['falta_causa'] ?? 'Sin descripción'); ?></div>
                                <div class="actividad-subtitulo"><?php echo htmlspecialchars($act['categoria'] ?? ''); ?></div>
                                <div class="actividad-notificador">
                                    <span class="icon-user"></span>
                                    <span>Notificado por: <strong><?php echo !empty($act['notificador']) ? htmlspecialchars($act['notificador']) : 'Sistema'; ?></strong></span>
                                </div>
                                <div class="actividad-meta">
                                    <span class="actividad-valor <?php echo $esMerito ? 'merito' : 'demerito'; ?>">
                                        <span class="<?php echo $esMerito ? 'icon-plus' : 'icon-minus'; ?>"></span>
                                        <?php echo $esMerito ? '+' : '-'; ?><?php echo $act['cantidad']; ?>
                                    </span>
                                    <span class="actividad-fecha"><span class="icon-calendar"></span> <?php echo date('d/m/Y', strtotime($act['fecha'])); ?></span>
                                    <span class="actividad-hora"><span class="icon-clock"></span> <?php echo date('H:i', strtotime($act['hora'])); ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php elseif (in_array($usuario_cargo, ['profesor', 'oficial'])): ?>
        <div class="panel-cargo">
            <div class="panel-icon">
                <?php if ($usuario_cargo === 'profesor'): ?>
                <span class="icon-teacher"></span>
                <?php else: ?>
                <span class="icon-official"></span>
                <?php endif; ?>
            </div>
            <h2>Panel de <?php echo ucfirst($usuario_cargo); ?></h2>
            <p>Bienvenido al sistema de gestión escolar.</p>
            <div class="panel-actions">
                <a href="notificar.php" class="panel-btn"><span class="icon-bell"></span> Notificar Actividad</a>
                <?php if ($usuario_cargo === 'profesor'): ?>
                <a href="funciones/horario/profesor_horario.php" class="panel-btn"><span class="icon-calendar"></span> Ver Mi Horario</a>
                <?php else: ?>
                <a href="funciones/horario/" class="panel-btn"><span class="icon-calendar"></span> Ver Horario</a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <!-- Modal de Alegación -->
    <div class="modal-overlay-alegar" id="modalAlegacion">
        <div class="modal-alegar-content">
            <div class="modal-alegar-header">
                <h2><span class="icon-edit"></span> <span id="alegacionTituloModal">Presentar Alegación</span></h2>
                <button class="modal-alegar-close" onclick="cerrarAlegacion()">
                    <span class="icon-close"></span>
                </button>
            </div>
            <div class="modal-alegar-body">
                <p class="alegacion-motivo"><strong>Demérito:</strong> <span id="alegacionDemerito"></span></p>
                <div id="alegacionFormContainer">
                    <form id="formAlegacion" onsubmit="guardarAlegacion(event)">
                        <input type="hidden" name="actividad_id" id="alegacionActividadId">
                        <textarea name="alegacion" id="alegacionTexto" class="alegacion-textarea" 
                                  placeholder="Escribe tu alegación aquí..." rows="5" required></textarea>
                        <button type="submit" class="btn-alegar-enviar" id="btnEnviarAlegacion">
                            <span class="icon-send"></span> Enviar Alegación
                        </button>
                    </form>
                </div>
                <div id="alegacionExistente" style="display:none;">
                    <div class="alegacion-existente-texto" id="alegacionExistenteTexto"></div>
                    <p class="alegacion-ya-enviada"><span class="icon-check-circle"></span> Ya has presentado tu alegación para este demérito.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast de éxito -->
    <div class="toast-exito" id="toastExito">
        <div class="toast-exito-icon"><span class="icon-check-circle"></span></div>
        <div class="toast-exito-content">
            <div class="toast-exito-title">¡Alegación Exitosa!</div>
            <div class="toast-exito-text">Tu alegación ha sido guardada correctamente.</div>
        </div>
        <button class="toast-exito-close" onclick="document.getElementById('toastExito').classList.remove('show')">
            <span class="icon-close"></span>
        </button>
    </div>

    <!-- TOAST DE ALARMA -->
    <?php if ($alarma_activa && $usuario_cargo === 'estudiante'): ?>
    <div class="alarma-toast" id="alarmaToast">
        <span class="icon-warning"></span>
        <div class="alarma-toast-content">
            <div class="alarma-toast-title">¡ALARMA ACTIVADA!</div>
            <div class="alarma-toast-text">Has superado el límite de deméritos. Contacta a tu profesor guía.</div>
        </div>
        <button class="alarma-toast-close" onclick="document.getElementById('alarmaToast').style.display='none'">
            <span class="icon-close"></span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Modal QR -->
    <div class="modal" id="qrModal">
        <div class="qr-modal-content">
            <button class="modal-close" id="closeModalBtn"><span class="icon-close"></span></button>
            <h2><span class="icon-qr"></span> Tu Código QR</h2>
            <div class="qr-container">
                <div class="qr-wrapper">
                    <div class="qr-code" id="qrCode"></div>
                    <div class="qr-user-name"><?php echo htmlspecialchars($usuario_nombre . ' ' . $usuario_apellidos); ?></div>
                </div>
            </div>
            <div class="user-qr-info">
                <span class="qr-info-item"><span class="icon-id"></span> CI: <?php echo htmlspecialchars($usuario_ci); ?></span>
                <span class="qr-info-item"><span class="icon-user"></span> <?php echo ucfirst($usuario_cargo); ?></span>
            </div>
            <div class="qr-actions">
                <button class="btn-download" id="downloadQRBtn"><span class="icon-download"></span> Descargar QR</button>
            </div>
            <p class="qr-note">Este código contiene tu información</p>
        </div>
    </div>

    <script src="js/qrcode.min.js"></script>
    <script>
        // ========== DROPDOWN ==========
        var accountBtn = document.getElementById('accountBtn');
        var accountDropdown = document.getElementById('accountDropdown');
        var overlay = document.getElementById('overlay');
        
        if (accountBtn) {
            accountBtn.onclick = function(e) {
                e.stopPropagation();
                accountDropdown.classList.toggle('show');
                overlay.classList.toggle('show');
            };
        }
        if (overlay) {
            overlay.onclick = function() {
                accountDropdown.classList.remove('show');
                overlay.classList.remove('show');
            };
        }
        document.onclick = function(e) {
            if (accountDropdown && accountBtn) {
                if (!accountDropdown.contains(e.target) && !accountBtn.contains(e.target)) {
                    accountDropdown.classList.remove('show');
                    overlay.classList.remove('show');
                }
            }
        };
        
        // ========== ALEGACIÓN ==========
        function abrirAlegacion(actividadId, demerito, alegacionExistente) {
            document.getElementById('alegacionActividadId').value = actividadId;
            document.getElementById('alegacionDemerito').textContent = demerito || 'Sin descripción';
            
            var formContainer = document.getElementById('alegacionFormContainer');
            var alegacionExistenteDiv = document.getElementById('alegacionExistente');
            var tituloModal = document.getElementById('alegacionTituloModal');
            
            if (alegacionExistente && alegacionExistente.trim() !== '') {
                // Ya tiene alegación - mostrar la existente
                tituloModal.textContent = 'Alegación Presentada';
                formContainer.style.display = 'none';
                alegacionExistenteDiv.style.display = 'block';
                document.getElementById('alegacionExistenteTexto').textContent = alegacionExistente;
            } else {
                // No tiene alegación - mostrar formulario
                tituloModal.textContent = 'Presentar Alegación';
                formContainer.style.display = 'block';
                alegacionExistenteDiv.style.display = 'none';
                document.getElementById('alegacionTexto').value = '';
                document.getElementById('btnEnviarAlegacion').disabled = false;
            }
            
            document.getElementById('modalAlegacion').style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function cerrarAlegacion() {
            document.getElementById('modalAlegacion').style.display = 'none';
            document.getElementById('alegacionTexto').value = '';
            document.body.style.overflow = '';
        }
        
        function mostrarToastExito() {
            var toast = document.getElementById('toastExito');
            toast.classList.add('show');
            
            // Ocultar después de 3 segundos
            setTimeout(function() {
                toast.classList.remove('show');
            }, 3000);
        }
        
        function guardarAlegacion(event) {
            event.preventDefault();
            
            var actividadId = document.getElementById('alegacionActividadId').value;
            var alegacion = document.getElementById('alegacionTexto').value;
            var btnEnviar = document.getElementById('btnEnviarAlegacion');
            
            if (!alegacion.trim()) {
                // Mostrar error en el textarea
                document.getElementById('alegacionTexto').style.borderColor = '#ef4444';
                document.getElementById('alegacionTexto').focus();
                setTimeout(function() {
                    document.getElementById('alegacionTexto').style.borderColor = '#e2e8f0';
                }, 2000);
                return;
            }
            
            // Deshabilitar botón mientras se envía
            btnEnviar.disabled = true;
            btnEnviar.innerHTML = '<span class="icon-spinner"></span> Enviando...';
            
            fetch('funciones/guardar_alegacion.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'actividad_id=' + encodeURIComponent(actividadId) + '&alegacion=' + encodeURIComponent(alegacion)
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    cerrarAlegacion();
                    mostrarToastExito();
                    // Recargar después de 1.5 segundos
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar'));
                    btnEnviar.disabled = false;
                    btnEnviar.innerHTML = '<span class="icon-send"></span> Enviar Alegación';
                }
            })
            .catch(function(error) {
                alert('Error de conexión');
                console.error(error);
                btnEnviar.disabled = false;
                btnEnviar.innerHTML = '<span class="icon-send"></span> Enviar Alegación';
            });
        }
        
        // Cerrar modal de alegación al hacer clic fuera
        document.getElementById('modalAlegacion').addEventListener('click', function(e) {
            if (e.target === this) cerrarAlegacion();
        });
        
        // ========== MODO ALERTA ==========
        <?php if ($usuario_cargo === 'estudiante'): ?>
        if (<?php echo $alarma_activa ? 'true' : 'false'; ?>) {
            document.body.classList.add('modo-alerta');
            
            setTimeout(function() {
                var toast = document.getElementById('alarmaToast');
                if (toast) toast.style.display = 'none';
            }, 10000);
        }
        <?php endif; ?>
        
        // ========== TUTORIAL ==========
        <?php if ($mostrar_tutorial): ?>
        var currentStep = 1;
        var totalSteps = <?php echo $usuario_cargo === 'estudiante' ? 5 : 4; ?>;
        var tutorialOverlay = document.getElementById('tutorialOverlay');
        var steps = document.querySelectorAll('.tutorial-step');
        var dots = document.querySelectorAll('.tutorial-dot');
        var prevBtn = document.getElementById('tutorialPrev');
        var nextBtn = document.getElementById('tutorialNext');
        var skipBtn = document.getElementById('tutorialSkip');
        var finishBtn = document.getElementById('tutorialFinish');

        function updateStep(step) {
            steps.forEach(function(s) { s.classList.remove('active'); });
            dots.forEach(function(d) { d.classList.remove('active'); });
            
            document.querySelector('.tutorial-step[data-step="' + step + '"]').classList.add('active');
            document.querySelector('.tutorial-dot[data-step="' + step + '"]').classList.add('active');
            
            prevBtn.disabled = (step === 1);
            
            if (step === totalSteps) {
                nextBtn.style.display = 'none';
                finishBtn.style.display = 'flex';
            } else {
                nextBtn.style.display = 'flex';
                finishBtn.style.display = 'none';
            }
            
            currentStep = step;
        }

        function marcarTutorialVisto() {
            fetch('marcar_tutorial.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ visto: true })
            }).catch(function(e) { console.error(e); });
        }

        if (prevBtn) {
            prevBtn.addEventListener('click', function() {
                if (currentStep > 1) updateStep(currentStep - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', function() {
                if (currentStep < totalSteps) updateStep(currentStep + 1);
            });
        }

        if (skipBtn) {
            skipBtn.addEventListener('click', function() {
                marcarTutorialVisto();
                tutorialOverlay.style.display = 'none';
            });
        }

        if (finishBtn) {
            finishBtn.addEventListener('click', function() {
                marcarTutorialVisto();
                tutorialOverlay.style.display = 'none';
            });
        }

        if (tutorialOverlay) {
            tutorialOverlay.addEventListener('click', function(e) {
                if (e.target === tutorialOverlay) {
                    marcarTutorialVisto();
                    tutorialOverlay.style.display = 'none';
                }
            });
        }

        updateStep(1);
        <?php endif; ?>
        
        // ========== QR ==========
        var qrData = <?php echo json_encode($qr_encriptado); ?>;
        var qrBtn = document.getElementById('qrBtn');
        var qrModal = document.getElementById('qrModal');
        var closeModalBtn = document.getElementById('closeModalBtn');
        var downloadBtn = document.getElementById('downloadQRBtn');
        var qrGenerado = false;
        
        function generarQR() {
            var container = document.getElementById("qrCode");
            container.innerHTML = '';
            if (typeof QRCode === 'undefined') {
                container.innerHTML = '<p style="color:red;">Error: QR no disponible</p>';
                return;
            }
            try {
                new QRCode(container, {
                    text: qrData, width: 160, height: 160,
                    colorDark: "#1e3c72", colorLight: "#ffffff",
                    correctLevel: QRCode.CorrectLevel.H
                });
                qrGenerado = true;
            } catch(e) {
                container.innerHTML = '<p style="color:red;">Error al generar QR</p>';
            }
        }
        
        if (qrBtn) qrBtn.onclick = function() {
            qrModal.style.display = 'flex';
            if (!qrGenerado) setTimeout(generarQR, 100);
        };
        if (closeModalBtn) closeModalBtn.onclick = function() { qrModal.style.display = 'none'; };
        if (qrModal) qrModal.onclick = function(e) { if (e.target === qrModal) qrModal.style.display = 'none'; };
        if (downloadBtn) downloadBtn.onclick = function() {
            var canvas = document.querySelector('#qrCode canvas');
            if (!canvas) { alert('Genera el QR primero'); return; }
            var nc = document.createElement('canvas'), ctx = nc.getContext('2d');
            nc.width = 220; nc.height = 260;
            ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, 220, 260);
            ctx.drawImage(canvas, 10, 10, 200, 200);
            ctx.fillStyle = '#1e3c72'; ctx.font = 'bold 13px Poppins'; ctx.textAlign = 'center';
            ctx.fillText('<?php echo htmlspecialchars($usuario_nombre . " " . $usuario_apellidos); ?>', 110, 235);
            var a = document.createElement('a');
            a.download = 'QR_<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $usuario_nombre . '_' . $usuario_apellidos); ?>.png';
            a.href = nc.toDataURL('image/png'); a.click();
        };
    </script>
</body>
</html>