// ============================================
// CONFIGURACIÓN DEL BACKEND
// ============================================
const BACKEND_URL = 'https://tarjeta-de-reporte.infinityfree.me/backend/api';

// ============================================
// VERIFICAR SESIÓN AL CARGAR
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    const logueado = localStorage.getItem('logueado');
    
    if (logueado !== 'true') {
        window.location.href = 'index.html';
        return;
    }
    
    // Cargar datos del usuario
    cargarUsuario();
    
    // Actualizar hora
    actualizarHora();
    setInterval(actualizarHora, 1000);
});

function cargarUsuario() {
    const nombre = localStorage.getItem('usuario_nombre') || 'Usuario';
    const apellidos = localStorage.getItem('usuario_apellidos') || '';
    const cargo = localStorage.getItem('usuario_cargo') || 'Cargo';
    const ci = localStorage.getItem('usuario_ci') || '';
    
    const elementos = {
        'usuarioNombre': nombre,
        'dropdownNombre': nombre + ' ' + apellidos,
        'dropdownCargo': capitalize(cargo),
        'badgeCargo': capitalize(cargo),
        'qrUserName': nombre + ' ' + apellidos,
        'qrCi': ci,
        'qrCargo': capitalize(cargo)
    };
    
    for (const [id, valor] of Object.entries(elementos)) {
        const elem = document.getElementById(id);
        if (elem) elem.textContent = valor;
    }
}

function capitalize(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function actualizarHora() {
    const now = new Date();
    const options = { 
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    };
    const elem = document.getElementById('lastUpdate');
    if (elem) elem.textContent = now.toLocaleDateString('es-ES', options);
}

// ============================================
// DROPDOWN DE USUARIO
// ============================================
const accountBtn = document.getElementById('accountBtn');
const accountDropdown = document.getElementById('accountDropdown');
const overlay = document.getElementById('overlay');

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

// ============================================
// LOGOUT
// ============================================
const logoutBtn = document.getElementById('logoutBtn');
if (logoutBtn) {
    logoutBtn.onclick = async function(e) {
        e.preventDefault();
        
        // Llamar al backend para cerrar sesión
        try {
            await fetch(BACKEND_URL + '/logout.php', {
                method: 'POST',
                credentials: 'include'
            });
        } catch (error) {
            console.error('Error al cerrar sesión en backend:', error);
        }
        
        // Limpiar localStorage y redirigir
        localStorage.clear();
        window.location.href = 'index.html';
    };
}

// ============================================
// MODAL QR
// ============================================
const qrBtn = document.getElementById('qrBtn');
const qrModal = document.getElementById('qrModal');
const closeModalBtn = document.getElementById('closeModalBtn');
let qrGenerado = false;

if (qrBtn) {
    qrBtn.onclick = function() {
        qrModal.classList.add('show');
        if (!qrGenerado) {
            setTimeout(generarQR, 100);
        }
    };
}

if (closeModalBtn) {
    closeModalBtn.onclick = function() {
        qrModal.classList.remove('show');
        qrGenerado = false; // Regenerar QR la próxima vez
    };
}

if (qrModal) {
    qrModal.onclick = function(e) {
        if (e.target === qrModal) {
            qrModal.classList.remove('show');
            qrGenerado = false;
        }
    };
}

function generarQR() {
    const container = document.getElementById('qrCode');
    if (!container) return;
    
    container.innerHTML = '';
    
    const usuario = {
        id: localStorage.getItem('usuario_id'),
        nombre: localStorage.getItem('usuario_nombre'),
        apellidos: localStorage.getItem('usuario_apellidos'),
        ci: localStorage.getItem('usuario_ci'),
        cargo: localStorage.getItem('usuario_cargo')
    };
    
    const qrData = JSON.stringify(usuario);
    
    if (typeof QRCode === 'undefined') {
        container.innerHTML = '<p style="color:red;">Error: QR no disponible</p>';
        return;
    }
    
    try {
        new QRCode(container, {
            text: qrData,
            width: 160,
            height: 160,
            colorDark: '#1e3c72',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H
        });
        qrGenerado = true;
    } catch(e) {
        container.innerHTML = '<p style="color:red;">Error al generar QR</p>';
    }
}
