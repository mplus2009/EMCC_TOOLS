<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#1e3c72">
    <title>Escanear QR - Login</title>
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
        .header-title { color: white; font-size: 1.4em; font-weight: 600; }
        .scanner-container {
            position: relative;
            width: 100%;
            height: calc(100vh - 78px);
            background: #000;
            overflow: hidden;
        }
        #qrVideo { 
            width: 100% !important; 
            height: 100% !important; 
            object-fit: cover !important;
            display: block !important;
        }
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
        
        /* Mensaje de error/permiso */
        .camera-error {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 30px;
            border-radius: 24px;
            text-align: center;
            max-width: 320px;
            width: 90%;
            z-index: 100;
            display: none;
        }
        .camera-error.show { display: block; }
        .camera-error i { font-size: 50px; color: #ef4444; margin-bottom: 15px; }
        .camera-error h3 { color: #1e293b; margin-bottom: 10px; }
        .camera-error p { color: #64748b; margin-bottom: 20px; }
        .camera-error button {
            background: #1e3c72;
            color: white;
            border: none;
            padding: 14px 25px;
            border-radius: 14px;
            font-size: 1.1em;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
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
        .btn-login-qr {
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
        <a href="login.php" class="back-btn"><i class="fas fa-arrow-left"></i></a>
        <h1 class="header-title">Escanear QR</h1>
    </header>

    <div class="scanner-container" id="scannerContainer">
        <video id="qrVideo" playsinline autoplay muted></video>
        
        <div class="scanner-overlay">
            <div class="scanner-frame">
                <div class="scanner-corner corner-tl"></div>
                <div class="scanner-corner corner-tr"></div>
                <div class="scanner-corner corner-bl"></div>
                <div class="scanner-corner corner-br"></div>
            </div>
            <div class="scanner-text"><span>Coloca el QR dentro del recuadro</span></div>
        </div>
        
        <div class="camera-error" id="cameraError">
            <i class="fas fa-camera-slash"></i>
            <h3>No se pudo acceder a la cámara</h3>
            <p>Verifica que hayas dado permiso y que no haya otra app usando la cámara.</p>
            <button onclick="window.location.reload()"><i class="fas fa-redo"></i> Reintentar</button>
        </div>

        <div class="loading" id="loading">
            <div class="spinner"></div>
            <p>Procesando...</p>
        </div>
    </div>

    <div class="result-modal" id="resultModal">
        <div class="result-content">
            <div class="result-icon" id="resultIcon"><i class="fas fa-check-circle"></i></div>
            <h2 id="resultTitle">¡Usuario Encontrado!</h2>
            <div class="result-info" id="resultInfo"></div>
            <div class="result-buttons">
                <button class="btn-login-qr" id="btnIniciarSesion">Iniciar Sesión</button>
                <button class="btn-escanear-otro" id="btnEscanearOtro">Escanear Otro</button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>
    
    <script>
    const qrVideo = document.getElementById('qrVideo');
    const cameraError = document.getElementById('cameraError');
    const loading = document.getElementById('loading');
    const resultModal = document.getElementById('resultModal');
    const resultIcon = document.getElementById('resultIcon');
    const resultTitle = document.getElementById('resultTitle');
    const resultInfo = document.getElementById('resultInfo');
    const btnIniciarSesion = document.getElementById('btnIniciarSesion');
    const btnEscanearOtro = document.getElementById('btnEscanearOtro');
    
    let stream = null;
    let scanning = true;
    let usuarioEncontrado = null;

    // Mostrar error de cámara
    function showCameraError() {
        cameraError.classList.add('show');
    }

    // Mostrar resultado
    function mostrarResultado(usuario) {
        loading.classList.remove('show');
        usuarioEncontrado = usuario;
        resultIcon.className = 'result-icon';
        resultIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
        resultTitle.textContent = '¡Usuario Encontrado!';
        
        // Mostrar todos los datos disponibles
        let html = `
            <div class="result-info-item"><span class="result-info-label">Nombre:</span><span class="result-info-value">${usuario.nombre || ''} ${usuario.apellidos || ''}</span></div>
            <div class="result-info-item"><span class="result-info-label">CI:</span><span class="result-info-value">${usuario.ci || ''}</span></div>
            <div class="result-info-item"><span class="result-info-label">Cargo:</span><span class="result-info-value">${usuario.cargo || 'estudiante'}</span></div>
        `;
        
        if (usuario.id) {
            html += `<div class="result-info-item"><span class="result-info-label">ID:</span><span class="result-info-value">${usuario.id}</span></div>`;
        }
        
        resultInfo.innerHTML = html;
        resultModal.classList.add('show');
    }

    // Mostrar error de QR
    function mostrarError(mensaje) {
        loading.classList.remove('show');
        resultIcon.className = 'result-icon error';
        resultIcon.innerHTML = '<i class="fas fa-exclamation-triangle"></i>';
        resultTitle.textContent = 'QR No Válido';
        resultInfo.innerHTML = `<p style="text-align: center; color: #64748b;">${mensaje}</p>`;
        resultModal.classList.add('show');
    }

    // Escanear QR
    function scanLoop() {
        if (!scanning || !qrVideo || !stream) return;
        
        if (qrVideo.readyState === qrVideo.HAVE_ENOUGH_DATA) {
            try {
                const canvas = document.createElement('canvas');
                canvas.width = qrVideo.videoWidth;
                canvas.height = qrVideo.videoHeight;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(qrVideo, 0, 0, canvas.width, canvas.height);
                
                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                const code = jsQR(imageData.data, imageData.width, imageData.height);
                
                if (code) {
                    scanning = false;
                    loading.classList.add('show');
                    
                    try {
                        let qrText = code.data;
                        console.log('QR escaneado:', qrText);
                        let usuario = null;
                        
                        // Intentar múltiples formas de decodificar
                        try { 
                            usuario = JSON.parse(atob(qrText)); 
                        } catch (e) {
                            try { 
                                usuario = JSON.parse(atob(qrText.replace(/-/g, '+').replace(/_/g, '/'))); 
                            } catch (e2) {
                                try { 
                                    usuario = JSON.parse(qrText); 
                                } catch (e3) {
                                    throw new Error('Formato no reconocido');
                                }
                            }
                        }
                        
                        console.log('Usuario decodificado:', usuario);
                        
                        // Verificar que tenga al menos ID o (nombre + apellidos + CI)
                        if (usuario && (usuario.id || (usuario.nombre && usuario.apellidos && usuario.ci))) {
                            if (!usuario.cargo) usuario.cargo = 'estudiante';
                            mostrarResultado(usuario);
                        } else {
                            throw new Error('Datos incompletos en el QR');
                        }
                    } catch (e) {
                        console.error('Error:', e);
                        mostrarError('El QR no contiene datos válidos del sistema');
                    }
                    return;
                }
            } catch (e) {
                console.error('Error en escaneo:', e);
            }
        }
        
        requestAnimationFrame(scanLoop);
    }
    
    
            // Iniciar cámara
        async function startCamera() {
            try {
                // Intentar con cámara trasera
                stream = await navigator.mediaDevices.getUserMedia({
                    video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } }
                });
                
                qrVideo.srcObject = stream;
                qrVideo.setAttribute('playsinline', '');
                qrVideo.setAttribute('autoplay', '');
                qrVideo.setAttribute('muted', '');
                
                await qrVideo.play();
                
                cameraError.classList.remove('show');
                scanning = true;
                requestAnimationFrame(scanLoop);
                
            } catch (err) {
                console.error('Error con cámara trasera:', err);
                
                // Intentar con cualquier cámara
                try {
                    stream = await navigator.mediaDevices.getUserMedia({
                        video: { width: { ideal: 1280 }, height: { ideal: 720 } }
                    });
                    
                    qrVideo.srcObject = stream;
                    await qrVideo.play();
                    
                    cameraError.classList.remove('show');
                    scanning = true;
                    requestAnimationFrame(scanLoop);
                    
                } catch (err2) {
                    console.error('Error con cualquier cámara:', err2);
                    showCameraError();
                }
            }
        }

        // Detener cámara
        function stopCamera() {
            scanning = false;
            if (stream) {
                stream.getTracks().forEach(track => track.stop());
                stream = null;
            }
        }

        // Eventos de botones
        btnIniciarSesion.addEventListener('click', () => {
            if (usuarioEncontrado) {
                const jsonStr = JSON.stringify(usuarioEncontrado);
                const base64 = btoa(unescape(encodeURIComponent(jsonStr)));
                window.location.href = 'login.php?qr=' + encodeURIComponent(base64);
            }
        });

        btnEscanearOtro.addEventListener('click', () => {
            resultModal.classList.remove('show');
            usuarioEncontrado = null;
            scanning = true;
            requestAnimationFrame(scanLoop);
        });

        // Iniciar al cargar
        window.addEventListener('load', () => {
            startCamera();
        });

        // Limpiar al salir
        window.addEventListener('beforeunload', () => {
            stopCamera();
        });
    </script>
</body>
</html>