// Configuración de la URL del backend
const BACKEND_URL = '../backend/api';

// Verificar sesión al cargar
window.addEventListener('load', function() {
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
    
    document.getElementById('usuarioNombre').textContent = nombre;
    document.getElementById('dropdownNombre').textContent = nombre + ' ' + apellidos;
    document.getElementById('dropdownCargo').textContent = capitalize(cargo);
    document.getElementById('badgeCargo').textContent = capitalize(cargo);
    document.getElementById('qrUserName').textContent = nombre + ' ' + apellidos;
    document.getElementById('qrCi').textContent = ci;
    document.getElementById('qrCargo').textContent = capitalize(cargo);
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
    document.getElementById('lastUpdate').textContent = now.toLocaleDateString('es-ES', options);
}

// Dropdown de usuario
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

// Logout
document.getElementById('logoutBtn').onclick = function(e) {
    e.preventDefault();
    localStorage.clear();
    window.location.href = 'index.html';
};

// Modal QR
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
    };
}

if (qrModal) {
    qrModal.onclick = function(e) {
        if (e.target === qrModal) {
            qrModal.classList.remove('show');
        }
    };
}

function generarQR() {
    const container = document.getElementById('qrCode');
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
