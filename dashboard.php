<?php
session_start();
// Protección de ruta: Si no hay sesión, al login
if (!isset($_SESSION['user_id'])) { header("Location: index"); exit; }

// --- LÓGICA DE RE-CONEXIÓN (Repetida para cumplir los 3 archivos) ---
// En producción usarías 'require' aquí, pero lo repetimos para simplificar tus archivos
$host = 'sql211.infinityfree.com'; $db = 'if0_41266869_usuarios_escuela'; $user = 'if0_41266869'; $pass = 'mplus2009'; 
try { $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass); } catch (PDOException $e) { die("Error de DB"); }

$rol = $_SESSION['rol'];
$nombre_completo = $_SESSION['nombre'] . " " . $_SESSION['apellido'];
$grado = $_SESSION['grado'];

// --- LÓGICA DE LOGOUT (Cerrar Sesión) ---
if (isset($_GET['logout'])) { session_destroy(); header("Location: index"); exit; }

// --- LÓGICA DE BÚSQUEDA AJAX (Manejada en el mismo archivo) ---
if (isset($_GET['buscar'])) {
    $q = "%" . $_GET['buscar'] . "%";
    // Solo buscamos estudiantes si el rol es oficial o superior
    if ($rol != 'estudiante') {
        $stmt = $pdo->prepare("SELECT id, nombre, apellido, grado FROM usuarios WHERE (nombre LIKE ? OR apellido LIKE ? OR grado LIKE ?) AND rol = 'estudiante' LIMIT 8");
        $stmt->execute([$q, $q, $q]);
        $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($resultados) {
            foreach ($resultados as $row) {
                echo "<div class='resultado-item' onclick='alert(\"Abriendo Expediente de: ".$row['nombre']."\")'>";
                echo "  <div class='user-info-search'><i class="fas fa-user-graduate"></i> <span>".$row['nombre']." ".$row['apellido']."</span></div>";
                echo "  <div class='grado-badge'>".$row['grado']."</div>";
                echo "</div>";
            }
        } else {
            echo "<div style='color: #8a8f98; text-align: center; padding: 15px;'>No se encontraron estudiantes.</div>";
        }
    }
    exit; // Detiene la carga de HTML para devolver solo la búsqueda
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SYSTEM_NAME; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root { --bg: #f0f2f5; --primary: #2c3e50; --accent: #1abc9c; --merito: #27ae60; --demerito: #e74c3c; --text: #333; --border: #e0e0e0; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: var(--bg); color: var(--text); margin: 0; padding: 0; }
        
        /* Header Visual */
        header { background: var(--primary); color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 10px rgba(0,0,0,0.1); position: sticky; top: 0; z-index: 100; }
        .logo { font-weight: 700; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .user-menu { display: flex; align-items: center; gap: 15px; font-size: 14px; }
        .logout-btn { background: rgba(255,255,255,0.1); color: white; text-decoration: none; padding: 8px 15px; border-radius: 20px; transition: 0.3s; }
        .logout-btn:hover { background: rgba(231, 76, 60, 0.2); }

        /* Contenedor Principal */
        .container { padding: 30px 20px; max-width: 1000px; margin: auto; }
        .welcome-section { margin-bottom: 30px; }
        h2 { margin: 0 0 5px 0; font-weight: 600; }
        .rol-badge { background: var(--accent); color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; }

        /* Tarjetas Principales (Grid Visual) */
        .grid-dashboard { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
        .card { background: white; padding: 25px; border-radius: 18px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); border: 1px solid var(--border); transition: 0.3s; }
        .card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }

        /* Estilos de Tarjetas de Estudiante */
        .promedio-circle { width: 100px; height: 100px; border-radius: 50%; border: 8px solid #e0e0e0; border-top-color: var(--merito); display: flex; justify-content: center; align-items: center; font-size: 24px; font-weight: 700; color: var(--merito); margin: 0 auto 15px; }
        
        /* Botones Gráficos y Táctiles */
        .btn-visual { display: flex; align-items: center; gap: 15px; text-decoration: none; color: white; padding: 20px; border-radius: 15px; margin-top: 15px; font-weight: 600; transition: 0.3s; border: none; width: 100%; font-size: 16px; cursor: pointer; }
        .btn-visual i { font-size: 24px; }
        .btn-qr { background: #34495e; } .btn-qr:hover { background: #2c3e50; }
        .btn-historial { background: var(--primary); } .btn-historial:hover { background: #1a252f; }
        
        /* Estilos de Buscador (Oficial) */
        .search-container { position: relative; }
        .search-input { width: 100%; padding: 15px 20px 15px 45px; border-radius: 25px; border: 1px solid var(--border); box-sizing: border-box; font-size: 16px; outline: none; transition: 0.3s; }
        .search-input:focus { border-color: var(--accent); box-shadow: 0 0 10px rgba(26, 188, 156, 0.1); }
        .search-container i { position: absolute; left: 18px; top: 18px; color: #8a8f98; }
        
        /* Resultados de búsqueda */
        #resultados-busqueda { margin-top: 15px; max-height: 300px; overflow-y: auto; }
        .resultado-item { background: #fff; padding: 15px; border-radius: 10px; border-bottom: 1px solid #f0f2f5; cursor: pointer; display: flex; justify-content: space-between; align-items: center; transition: 0.2s; }
        .resultado-item:hover { background: #edf2f7; transform: scale(1.01); }
        .user-info-search { display: flex; align-items: center; gap: 10px; font-weight: 500; color: #2c3e50;}
        .user-info-search i { color: #8a8f98; }
        .grado-badge { background: #edf2f7; color: #2c3e50; font-size: 11px; padding: 4px 10px; border-radius: 10px; font-weight: 700; }

        /* Tarjetas de Acción de Oficial */
        .action-icon { font-size: 30px; margin-bottom: 15px; display: block; }

        /* Adaptación Móvil */
        @media (max-width: 600px) {
            .grid-dashboard { grid-template-columns: 1fr; }
            header { padding: 15px; } .user-menu span { display: none; }
        }
    </style>
</head>
<body>
    <header>
        <div class="logo"><i class="fas fa-shield-alt"></i> <span>EMCC DIGITAL</span></div>
        <div class="user-menu">
            <span><i class="fas fa-user-circle"></i> <?php echo $nombre_completo; ?></span>
            <a href="dashboard?logout=1" class="logout-btn"><i class="fas fa-sign-out-alt"></i></a>
        </div>
    </header>

    <div class="container">
        <div class="welcome-section">
            <span class="rol-badge"><?php echo $rol; ?></span>
            <h2>Bienvenido, <?php echo $_SESSION['nombre']; ?></h2>
            <p style="color: #666; margin: 0; font-size: 14px;">Aquí tienes tu panel de control disciplinario.</p>
        </div>

        <div class="grid-dashboard">
            <?php if ($rol == 'estudiante'): ?>
                <div class="card" style="text-align: center;">
                    <h3>Mi Comportamiento</h3>
                    <div class="promedio-circle">
                        92%
                    </div>
                    <p style="color: #666; font-size: 14px; margin: 0 0 10px 0;">Semana actual (Mie-Mie)</p>
                    <span style="color:var(--merito); font-weight:700;">120 Méritos</span> / 
                    <span style="color:var(--demerito); font-weight:700;">1 Demérito</span>
                </div>

                <div class="card">
                    <h3>Acciones Rápidas</h3>
                    <button class="btn-visual btn-qr" onclick="alert('Abriendo tu código QR...')">
                        <i class="fas fa-qrcode"></i>
                        <div>
                            <b>Mi Identificación</b><br>
                            <span style="font-size:12px; font-weight:400;">Muestra este código para reportes.</span>
                        </div>
                    </button>

                    <button class="btn-visual btn-historial" onclick="alert('Abriendo historial completo...')">
                        <i class="fas fa-history"></i>
                        <div>
                            <b>Ver Mi Historial</b><br>
                            <span style="font-size:12px; font-weight:400;">Revisa tus méritos y deméritos antiguos.</span>
                        </div>
                    </button>
                </div>
            
            <?php else: ?>
                <div class="card" style="grid-column: 1 / -1;">
                    <h3>Buscador Inteligente de Estudiantes</h3>
                    <div class="search-container">
                        <i class="fas fa-search"></i>
                        <input type="text" id="busqueda" class="search-input" placeholder="Escribe Nombre, Apellido o Grado (ej: 10mo)..." onkeyup="buscarEstudiante()">
                    </div>
                    <div id="resultados-busqueda"></div>
                </div>

                <div class="card" onclick="alert('Iniciando Cámara para Escaneo QR...')" style="cursor:pointer; text-align:center;">
                    <i class="fas fa-camera action-icon" style="color:var(--accent);"></i>
                    <h3>Escanear Código</h3>
                    <p style="color:#666; font-size:14px; margin:0;">Usa la cámara para escanear el QR físico del estudiante.</p>
                </div>

                <div class="card" onclick="alert('Abriendo Tabla de Valores...')" style="cursor:pointer; text-align:center;">
                    <i class="fas fa-list-ol action-icon" style="color:#34495e;"></i>
                    <h3>Tabla de Valores</h3>
                    <p style="color:#666; font-size:14px; margin:0;">Consulta los puntos establecidos para méritos y deméritos.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 50px; font-size: 11px; color: #8a8f98;">
            Sistema de Gestión Disciplinaria EMCC Digital v1.0 | Cuba
        </div>
    </div>

    <script>
    function buscarEstudiante() {
        const input = document.getElementById('busqueda').value;
        const resultadosDiv = document.getElementById('resultados-busqueda');

        if (input.length > 2) { // Buscar desde 3 caracteres
            // Fetch al mismo archivo (mira la lógica PHP arriba)
            fetch(`dashboard.php?buscar=${encodeURIComponent(input)}`)
                .then(response => response.text())
                .then(html => {
                    resultadosDiv.innerHTML = html;
                })
                .catch(error => {
                    console.error('Error:', error);
                    resultadosDiv.innerHTML = "<div style='color:red; text-align:center;'>Error en la búsqueda.</div>";
                });
        } else if (input.length == 0) {
            resultadosDiv.innerHTML = ""; // Limpiar si borra todo
        }
    }
    </script>
</body>
</html>
