<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../../login.php');
    exit();
}

require_once '../../db.php';

$usuario_cargo = $_SESSION['usuario_cargo'] ?? '';
$usuario_nombre = $_SESSION['usuario_nombre'] ?? '';

// Obtener catálogos
$meritos = [];
$demeritos = [];

$result = $conexion->query("SELECT * FROM meritos ORDER BY categoria, causa");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $meritos[] = $row;
    }
}

$result = $conexion->query("SELECT * FROM demeritos ORDER BY categoria, falta");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $demeritos[] = $row;
    }
}

// Agrupar por categoría
$meritos_por_categoria = [];
foreach ($meritos as $m) {
    $cat = $m['categoria'];
    if (!isset($meritos_por_categoria[$cat])) {
        $meritos_por_categoria[$cat] = [];
    }
    $meritos_por_categoria[$cat][] = $m;
}

$demeritos_por_categoria = [];
foreach ($demeritos as $d) {
    $cat = $d['categoria'];
    if (!isset($demeritos_por_categoria[$cat])) {
        $demeritos_por_categoria[$cat] = [];
    }
    $demeritos_por_categoria[$cat][] = $d;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="theme-color" content="#1e3c72">
    <title>Tabla de Méritos y Deméritos</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../../iconos_vectoriales.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            background: #f0f4f8;
            min-height: 100vh;
            padding-bottom: 30px;
        }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .back-btn {
            background: rgba(255,255,255,0.15);
            border: none;
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            text-decoration: none;
            margin-right: 16px;
            cursor: pointer;
        }
        .header-title {
            color: white;
            font-size: 1.4em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .main-content {
            padding: 20px 16px;
            max-width: 700px;
            margin: 0 auto;
        }
        
        /* Buscador */
        .search-box {
            margin-bottom: 20px;
        }
        .search-wrapper {
            position: relative;
        }
        .search-wrapper .icon-search {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1.2em;
        }
        .search-wrapper input {
            width: 100%;
            padding: 16px 20px 16px 50px;
            border: 2px solid #e2e8f0;
            border-radius: 18px;
            font-family: 'Poppins', sans-serif;
            font-size: 1em;
            background: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .search-wrapper input:focus {
            outline: none;
            border-color: #1e3c72;
            box-shadow: 0 4px 15px rgba(30,60,114,0.1);
        }
        
        /* Tabs */
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tab-btn {
            flex: 1;
            padding: 14px;
            border: none;
            border-radius: 18px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(0,0,0,0.04);
        }
        .tab-btn.merito {
            background: white;
            color: #065f46;
            border: 2px solid #d1fae5;
        }
        .tab-btn.merito.active {
            background: #10b981;
            border-color: #10b981;
            color: white;
        }
        .tab-btn.demerito {
            background: white;
            color: #991b1b;
            border: 2px solid #fee2e2;
        }
        .tab-btn.demerito.active {
            background: #ef4444;
            border-color: #ef4444;
            color: white;
        }
        
        /* Contenido de tabs */
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        
        /* Categoría */
        .categoria-section {
            background: white;
            border-radius: 20px;
            padding: 20px;
            margin-bottom: 18px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .categoria-title {
            color: #1e3c72;
            font-size: 1.2em;
            font-weight: 700;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #e8edf2;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .categoria-title .icon-folder {
            color: #667eea;
            font-size: 1.1em;
        }
        
        /* Items */
        .item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .item-row:last-child {
            border-bottom: none;
        }
        .item-nombre {
            flex: 1;
            font-weight: 500;
            color: #1e293b;
            font-size: 0.95em;
            padding-right: 10px;
        }
        .item-valor {
            font-weight: 700;
            font-size: 1.1em;
            padding: 6px 14px;
            border-radius: 25px;
            display: flex;
            align-items: center;
            gap: 5px;
            white-space: nowrap;
        }
        .item-valor.merito {
            background: #d1fae5;
            color: #065f46;
        }
        .item-valor.demerito {
            background: #fee2e2;
            color: #991b1b;
        }
        .item-valor .icon-trophy,
        .item-valor .icon-warning {
            font-size: 0.9em;
        }
        
        /* Subcategoría */
        .subcategoria {
            margin-left: 0;
            margin-top: 15px;
            margin-bottom: 8px;
            font-weight: 600;
            color: #64748b;
            font-size: 0.9em;
            display: flex;
            align-items: center;
            gap: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .subcategoria .icon-subfolder {
            color: #667eea;
            font-size: 0.9em;
        }
        
        /* Estado vacío */
        .no-results {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        .no-results .icon-search-large {
            font-size: 50px;
            margin-bottom: 15px;
            color: #cbd5e1;
        }
        .no-results h3 {
            font-size: 1.2em;
            margin-bottom: 8px;
            color: #64748b;
        }
        
        /* Contador de resultados */
        .result-count {
            text-align: center;
            padding: 10px;
            color: #64748b;
            font-size: 0.9em;
        }
        
        /* Leyenda */
        .leyenda {
            display: flex;
            justify-content: center;
            gap: 30px;
            margin-top: 25px;
            padding: 15px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }
        .leyenda-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 0.85em;
        }
        .leyenda-color {
            width: 20px;
            height: 20px;
            border-radius: 8px;
        }
        .leyenda-color.merito {
            background: #d1fae5;
            border: 2px solid #10b981;
        }
        .leyenda-color.demerito {
            background: #fee2e2;
            border: 2px solid #ef4444;
        }
        
        
        
        
        .
        .back-btn {
    background: rgba(255,255,255,0.15);
    border: none;
    width: 44px;
    height: 44px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    text-decoration: none;
    margin-right: 16px;
    cursor: pointer;
}

.back-btn span {
    font-size: 24px;
    color: white;
}

.icon-arrow-left::before {
    content: "";
    display: inline-block;
    width: 1em;
    height: 1em;
    background-color: currentColor;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M20 11H7.83l5.59-5.59L12 4l-8 8 8 8 1.41-1.41L7.83 13H20v-2z'/%3E%3C/svg%3E");
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
        
        /* Icono de Carpeta */
.icon-folder::before {
    content: "";
    display: inline-block;
    width: 1em;
    height: 1em;
    background-color: currentColor;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z'/%3E%3C/svg%3E");
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}

/* Icono de Subcarpeta */
.icon-subfolder::before {
    content: "";
    display: inline-block;
    width: 1em;
    height: 1em;
    background-color: currentColor;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M20 6h-8l-2-2H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zm0 12H4V8h16v10z'/%3E%3C/svg%3E");
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}

/* Icono de Tabla Méritos (Tabla con trofeo) */
.icon-tabla-meritos::before {
    content: "";
    display: inline-block;
    width: 1em;
    height: 1em;
    background-color: currentColor;
    -webkit-mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M3 3h18v18H3V3zm2 2v14h14V5H5zm2 2h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z'/%3E%3C/svg%3E");
    mask-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24'%3E%3Cpath d='M3 3h18v18H3V3zm2 2v14h14V5H5zm2 2h10v2H7V7zm0 4h10v2H7v-2zm0 4h7v2H7v-2z'/%3E%3C/svg%3E");
    -webkit-mask-size: contain;
    mask-size: contain;
    -webkit-mask-repeat: no-repeat;
    mask-repeat: no-repeat;
    -webkit-mask-position: center;
    mask-position: center;
}
        
        
        
    </style>
</head>
<body>
<header class="header">
    <a href="../index.php" class="back-btn">
        <span class="icon-arrow-left" style="font-size: 24px;"></span>
    </a>
    <h1 class="header-title">
        <span class="icon-tabla-meritos"></span> 
        Méritos y Deméritos
    </h1>
</header>
    <main class="main-content">
        <!-- Buscador -->
        <div class="search-box">
            <div class="search-wrapper">
                <span class="icon-search"></span>
                <input type="text" id="searchInput" placeholder="🔍 Buscar mérito o demérito...">
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs">
            <button class="tab-btn merito active" id="tabMerito">
                <span class="icon-trophy"></span> Méritos
            </button>
            <button class="tab-btn demerito" id="tabDemerito">
                <span class="icon-warning"></span> Deméritos
            </button>
        </div>

        <!-- TABLA DE MÉRITOS -->
        <div id="contentMerito" class="tab-content active">
            <div id="meritoContainer">
                <?php foreach ($meritos_por_categoria as $categoria => $items): ?>
                <div class="categoria-section merito-section" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                    <h3 class="categoria-title">
                        <span class="icon-folder"></span>
                        <?php echo htmlspecialchars($categoria); ?>
                    </h3>
                    <?php
                    $subcategorias = [];
                    foreach ($items as $item) {
                        $sub = $item['subcategoria'] ?? '';
                        if (!empty($sub)) {
                            if (!isset($subcategorias[$sub])) $subcategorias[$sub] = [];
                            $subcategorias[$sub][] = $item;
                        }
                    }
                    ?>
                    <?php if (!empty($subcategorias)): ?>
                        <?php foreach ($subcategorias as $sub => $subitems): ?>
                        <div class="subcategoria">
                            <span class="icon-subfolder"></span>
                            <?php echo htmlspecialchars($sub); ?>
                        </div>
                        <?php foreach ($subitems as $item): ?>
                        <div class="item-row" data-nombre="<?php echo htmlspecialchars($item['causa']); ?>" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                            <span class="item-nombre"><?php echo htmlspecialchars($item['causa']); ?></span>
                            <span class="item-valor merito">
                                <span class="icon-trophy"></span>
                                +<?php echo $item['meritos']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <?php if (empty($item['subcategoria'])): ?>
                        <div class="item-row" data-nombre="<?php echo htmlspecialchars($item['causa']); ?>" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                            <span class="item-nombre"><?php echo htmlspecialchars($item['causa']); ?></span>
                            <span class="item-valor merito">
                                <span class="icon-trophy"></span>
                                +<?php echo $item['meritos']; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="meritoNoResults" class="no-results" style="display: none;">
                <span class="icon-search-large"></span>
                <h3>No se encontraron méritos</h3>
                <p>Intenta con otro término de búsqueda</p>
            </div>
        </div>

        <!-- TABLA DE DEMÉRITOS -->
        <div id="contentDemerito" class="tab-content">
            <div id="demeritoContainer">
                <?php foreach ($demeritos_por_categoria as $categoria => $items): ?>
                <div class="categoria-section demerito-section" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                    <h3 class="categoria-title">
                        <span class="icon-folder"></span>
                        <?php echo htmlspecialchars($categoria); ?>
                    </h3>
                    <?php
                    $subcategorias = [];
                    foreach ($items as $item) {
                        $sub = $item['subcategoria'] ?? '';
                        if (!empty($sub)) {
                            if (!isset($subcategorias[$sub])) $subcategorias[$sub] = [];
                            $subcategorias[$sub][] = $item;
                        }
                    }
                    ?>
                    <?php if (!empty($subcategorias)): ?>
                        <?php foreach ($subcategorias as $sub => $subitems): ?>
                        <div class="subcategoria">
                            <span class="icon-subfolder"></span>
                            <?php echo htmlspecialchars($sub); ?>
                        </div>
                        <?php foreach ($subitems as $item): ?>
                        <div class="item-row" data-nombre="<?php echo htmlspecialchars($item['falta']); ?>" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                            <span class="item-nombre"><?php echo htmlspecialchars($item['falta']); ?></span>
                            <span class="item-valor demerito">
                                <span class="icon-warning"></span>
                                -<?php echo $item['demeritos_10mo']; ?>
                            </span>
                        </div>
                        <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    <?php foreach ($items as $item): ?>
                        <?php if (empty($item['subcategoria'])): ?>
                        <div class="item-row" data-nombre="<?php echo htmlspecialchars($item['falta']); ?>" data-categoria="<?php echo htmlspecialchars($categoria); ?>">
                            <span class="item-nombre"><?php echo htmlspecialchars($item['falta']); ?></span>
                            <span class="item-valor demerito">
                                <span class="icon-warning"></span>
                                -<?php echo $item['demeritos_10mo']; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="demeritoNoResults" class="no-results" style="display: none;">
                <span class="icon-search-large"></span>
                <h3>No se encontraron deméritos</h3>
                <p>Intenta con otro término de búsqueda</p>
            </div>
        </div>
        
        <!-- Leyenda -->
        <div class="leyenda">
            <div class="leyenda-item">
                <span class="leyenda-color merito"></span>
                <span>Méritos</span>
            </div>
            <div class="leyenda-item">
                <span class="leyenda-color demerito"></span>
                <span>Deméritos</span>
            </div>
        </div>
    </main>

    <script>
        const tabMerito = document.getElementById('tabMerito');
        const tabDemerito = document.getElementById('tabDemerito');
        const contentMerito = document.getElementById('contentMerito');
        const contentDemerito = document.getElementById('contentDemerito');
        const searchInput = document.getElementById('searchInput');
        const meritoContainer = document.getElementById('meritoContainer');
        const demeritoContainer = document.getElementById('demeritoContainer');
        const meritoNoResults = document.getElementById('meritoNoResults');
        const demeritoNoResults = document.getElementById('demeritoNoResults');

        // Cambiar tabs
        tabMerito.addEventListener('click', () => {
            tabMerito.classList.add('active');
            tabDemerito.classList.remove('active');
            contentMerito.classList.add('active');
            contentDemerito.classList.remove('active');
            searchInput.value = '';
            resetSearch();
        });

        tabDemerito.addEventListener('click', () => {
            tabDemerito.classList.add('active');
            tabMerito.classList.remove('active');
            contentDemerito.classList.add('active');
            contentMerito.classList.remove('active');
            searchInput.value = '';
            resetSearch();
        });

        function resetSearch() {
            document.querySelectorAll('.item-row').forEach(row => row.style.display = 'flex');
            document.querySelectorAll('.categoria-section').forEach(section => section.style.display = 'block');
            document.querySelectorAll('.subcategoria').forEach(sub => sub.style.display = 'flex');
            if (meritoNoResults) meritoNoResults.style.display = 'none';
            if (demeritoNoResults) demeritoNoResults.style.display = 'none';
        }

        // Buscador
        searchInput.addEventListener('input', function() {
            const query = this.value.toLowerCase().trim();
            const isMeritoActive = contentMerito.classList.contains('active');
            
            if (query === '') {
                resetSearch();
                return;
            }
            
            if (isMeritoActive) {
                searchInContainer(meritoContainer, query, meritoNoResults);
            } else {
                searchInContainer(demeritoContainer, query, demeritoNoResults);
            }
        });

        function searchInContainer(container, query, noResultsEl) {
            const sections = container.querySelectorAll('.categoria-section');
            let totalVisible = 0;
            
            sections.forEach(section => {
                const rows = section.querySelectorAll('.item-row');
                let sectionVisible = false;
                
                rows.forEach(row => {
                    const nombre = row.dataset.nombre?.toLowerCase() || '';
                    const categoria = row.dataset.categoria?.toLowerCase() || '';
                    
                    if (nombre.includes(query) || categoria.includes(query)) {
                        row.style.display = 'flex';
                        sectionVisible = true;
                        totalVisible++;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                // Mostrar/ocultar subcategorías
                const subcats = section.querySelectorAll('.subcategoria');
                subcats.forEach(sub => {
                    let subHasVisible = false;
                    let nextEl = sub.nextElementSibling;
                    
                    while (nextEl && nextEl.classList.contains('item-row')) {
                        if (nextEl.style.display !== 'none') {
                            subHasVisible = true;
                            break;
                        }
                        nextEl = nextEl.nextElementSibling;
                    }
                    sub.style.display = subHasVisible ? 'flex' : 'none';
                });
                
                section.style.display = sectionVisible ? 'block' : 'none';
            });
            
            if (noResultsEl) {
                noResultsEl.style.display = totalVisible === 0 ? 'block' : 'none';
            }
        }
    </script>
</body>
</html>