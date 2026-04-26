<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['logueado']) || $_SESSION['logueado'] !== true) {
    header('Location: ../login.php');
    exit();
}

$usuario_actual_id = $_SESSION['usuario_id'] ?? 0;
$usuario_actual_ci = $_SESSION['usuario_ci'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <title>Escanear QR - Sistema Escolar</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #000; min-height: 100vh; }
        .header {
            background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
            padding: 16px 20px;
            display: flex;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 100;
        }
        .back-btn {
            background: rgba(255, 255, 255, 0.15);
            border: none;
            width: 46px;
            height: 46px;
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
        .header-title { color: white; font-size: 1.4em; font-weight: 600; flex: 1; }
        .header-right { display: flex; align-items: center; gap: 10px; }
        .counter-badge {
            background: #10b981;
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .scanner-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 78px);
            background: #000;
            overflow: hidden;
        }
        #qrReader { width: 100% !important; height: 100% !important; }
        #qrReader video { width: 100% !important; height: 100% !important; object-fit: cover !important; }
        .scanner-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 10;
        }
        .scanner-frame {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 250px;
            height: 250px;
            border: 3px solid #10b981;
            border-radius: 20px;
            box-shadow: 0 0 0 2000px rgba(0, 0, 0, 0.5);
        }
        .scanner-corner { position: absolute; width: 25px; height: 25px; border-color: white; border-style: solid; }
        .corner-tl { top: -3px; left: -3px; border-width: 4px 0 0 4px; border-radius: 8px 0 0 0; }
        .corner-tr { top: -3px; right: -3px; border-width: 4px 4px 0 0; border-radius: 0 8px 0 0; }
        .corner-bl { bottom: -3px; left: -3px; border-width: 0 0 4px 4px; border-radius: 0 0 0 8px; }
        .corner-br { bottom: -3px; right: -3px; border-width: 0 4px 4px 0; border-radius: 0 0 8px 0; }
        .focus-hint {
            position: absolute;
            bottom: 130px;
            left: 0;
            width: 100%;
            text-align: center;
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.9em;
            pointer-events: none;
            z-index: 15;
        }
        .scanner-text {
            position: absolute;
            bottom: 70px;
            left: 0;
            width: 100%;
            text-align: center;
            color: white;
            font-size: 1em;
            pointer-events: none;
        }
        .scanner-text span { background: rgba(0, 0, 0, 0.6); padding: 8px 20px; border-radius: 40px; }
        .scanner-actions {
            position: absolute;
            bottom: 20px;
            right: 20px;
            display: flex;
            gap: 12px;
            z-index: 20;
        }
        .scanner-btn {
            background: rgba(255, 255, 255, 0.9);
            border: none;
            width: 50px;
            height: 50px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            color: #1e3c72;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
        }
        .scanner-btn.torch { background: #fbbf24; color: #000; }
        .scanner-btn.torch.active { background: #f59e0b; color: white; }
        .scanned-list {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            background: white;
            border-radius: 24px 24px 0 0;
            padding: 16px;
            max-height: 250px;
            overflow-y: auto;
            z-index: 20;
            transform: translateY(100%);
            transition: transform 0.3s ease;
            box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.1);
        }
        .scanned-list.show { transform: translateY(0); }
        .scanned-list h3 { color: #1e3c72; font-size: 1.1em; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
        .scanned-items { display: flex; flex-wrap: wrap; gap: 8px; }
        .scanned-tag {
            background: #e8f0fe;
            color: #1e3c72;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .scanned-tag i { cursor: pointer; color: #ef4444; font-size: 1.1em; }
        .btn-finalizar {
            width: 100%;
            padding: 14px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 1.1em;
            font-weight: 600;
            margin-top: 12px;
            cursor: pointer;
        }
        .result-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(6px);
            justify-content: center;
            align-items: flex-end;
            z-index: 200;
        }
        .result-modal.show { display: flex; }
        .result-content {
            background: white;
            border-radius: 36px 36px 0 0;
            padding: 32px 24px;
            width: 100%;
            max-width: 480px;
            animation: slideUp 0.25s ease;
        }
        @keyframes slideUp { from { transform: translateY(100%); } to { transform: translateY(0); } }
        .result-icon { font-size: 60px; color: #10b981; text-align: center; margin-bottom: 15px; }
        .result-icon.error { color: #ef4444; }
        .result-content h2 { color: #1e3c72; margin-bottom: 20px; text-align: center; font-size: 1.5em; }
        .result-info { background: #f8fafc; padding: 20px; border-radius: 20px; margin-bottom: 25px; }
        .result-info-item { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e2e8f0; }
        .result-info-item:last-child { border-bottom: none; }
        .result-info-label { color: #64748b; font-weight: 500; font-size: 1em; }
        .result-info-value { color: #1e293b; font-weight: 600; font-size: 1em; }
        .result-buttons { display: flex; gap: 12px; }
        .btn-agregar {
            flex: 1;
            padding: 18px;
            background: #10b981;
            color: white;
            border: none;
            border-radius: 16px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-escanear-otro {
            flex: 1;
            padding: 18px;
            background: #e2e8f0;
            color: #64748b;
            border: none;
            border-radius: 16px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
        }
        .loading {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: white;
            text-align: center;
            z-index: 50;
        }
        .loading.show { display: block; }
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>
    <header class="header">
        <a href="notificar.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="header-title">Escanear QR</h1>
        <div class="header-right">
            <div class="counter-badge" id="counterBadge">
                <i class="fas fa-users"></i>
                <span id="scannedCount">0</span>
            </div>
        </div>
    </header>

    <div class="scanner-container" id="scannerContainer">
        <div id="qrReader"></div>
        <div class="scanner-overlay">
            <div class="scanner-frame">
                <div class="scanner-corner corner-tl"></div>
                <div class="scanner-corner corner-tr"></div>
                <div class="scanner-corner corner-bl"></div>
                <div class="scanner-corner corner-br"></div>
            </div>
            <div class="focus-hint"><i class="fas fa-hand-pointer"></i> Toca la pantalla para enfocar</div>
            <div class="scanner-text"><span>Coloca el QR dentro del recuadro</span></div>
        </div>

        <div class="scanner-actions">
            <button class="scanner-btn" id="switchCameraBtn" style="display: none;"><i class="fas fa-camera-rotate"></i></button>
            <button class="scanner-btn torch" id="torchBtn" style="display: none;"><i class="fas fa-flashlight"></i></button>
        </div>

        <div class="scanned-list" id="scannedList">
            <h3><i class="fas fa-user-check"></i> Estudiantes escaneados (<span id="listCount">0</span>)</h3>
            <div class="scanned-items" id="scannedItems"></div>
            <button class="btn-finalizar" id="btnFinalizar"><i class="fas fa-check-circle"></i> Finalizar y Continuar</button>
        </div>

        <div class="loading" id="loading"><div class="spinner"></div><p>Procesando...</p></div>
    </div>

    <div class="result-modal" id="resultModal">
        <div class="result-content">
            <div class="result-icon" id="resultIcon"><i class="fas fa-check-circle"></i></div>
            <h2 id="resultTitle">¡Usuario Encontrado!</h2>
            <div class="result-info" id="resultInfo"></div>
            <div class="result-buttons">
                <button class="btn-agregar" id="btnAgregarYSeguir"><i class="fas fa-plus-circle"></i> Agregar y Seguir</button>
                <button class="btn-escanear-otro" id="btnSoloAgregar"><i class="fas fa-check"></i> Solo Agregar</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
    const urlParams = new URLSearchParams(window.location.search);
    const mode = urlParams.get('mode') || 'destinatario';
    
    const usuarioActualId = <?php echo json_encode($usuario_actual_id); ?>;
    const usuarioActualCI = <?php echo json_encode($usuario_actual_ci); ?>;
    
    let stream = null, video = null, canvas = null, ctx = null;
    let scanning = false, animationId = null;
    let currentFacingMode = 'environment';
    let torchEnabled = false;
    let usuarioEncontrado = null;
    let scannedUsers = [];
    
    const qrReader = document.getElementById('qrReader');
    const scannerContainer = document.getElementById('scannerContainer');
    const switchCameraBtn = document.getElementById('switchCameraBtn');
    const torchBtn = document.getElementById('torchBtn');
    const resultModal = document.getElementById('resultModal');
    const resultIcon = document.getElementById('resultIcon');
    const resultTitle = document.getElementById('resultTitle');
    const resultInfo = document.getElementById('resultInfo');
    const btnAgregarYSeguir = document.getElementById('btnAgregarYSeguir');
    const btnSoloAgregar = document.getElementById('btnSoloAgregar');
    const loading = document.getElementById('loading');
    const scannedList = document.getElementById('scannedList');
    const scannedItems = document.getElementById('scannedItems');
    const scannedCount = document.getElementById('scannedCount');
    const listCount = document.getElementById('listCount');
    const btnFinalizar = document.getElementById('btnFinalizar');

    // ============================================
    // FUNCIONES DE ENFOQUE Y LINTERNA
    // ============================================
    function enfocarCamara() {
        if (video && stream) {
            const track = stream.getVideoTracks()[0];
            if (track) {
                track.applyConstraints({ advanced: [{ focusMode: 'continuous' }] })
                    .catch(() => track.applyConstraints({ advanced: [{ focusMode: 'auto' }] }).catch(() => {}));
            }
        }
    }

    async function toggleTorch() {
        if (stream) {
            const track = stream.getVideoTracks()[0];
            if (track && track.getCapabilities().torch) {
                try {
                    if (torchEnabled) {
                        await track.applyConstraints({ advanced: [{ torch: false }] });
                        torchEnabled = false;
                        torchBtn.classList.remove('active');
                    } else {
                        await track.applyConstraints({ advanced: [{ torch: true }] });
                        torchEnabled = true;
                        torchBtn.classList.add('active');
                    }
                } catch (e) {}
            }
        }
    }

    scannerContainer.addEventListener('click', (e) => {
        if (!e.target.closest('.scanner-btn') && !e.target.closest('.btn-finalizar')) {
            enfocarCamara();
        }
    });

    // ============================================
    // LISTA DE USUARIOS ESCANEADOS
    // ============================================
    function actualizarLista() {
        const count = scannedUsers.length;
        scannedCount.textContent = count;
        listCount.textContent = count;
        scannedList.classList.toggle('show', count > 0);
        
        scannedItems.innerHTML = '';
        scannedUsers.forEach((user, index) => {
            const tag = document.createElement('div');
            tag.className = 'scanned-tag';
            tag.innerHTML = `${user.nombre} ${user.apellidos} (${user.ci}) <i class="fas fa-times-circle" onclick="eliminarUsuario(${index})"></i>`;
            scannedItems.appendChild(tag);
        });
    }
    
    window.eliminarUsuario = function(index) {
        scannedUsers.splice(index, 1);
        actualizarLista();
    };
    
    function agregarUsuario(usuario) {
        if (usuario.id && usuarioActualId && String(usuario.id) === String(usuarioActualId)) {
            alert('No puedes agregarte a ti mismo como destinatario.');
            return false;
        }
        if (usuario.ci && usuarioActualCI && String(usuario.ci).trim() === String(usuarioActualCI).trim()) {
            alert('No puedes agregarte a ti mismo como destinatario.');
            return false;
        }
        const existe = scannedUsers.some(u => 
            (u.id && usuario.id && String(u.id) === String(usuario.id)) || 
            (u.ci && usuario.ci && String(u.ci).trim() === String(usuario.ci).trim())
        );
        if (!existe) {
            scannedUsers.push(usuario);
            actualizarLista();
            return true;
        }
        return false;
    }
            // ============================================
        // ESCANEO QR
        // ============================================
        function mostrarResultado(usuario) {
            loading.classList.remove('show');
            
            if (usuario.id && usuarioActualId && String(usuario.id) === String(usuarioActualId)) {
                resultIcon.className = 'result-icon error';
                resultIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                resultTitle.textContent = '¡No permitido!';
                resultInfo.innerHTML = '<p style="text-align: center;">No puedes escanearte a ti mismo.</p>';
                resultModal.classList.add('show');
                usuarioEncontrado = null;
                return;
            }
            
            if (usuario.ci && usuarioActualCI && String(usuario.ci).trim() === String(usuarioActualCI).trim()) {
                resultIcon.className = 'result-icon error';
                resultIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                resultTitle.textContent = '¡No permitido!';
                resultInfo.innerHTML = '<p style="text-align: center;">No puedes escanearte a ti mismo.</p>';
                resultModal.classList.add('show');
                usuarioEncontrado = null;
                return;
            }
            
            if (usuario.cargo && usuario.cargo !== 'estudiante') {
                resultIcon.className = 'result-icon error';
                resultIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
                resultTitle.textContent = '¡Solo estudiantes!';
                resultInfo.innerHTML = '<p style="text-align: center;">Solo puedes notificar a estudiantes.</p>';
                resultModal.classList.add('show');
                usuarioEncontrado = null;
                return;
            }
            
            usuarioEncontrado = usuario;
            resultIcon.className = 'result-icon';
            resultIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
            resultTitle.textContent = '¡Usuario Encontrado!';
            resultInfo.innerHTML = `
                <div class="result-info-item"><span>Nombre:</span><span>${usuario.nombre || ''} ${usuario.apellidos || ''}</span></div>
                <div class="result-info-item"><span>CI:</span><span>${usuario.ci || ''}</span></div>
                <div class="result-info-item"><span>Cargo:</span><span>${usuario.cargo || 'estudiante'}</span></div>
                ${usuario.id ? `<div class="result-info-item"><span>ID:</span><span>${usuario.id}</span></div>` : ''}
            `;
            resultModal.classList.add('show');
        }

        function mostrarError(mensaje) {
            loading.classList.remove('show');
            resultIcon.className = 'result-icon error';
            resultIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
            resultTitle.textContent = 'QR No Válido';
            resultInfo.innerHTML = `<p style="text-align: center;">${mensaje}</p>`;
            resultModal.classList.add('show');
        }

        function scanQRCode() {
            if (!scanning || !video) return;
            if (video.readyState === video.HAVE_ENOUGH_DATA) {
                canvas.width = video.videoWidth;
                canvas.height = video.videoHeight;
                try {
                    ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const code = jsQR(imageData.data, imageData.width, imageData.height, { inversionAttempts: "dontInvert" });
                    if (code) {
                        scanning = false;
                        if (animationId) { cancelAnimationFrame(animationId); animationId = null; }
                        loading.classList.add('show');
                        try {
                            let qrText = code.data;
                            let usuario = null;
                            try { usuario = JSON.parse(atob(qrText)); } catch (e) {
                                try { usuario = JSON.parse(atob(qrText.replace(/-/g, '+').replace(/_/g, '/'))); } catch (e2) {
                                    try { usuario = JSON.parse(qrText); } catch (e3) {
                                        try { usuario = JSON.parse(decodeURIComponent(escape(atob(qrText)))); } catch (e4) {
                                            throw new Error('Formato no reconocido');
                                        }
                                    }
                                }
                            }
                            if (usuario && (usuario.id || (usuario.nombre && usuario.apellidos && usuario.ci))) {
                                if (!usuario.cargo) usuario.cargo = 'estudiante';
                                mostrarResultado(usuario);
                            } else {
                                throw new Error('Datos incompletos');
                            }
                        } catch (e) {
                            mostrarError('El QR no contiene datos válidos');
                        }
                        return;
                    }
                } catch (e) {}
            }
            setTimeout(() => { if (scanning) animationId = requestAnimationFrame(scanQRCode); }, 50);
        }

        // ============================================
        // INICIO DE CÁMARA
        // ============================================
        async function startScanner() {
            if (scanning) return;
            try {
                if (stream) stream.getTracks().forEach(t => t.stop());
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: currentFacingMode, width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                video = document.createElement('video');
                video.setAttribute('playsinline', '');
                video.setAttribute('autoplay', '');
                video.setAttribute('muted', '');
                video.style.width = '100%';
                video.style.height = '100%';
                video.style.objectFit = 'cover';
                qrReader.innerHTML = '';
                qrReader.appendChild(video);
                video.srcObject = stream;
                await video.play();
                canvas = document.createElement('canvas');
                ctx = canvas.getContext('2d', { willReadFrequently: true });
                scanning = true;
                animationId = requestAnimationFrame(scanQRCode);
                
                const devices = await navigator.mediaDevices.enumerateDevices();
                if (devices.filter(d => d.kind === 'videoinput').length > 1) switchCameraBtn.style.display = 'flex';
                const track = stream.getVideoTracks()[0];
                if (track && track.getCapabilities().torch) torchBtn.style.display = 'flex';
            } catch (err) {
                alert('Error al acceder a la cámara');
            }
        }

        function stopScanner() {
            scanning = false;
            if (animationId) { cancelAnimationFrame(animationId); animationId = null; }
            if (stream) { stream.getTracks().forEach(t => t.stop()); stream = null; }
        }

        async function switchCamera() {
            stopScanner();
            currentFacingMode = currentFacingMode === 'environment' ? 'user' : 'environment';
            torchEnabled = false;
            torchBtn.classList.remove('active');
            setTimeout(startScanner, 200);
        }

        switchCameraBtn.addEventListener('click', switchCamera);
        torchBtn.addEventListener('click', toggleTorch);

        btnAgregarYSeguir.addEventListener('click', () => {
            if (usuarioEncontrado) agregarUsuario(usuarioEncontrado);
            resultModal.classList.remove('show');
            usuarioEncontrado = null;
            stopScanner();
            setTimeout(startScanner, 200);
        });

        btnSoloAgregar.addEventListener('click', () => {
            if (usuarioEncontrado) agregarUsuario(usuarioEncontrado);
            resultModal.classList.remove('show');
            usuarioEncontrado = null;
        });
// CORREGIDO: Pasar datos por URL en lugar de sessionStorage
btnFinalizar.addEventListener('click', () => {
    if (scannedUsers.length > 0) {
        // Convertir a JSON y codificar para URL
        const jsonUsers = JSON.stringify(scannedUsers);
        const encodedUsers = btoa(unescape(encodeURIComponent(jsonUsers)));
        window.location.href = 'notificar.php?escaneados=' + encodedUsers;
    } else {
        window.location.href = 'notificar.php';
    }
});

        window.addEventListener('load', () => {
            const prevUsers = sessionStorage.getItem('usuariosEscaneados');
            if (prevUsers) {
                try { scannedUsers = JSON.parse(prevUsers); actualizarLista(); } catch (e) {}
                sessionStorage.removeItem('usuariosEscaneados');
            }
            startScanner();
        });

        window.addEventListener('beforeunload', stopScanner);
    </script>
</body>
</html>