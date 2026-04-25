<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <title>Notificar - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="notificar_estilos.css">
    <style>
        .cantidad-range-wrapper { margin-top: 10px; }
        .cantidad-range-container {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .cantidad-range-container input[type="range"] {
            flex: 1;
            -webkit-appearance: none;
            height: 10px;
            background: #e2e8f0;
            border-radius: 10px;
            outline: none;
        }
        .cantidad-range-container input[type="range"]::-webkit-slider-thumb {
            -webkit-appearance: none;
            width: 28px;
            height: 28px;
            background: #1e3c72;
            border-radius: 50%;
            cursor: pointer;
            border: 3px solid white;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        .rango-limite { font-weight: 800; color: #64748b; min-width: 25px; }
        .rango-valor-central { text-align: center; margin-top: 10px; }
        .rango-valor-badge {
            display: inline-block;
            padding: 5px 20px;
            border-radius: 15px;
            font-weight: 700;
            color: white;
            transition: 0.3s;
        }
        .rango-valor-badge.merito { background: #10b981; }
        .rango-valor-badge.demerito { background: #ef4444; }
        .rango-valor-badge.default { background: #94a3b8; }
        
        /* Ajuste para el buscador de actividades */
        #actividadSearchResults {
            position: absolute;
            z-index: 100;
            width: 100%;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            max-height: 200px;
            overflow-y: auto;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>

    <header class="header">
        <div class="header-left">
            <a href="index.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
            <h1 class="header-title">Nueva Notificación</h1>
        </div>
    </header>

    <main class="main-content">
        <form id="notificarForm">
            <div class="form-section">
                <h2 class="section-title"><i class="fas fa-user-graduate"></i> Estudiantes</h2>
                <div class="destinatarios-list" id="destinatariosList">
                    <p class="empty-destinatarios" id="emptyDestinatarios">Busca y selecciona estudiantes</p>
                </div>
                <div class="search-box">
                    <div class="search-input-wrapper">
                        <i class="fas fa-search"></i>
                        <input type="text" id="buscarUsuario" placeholder="Nombre, apellidos o CI..." autocomplete="off">
                    </div>
                    <div class="search-results" id="searchResults"></div>
                </div>
                <input type="hidden" name="destinatarios" id="destinatariosInput">
            </div>

            <div class="form-section">
                <h2 class="section-title"><i class="fas fa- clipboard-list"></i> Actividad y Puntos</h2>
                
                <div class="search-box" style="position: relative; margin-bottom: 20px;">
                    <div class="search-input-wrapper">
                        <i class="fas fa-bolt"></i>
                        <input type="text" id="buscarActividad" placeholder="Buscar actividad rápidamente..." autocomplete="off">
                    </div>
                    <div id="actividadSearchResults" style="display:none;"></div>
                </div>

                <div class="tipo-selector">
                    <button type="button" class="tipo-btn active" data-tipo="merito">Mérito</button>
                    <button type="button" class="tipo-btn" data-tipo="demerito">Demérito</button>
                </div>

                <div class="form-group">
                    <label>Categoría</label>
                    <select id="selectCategoria" class="form-control"></select>
                </div>

                <div class="form-group">
                    <label>Actividad Específica</label>
                    <select id="selectActividad" class="form-control"></select>
                </div>

                <div class="form-group">
                    <label>Puntuación</label>
                    <div class="cantidad-range-wrapper">
                        <div class="cantidad-range-container">
                            <span class="rango-limite">1</span>
                            <input type="range" id="cantidadRange" min="1" max="1" value="1">
                            <span class="rango-limite" id="rangoMax">1</span>
                        </div>
                        <div class="rango-valor-central">
                            <span class="rango-valor-badge default" id="rangoValorBadge">1</span>
                        </div>
                    </div>
                    <input type="hidden" id="cantidadHidden" value="1">
                </div>

                <button type="button" class="btn-add-actividad" id="btnAgregarActividad">
                    <i class="fas fa-plus"></i> Añadir a la lista
                </button>
                
                <div id="actividadesList" style="margin-top: 15px;"></div>
                <input type="hidden" name="actividades" id="actividadesInput">
            </div>

            <div class="form-section">
                <div class="form-row">
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" name="fecha" class="form-control" value="<?= date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Hora</label>
                        <input type="time" name="hora" class="form-control" value="<?= date('H:i'); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>Observaciones</label>
                    <textarea name="observaciones" class="form-control" rows="2" placeholder="Opcional..."></textarea>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="btnSubmit" disabled>Notificar ahora</button>
        </form>
    </main>

    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h3>Confirmar Registro</h3>
            <div id="confirmResumen"></div>
            <div class="modal-buttons">
                <button type="button" class="btn-cancelar" id="btnCancelarConfirm">Editar</button>
                <button type="button" class="btn-confirmar" id="btnConfirmarEnvio">Confirmar</button>
            </div>
        </div>
    </div>

    <div class="modal" id="successModal">
        <div class="modal-content success-modal">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: #10b981;"></i>
            <h2>¡Listo!</h2>
            <p>Las actividades han sido registradas.</p>
            <button type="button" class="btn-success" onclick="window.location.href='index.php'">Cerrar</button>
        </div>
    </div>

    <script>
        var catalogoMeritos = <?= json_encode($catalogo_meritos); ?>;
        var catalogoDemeritos = <?= json_encode($catalogo_demeritos); ?>;
    </script>
    <script src="notificar.js"></script>
</body>
</html>