// ============================================
// CONFIGURACIÓN DEL BACKEND
// ============================================
// IMPORTANTE: Cambia esta URL según donde tengas el backend
// Para InfinityFree: https://tarjeta-de-reporte.infinityfree.me/backend/api
// Para local con KSWEB: http://192.168.x.x/backend/api (tu IP local)
// Para GitHub Pages + InfinityFree: usa la URL completa de InfinityFree

const BACKEND_URL = 'https://tarjeta-de-reporte.infinityfree.me/backend/api';

// ============================================
// FUNCIONES DE LOGIN
// ============================================

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    
    if (loginForm) {
        loginForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = {
                ci: document.getElementById('ci').value,
                nombre: document.getElementById('nombre').value,
                apellidos: document.getElementById('apellidos').value,
                password: document.getElementById('password').value,
                cargo: document.getElementById('cargo').value
            };
            
            const errorDiv = document.getElementById('errorMessage');
            const submitBtn = document.querySelector('.btn-login');
            
            // Ocultar error previo
            errorDiv.style.display = 'none';
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Iniciando sesión...';
            
            try {
                const response = await fetch(BACKEND_URL + '/login_procesar.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(formData),
                    credentials: 'include' // Importante para cookies/sesiones
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Guardar datos del usuario en localStorage
                    localStorage.setItem('usuario_id', data.usuario.id);
                    localStorage.setItem('usuario_nombre', data.usuario.nombre);
                    localStorage.setItem('usuario_apellidos', data.usuario.apellidos);
                    localStorage.setItem('usuario_ci', data.usuario.ci || '');
                    localStorage.setItem('usuario_cargo', data.usuario.cargo);
                    localStorage.setItem('logueado', 'true');
                    
                    window.location.href = 'dashboard.html';
                } else {
                    errorDiv.textContent = data.message;
                    errorDiv.style.display = 'block';
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Iniciar Sesión';
                }
            } catch (error) {
                console.error('Error:', error);
                errorDiv.textContent = 'Error de conexión con el servidor. Verifica tu conexión a internet.';
                errorDiv.style.display = 'block';
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Iniciar Sesión';
            }
        });
    }
    
    // Verificar si ya hay sesión activa al cargar
    verificarSesionActiva();
});

function verificarSesionActiva() {
    const logueado = localStorage.getItem('logueado');
    if (logueado === 'true') {
        window.location.href = 'dashboard.html';
    }
}

function abrirEscanerQR() {
    // Abrir el escáner QR del backend en una nueva ventana
    window.open(BACKEND_URL + '/escaner_qr_login.php', '_blank');
}
