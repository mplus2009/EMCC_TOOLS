<?php
session_start();

// Incluir conexión
require_once __DIR__ . '../../../db.php';

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: ../login.php');
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$usuario_cargo = $_SESSION['usuario_cargo'];
$usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Usuario';
$usuario_apellidos = $_SESSION['usuario_apellidos'] ?? '';

// Formatear ID para búsquedas (el usuario es el EMISOR)
$id_formateado = $usuario_cargo . '_' . $usuario_id;

// Obtener ocupación del usuario
$ocupacion_usuario = '';
if ($usuario_id > 0) {
    $check = $conexion->query("SHOW COLUMNS FROM $usuario_cargo LIKE 'ocupacion'");
    if ($check && $check->num_rows > 0) {
        $sql = "SELECT ocupacion FROM $usuario_cargo WHERE id = ?";
        $stmt = $conexion->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $usuario_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc()) {
                $ocupacion_usuario = $row['ocupacion'] ?? '';
            }
            $stmt->close();
        }
    }
}

// Procesar filtros
$filtro_tipo = $_GET['tipo'] ?? 'todos';
$filtro_fecha = $_GET['fecha'] ?? '';
$busqueda = $_GET['busqueda'] ?? '';
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 15;
$offset = ($pagina - 1) * $por_pagina;

// Construir consulta base
$where_conditions = ["a.id_star = ?"];
$params = [$id_formateado];
$types = "s";

if ($filtro_tipo !== 'todos') {
    $where_conditions[] = "a.tipo = ?";
    $params[] = $filtro_tipo;
    $types .= "s";
}

if (!empty($filtro_fecha)) {
    $where_conditions[] = "DATE(a.fecha) = ?";
    $params[] = $filtro_fecha;
    $types .= "s";
}

if (!empty($busqueda)) {
    $where_conditions[] = "(e.nombre LIKE ? OR e.apellidos LIKE ? OR a.falta_causa LIKE ?)";
    $search_term = "%$busqueda%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $types .= "sss";
}

$where_clause = implode(' AND ', $where_conditions);

// Inicializar variables por defecto
$total_registros = 0;
$total_paginas = 0;
$notificaciones = [];
$stats = ['total' => 0, 'total_meritos' => 0, 'total_demeritos' => 0];

// Verificar si la tabla actividad existe
$check_tabla = $conexion->query("SHOW TABLES LIKE 'actividad'");
if ($check_tabla && $check_tabla->num_rows > 0) {
    // Contar total de registros
    $sql_count = "SELECT COUNT(*) as total 
                  FROM actividad a 
                  LEFT JOIN estudiante e ON a.id_end = CONCAT('estudiante_', e.id)
                  WHERE $where_clause";
    
    $stmt_count = $conexion->prepare($sql_count);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $total_registros = $result_count->fetch_assoc()['total'];
    $total_paginas = ceil($total_registros / $por_pagina);
    $stmt_count->close();
    
    // Obtener notificaciones
    $sql = "SELECT a.*, 
            CASE 
                WHEN a.id_end LIKE 'estudiante_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM estudiante WHERE id = SUBSTRING_INDEX(a.id_end, '_', -1))
                WHEN a.id_end LIKE 'profesor_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM profesor WHERE id = SUBSTRING_INDEX(a.id_end, '_', -1))
                WHEN a.id_end LIKE 'oficial_%' THEN (SELECT CONCAT(nombre, ' ', apellidos) FROM oficial WHERE id = SUBSTRING_INDEX(a.id_end, '_', -1))
                ELSE 'Desconocido'
            END as destinatario_nombre,
            CASE 
                WHEN a.id_end LIKE 'estudiante_%' THEN (SELECT CI FROM estudiante WHERE id = SUBSTRING_INDEX(a.id_end, '_', -1))
                ELSE ''
            END as destinatario_ci,
            CASE 
                WHEN a.id_end LIKE 'estudiante_%' THEN (SELECT grado FROM estudiante WHERE id = SUBSTRING_INDEX(a.id_end, '_', -1))
                ELSE ''
            END as destinatario_grado
            FROM actividad a
            LEFT JOIN estudiante e ON a.id_end = CONCAT('estudiante_', e.id)
            WHERE $where_clause
            ORDER BY a.fecha DESC, a.hora DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conexion->prepare($sql);
    $params_paginados = $params;
    $params_paginados[] = $por_pagina;
    $params_paginados[] = $offset;
    $types_paginados = $types . "ii";
    $stmt->bind_param($types_paginados, ...$params_paginados);
    $stmt->execute();
    $notificaciones = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Estadísticas
    $sql_stats = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN tipo = 'merito' THEN 1 ELSE 0 END) as total_meritos,
        SUM(CASE WHEN tipo = 'demerito' THEN 1 ELSE 0 END) as total_demeritos
        FROM actividad 
        WHERE id_star = ?";
    $stmt_stats = $conexion->prepare($sql_stats);
    $stmt_stats->bind_param("s", $id_formateado);
    $stmt_stats->execute();
    $stats = $stmt_stats->get_result()->fetch_assoc();
    $stmt_stats->close();
}

// Funciones helper
function formatearFecha($fecha) {
    return date('d/m/Y', strtotime($fecha));
}

function getTipoClase($tipo) {
    return $tipo === 'merito' ? 'merito' : 'demerito';
}

function getTipoIcono($tipo) {
    return $tipo === 'merito' ? 'icon-trophy' : 'icon-warning';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <title>Mis Notificaciones - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../iconos_vectoriales.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            padding-bottom: 20px;
        }
        
        /* Header - Optimizado móvil */
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 12px 16px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.12);
        }
        
        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: white;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            text-decoration: none;
            font-size: 20px;
            transition: background 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        
        .back-btn:active {
            background: rgba(255, 255, 255, 0.25);
        }
        
        .header h1 {
            color: white;
            font-size: 1.2em;
            font-weight: 600;
            flex: 1;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        /* Contenedor principal - Optimizado móvil */
        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 14px 12px;
        }
        
        /* Estadísticas - Grid adaptable móvil */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 6px;
            margin-bottom: 16px;
        }
        
        .stat-mini-card {
            background: white;
            border-radius: 14px;
            padding: 12px 4px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            transition: transform 0.2s;
        }
        
        .stat-mini-card:active {
            transform: scale(0.98);
        }
        
        .stat-mini-icon {
            font-size: 18px;
            margin-bottom: 4px;
        }
        
        .stat-mini-value {
            font-size: 1.2em;
            font-weight: 800;
            color: #1e293b;
            line-height: 1.2;
        }
        
        .stat-mini-label {
            font-size: 0.55em;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        
        .stat-mini-card.merito .stat-mini-icon { color: #10b981; }
        .stat-mini-card.demerito .stat-mini-icon { color: #ef4444; }
        .stat-mini-card.total .stat-mini-icon { color: #1e3c72; }
        
        /* Filtros - Optimizado móvil */
        .filtros-section {
            background: white;
            border-radius: 18px;
            padding: 14px;
            margin-bottom: 16px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }
        
        .filtros-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .filtros-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        
        .filtro-select, .filtro-input {
            padding: 12px 12px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9em;
            background: white;
            flex: 1;
            min-width: 120px;
            -webkit-appearance: none;
            appearance: none;
        }
        
        .filtro-select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%2364748b'%3E%3Cpath d='M7 10l5 5 5-5z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 10px center;
            background-size: 20px;
            padding-right: 35px;
        }
        
        .filtro-select:focus, .filtro-input:focus {
            outline: none;
            border-color: #1e3c72;
        }
        
        .search-wrapper {
            display: flex;
            gap: 8px;
        }
        
        .search-input {
            flex: 1;
            padding: 12px 14px;
            border: 1.5px solid #e2e8f0;
            border-radius: 12px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.9em;
            -webkit-appearance: none;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #1e3c72;
        }
        
        .filtro-btn {
            padding: 12px 16px;
            background: #1e3c72;
            color: white;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            font-size: 0.9em;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
            transition: background 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        
        .filtro-btn:active {
            background: #15294f;
        }
        
        .filtro-btn.secondary {
            background: #e2e8f0;
            color: #475569;
        }
        
        .filtro-btn.secondary:active {
            background: #cbd5e1;
        }
        
        .filtros-actions {
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        
        /* Lista de notificaciones - Optimizado móvil */
        .notificaciones-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .notificacion-card {
            background: white;
            border-radius: 16px;
            padding: 14px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            border-left: 4px solid;
            transition: transform 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        
        .notificacion-card:active {
            transform: scale(0.99);
        }
        
        .notificacion-card.merito {
            border-left-color: #10b981;
            background: linear-gradient(90deg, #f0fdf4 0%, white 100%);
        }
        
        .notificacion-card.demerito {
            border-left-color: #ef4444;
            background: linear-gradient(90deg, #fef2f2 0%, white 100%);
        }
        
        .notificacion-header {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            margin-bottom: 10px;
        }
        
        .notificacion-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        
        .notificacion-card.merito .notificacion-icon {
            background: #d1fae5;
            color: #065f46;
        }
        
        .notificacion-card.demerito .notificacion-icon {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .notificacion-info {
            flex: 1;
            min-width: 0;
        }
        
        .notificacion-titulo {
            font-size: 0.95em;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .notificacion-destinatario {
            display: flex;
            align-items: center;
            gap: 5px;
            font-size: 0.75em;
            color: #64748b;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .notificacion-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7em;
            font-weight: 600;
        }
        
        .notificacion-badge.merito {
            background: #d1fae5;
            color: #065f46;
        }
        
        .notificacion-badge.demerito {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .notificacion-cantidad {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 8px;
            border-radius: 20px;
            font-weight: 700;
            font-size: 0.8em;
            margin-top: 6px;
        }
        
        .notificacion-card.merito .notificacion-cantidad {
            background: #d1fae5;
            color: #065f46;
        }
        
        .notificacion-card.demerito .notificacion-cantidad {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .notificacion-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
        }
        
        .notificacion-fecha {
            display: flex;
            align-items: center;
            gap: 5px;
            color: #64748b;
            font-size: 0.7em;
        }
        
        .notificacion-actions {
            display: flex;
            gap: 6px;
        }
        
        .btn-detalles {
            padding: 8px 12px;
            background: #1e3c72;
            color: white;
            border: none;
            border-radius: 20px;
            font-size: 0.75em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 4px;
            text-decoration: none;
            transition: background 0.2s;
            -webkit-tap-highlight-color: transparent;
        }
        
        .btn-detalles:active {
            background: #15294f;
        }
        
        /* Estado vacío */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.04);
        }
        
        .empty-state-icon {
            font-size: 50px;
            color: #cbd5e1;
            margin-bottom: 12px;
        }
        
        .empty-state h3 {
            color: #475569;
            margin-bottom: 6px;
            font-size: 1.1em;
        }
        
        .empty-state p {
            color: #94a3b8;
            font-size: 0.85em;
        }
        
        .empty-state .btn-primary {
            display: inline-block;
            margin-top: 18px;
            background: #1e3c72;
            color: white;
            padding: 12px 22px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9em;
            transition: background 0.2s;
        }
        
        .empty-state .btn-primary:active {
            background: #15294f;
        }
        
        /* Paginación */
        .paginacion {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 12px;
            margin-top: 20px;
        }
        
        .paginacion-btn {
            padding: 10px 16px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            color: #1e3c72;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background 0.2s;
        }
        
        .paginacion-btn:active {
            background: #f1f5f9;
        }
        
        .paginacion-btn.disabled {
            opacity: 0.5;
            pointer-events: none;
        }
        
        .paginacion-info {
            color: #64748b;
            font-size: 0.8em;
        }
        
        /* Modal de detalles - Optimizado móvil */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            z-index: 1000;
            align-items: flex-end;
            justify-content: center;
        }
        
        .modal-content {
            background: white;
            border-radius: 24px 24px 0 0;
            max-width: 500px;
            width: 100%;
            max-height: 85vh;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); }
            to { transform: translateY(0); }
        }
        
        .modal-header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            color: white;
            padding: 18px 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .modal-header h2 {
            font-size: 1.2em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .modal-close {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            width: 38px;
            height: 38px;
            border-radius: 10px;
            color: white;
            font-size: 18px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            -webkit-tap-highlight-color: transparent;
        }
        
        .modal-close:active {
            background: rgba(255, 255, 255, 0.25);
        }
        
        .modal-body {
            padding: 18px 16px;
            overflow-y: auto;
        }
        
        .detalle-item {
            display: flex;
            margin-bottom: 14px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .detalle-label {
            width: 110px;
            font-weight: 600;
            color: #1e293b;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .detalle-value {
            flex: 1;
            color: #475569;
            font-size: 0.85em;
        }
        
        .detalle-descripcion {
            background: #f8fafc;
            padding: 14px;
            border-radius: 12px;
            margin-top: 8px;
            color: #475569;
            line-height: 1.5;
            font-size: 0.85em;
        }
        
        .estado-leido {
            margin-top: 15px;
            padding: 10px 12px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8em;
        }
        
        .estado-leido.leido {
            background: #d1fae5;
            color: #065f46;
        }
        
        .estado-leido.pendiente {
            background: #fef3c7;
            color: #92400e;
        }
        
        /* Ajustes para pantallas muy pequeñas */
        @media (max-width: 400px) {
            .header h1 {
                font-size: 1.1em;
            }
            
            .container {
                padding: 12px 10px;
            }
            
            .notificacion-footer {
                flex-direction: column;
                gap: 8px;
                align-items: flex-start;
            }
            
            .notificacion-actions {
                width: 100%;
            }
            
            .btn-detalles {
                flex: 1;
                justify-content: center;
            }
        }
        
        /* Ajustes para tablets */
        @media (min-width: 600px) {
            .modal-content {
                border-radius: 24px;
                margin: 20px;
                max-height: 80vh;
            }
            
            .modal-overlay {
                align-items: center;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <a href="../index.php" class="back-btn">
            <span class="icon-arrow-left"></span>
        </a>
        <h1><span class="icon-bell"></span> Mis Notificaciones</h1>
    </header>

    <div class="container">
        <!-- Estadísticas -->
        <div class="stats-cards">
            <div class="stat-mini-card total">
                <div class="stat-mini-icon"><span class="icon-bell"></span></div>
                <div class="stat-mini-value"><?php echo $stats['total'] ?? 0; ?></div>
                <div class="stat-mini-label">Total</div>
            </div>
            <div class="stat-mini-card merito">
                <div class="stat-mini-icon"><span class="icon-trophy"></span></div>
                <div class="stat-mini-value"><?php echo $stats['total_meritos'] ?? 0; ?></div>
                <div class="stat-mini-label">Méritos</div>
            </div>
            <div class="stat-mini-card demerito">
                <div class="stat-mini-icon"><span class="icon-warning"></span></div>
                <div class="stat-mini-value"><?php echo $stats['total_demeritos'] ?? 0; ?></div>
                <div class="stat-mini-label">Deméritos</div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="filtros-section">
            <form method="GET" class="filtros-form">
                <div class="filtros-row">
                    <select name="tipo" class="filtro-select">
                        <option value="todos" <?php echo $filtro_tipo === 'todos' ? 'selected' : ''; ?>>Todos</option>
                        <option value="merito" <?php echo $filtro_tipo === 'merito' ? 'selected' : ''; ?>>🏆 Méritos</option>
                        <option value="demerito" <?php echo $filtro_tipo === 'demerito' ? 'selected' : ''; ?>>⚠️ Deméritos</option>
                    </select>
                    <input type="date" name="fecha" class="filtro-input" value="<?php echo htmlspecialchars($filtro_fecha); ?>" placeholder="Fecha">
                </div>
                <div class="search-wrapper">
                    <input type="text" name="busqueda" class="search-input" placeholder="Buscar..." value="<?php echo htmlspecialchars($busqueda); ?>">
                    <button type="submit" class="filtro-btn">
                        <span class="icon-search"></span>
                    </button>
                </div>
                <?php if ($filtro_tipo !== 'todos' || !empty($filtro_fecha) || !empty($busqueda)): ?>
                <div class="filtros-actions">
                    <a href="mis_notificaciones.php" class="filtro-btn secondary">
                        <span class="icon-close"></span> Limpiar
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Lista de notificaciones -->
        <?php if (empty($notificaciones)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><span class="icon-bell-off"></span></div>
            <h3>No hay notificaciones</h3>
            <p><?php echo (!empty($busqueda) || !empty($filtro_fecha) || $filtro_tipo !== 'todos') ? 'No se encontraron resultados.' : 'Aún no has enviado ninguna notificación.'; ?></p>
            <?php if (empty($busqueda) && empty($filtro_fecha) && $filtro_tipo === 'todos'): ?>
            <a href="../notificar.php" class="btn-primary">
                <span class="icon-plus"></span> Enviar primera notificación
            </a>
            <?php else: ?>
            <a href="mis_notificaciones.php" style="display: inline-block; margin-top: 18px; color: #1e3c72; text-decoration: none; font-weight: 600;">
                <span class="icon-arrow-left"></span> Ver todas
            </a>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="notificaciones-list">
            <?php foreach ($notificaciones as $not): ?>
            <div class="notificacion-card <?php echo getTipoClase($not['tipo']); ?>" data-id="<?php echo $not['id']; ?>">
                <div class="notificacion-header">
                    <div class="notificacion-icon">
                        <span class="<?php echo getTipoIcono($not['tipo']); ?>"></span>
                    </div>
                    <div class="notificacion-info">
                        <div class="notificacion-titulo">
                            <?php echo htmlspecialchars($not['falta_causa'] ?? 'Sin descripción'); ?>
                        </div>
                        <div class="notificacion-destinatario">
                            <span class="icon-user"></span>
                            <?php echo htmlspecialchars($not['destinatario_nombre'] ?? 'Desconocido'); ?>
                        </div>
                        <span class="notificacion-badge <?php echo getTipoClase($not['tipo']); ?>">
                            <?php echo $not['tipo'] === 'merito' ? 'Mérito' : 'Demérito'; ?>
                        </span>
                    </div>
                </div>
                
                <div class="notificacion-cantidad">
                    <span class="<?php echo $not['tipo'] === 'merito' ? 'icon-plus' : 'icon-minus'; ?>"></span>
                    <?php echo $not['tipo'] === 'merito' ? '+' : '-'; ?><?php echo $not['cantidad']; ?> punto(s)
                </div>
                
                <div class="notificacion-footer">
                    <div class="notificacion-fecha">
                        <span class="icon-calendar"></span>
                        <?php echo formatearFecha($not['fecha']); ?>
                        <span class="icon-clock"></span>
                        <?php echo date('H:i', strtotime($not['hora'])); ?>
                    </div>
                    <div class="notificacion-actions">
                        <button class="btn-detalles" onclick='mostrarDetalles(<?php echo json_encode($not, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                            <span class="icon-eye"></span> Ver
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Paginación -->
        <?php if ($total_paginas > 1): ?>
        <div class="paginacion">
            <?php 
            $url_params = [];
            if ($filtro_tipo !== 'todos') $url_params[] = "tipo=$filtro_tipo";
            if (!empty($filtro_fecha)) $url_params[] = "fecha=$filtro_fecha";
            if (!empty($busqueda)) $url_params[] = "busqueda=" . urlencode($busqueda);
            $url_string = !empty($url_params) ? '&' . implode('&', $url_params) : '';
            ?>
            
            <a href="?pagina=<?php echo $pagina - 1 . $url_string; ?>" class="paginacion-btn <?php echo $pagina <= 1 ? 'disabled' : ''; ?>">
                <span class="icon-arrow-left"></span>
            </a>
            <span class="paginacion-info"><?php echo $pagina; ?> / <?php echo $total_paginas; ?></span>
            <a href="?pagina=<?php echo $pagina + 1 . $url_string; ?>" class="paginacion-btn <?php echo $pagina >= $total_paginas ? 'disabled' : ''; ?>">
                <span class="icon-arrow-right"></span>
            </a>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Modal de detalles -->
    <div class="modal-overlay" id="detalleModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><span class="icon-info"></span> Detalles</h2>
                <button class="modal-close" onclick="cerrarModal()">
                    <span class="icon-close"></span>
                </button>
            </div>
            <div class="modal-body" id="modalBody">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <script>
        function mostrarDetalles(notificacion) {
            var modal = document.getElementById('detalleModal');
            var modalBody = document.getElementById('modalBody');
            
            var tipoTexto = notificacion.tipo === 'merito' ? 'Mérito' : 'Demérito';
            var tipoIcono = notificacion.tipo === 'merito' ? 'icon-trophy' : 'icon-warning';
            var tipoClase = notificacion.tipo === 'merito' ? 'merito' : 'demerito';
            var signo = notificacion.tipo === 'merito' ? '+' : '-';
            
            var html = `
                <div class="notificacion-card ${tipoClase}" style="margin-bottom: 15px; box-shadow: none;">
                    <div class="notificacion-header">
                        <div class="notificacion-icon">
                            <span class="${tipoIcono}"></span>
                        </div>
                        <div class="notificacion-info">
                            <div class="notificacion-titulo">${tipoTexto}</div>
                            <span class="notificacion-badge ${tipoClase}">${notificacion.categoria || 'Sin categoría'}</span>
                        </div>
                    </div>
                    
                    <div class="notificacion-cantidad">
                        <span class="${notificacion.tipo === 'merito' ? 'icon-plus' : 'icon-minus'}"></span>
                        ${signo}${notificacion.cantidad} punto(s)
                    </div>
                </div>
                
                <div class="detalle-item">
                    <div class="detalle-label"><span class="icon-user"></span> Para:</div>
                    <div class="detalle-value">
                        <strong>${notificacion.destinatario_nombre || 'Desconocido'}</strong>
                        ${notificacion.destinatario_ci ? '<br><span class="icon-id"></span> CI: ' + notificacion.destinatario_ci : ''}
                        ${notificacion.destinatario_grado ? '<br><span class="icon-graduation"></span> ' + notificacion.destinatario_grado : ''}
                    </div>
                </div>
                
                <div class="detalle-item">
                    <div class="detalle-label"><span class="icon-calendar"></span> Fecha:</div>
                    <div class="detalle-value">
                        ${formatearFecha(notificacion.fecha)} a las ${notificacion.hora.substring(0, 5)}
                    </div>
                </div>
                
                <div class="detalle-item">
                    <div class="detalle-label"><span class="icon-tag"></span> Categoría:</div>
                    <div class="detalle-value">${notificacion.categoria || 'No especificada'}</div>
                </div>
                
                <div class="detalle-item">
                    <div class="detalle-label"><span class="icon-document"></span> Causa:</div>
                    <div class="detalle-value">${notificacion.falta_causa || 'Sin descripción'}</div>
                </div>
                
                ${notificacion.descripcion ? `
                <div class="detalle-descripcion">
                    <strong><span class="icon-edit"></span> Descripción:</strong><br>
                    ${notificacion.descripcion}
                </div>
                ` : ''}
                
                ${notificacion.leido ? `
                <div class="estado-leido leido">
                    <span class="icon-check-circle"></span> Notificación leída por el destinatario
                </div>
                ` : `
                <div class="estado-leido pendiente">
                    <span class="icon-clock"></span> Pendiente de leer por el destinatario
                </div>
                `}
            `;
            
            modalBody.innerHTML = html;
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }
        
        function formatearFecha(fecha) {
            var d = new Date(fecha);
            if (isNaN(d.getTime())) return fecha;
            return d.getDate().toString().padStart(2, '0') + '/' + 
                   (d.getMonth() + 1).toString().padStart(2, '0') + '/' + 
                   d.getFullYear();
        }
        
        function cerrarModal() {
            document.getElementById('detalleModal').style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // Cerrar modal al hacer clic fuera
        document.getElementById('detalleModal').addEventListener('click', function(e) {
            if (e.target === this) {
                cerrarModal();
            }
        });
        
        // Cerrar modal con tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarModal();
            }
        });
        
        // Prevenir zoom en inputs en iOS
        document.querySelectorAll('input, select, button').forEach(function(el) {
            el.addEventListener('touchstart', function(e) {
                e.stopPropagation();
            }, { passive: true });
        });
    </script>
</body>
</html>