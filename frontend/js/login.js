// ============================================
// LOGIN.JS - Frontend puro (sin PHP)
// ============================================

// CONFIGURACIÓN - CAMBIA ESTA URL POR LA DE TU BACKEND
const BACKEND_URL = 'http://tuservidor.com/backend/api';

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('login-form');
    const errorMessage = document.getElementById('error-message');
    const btnLogin = document.getElementById('btn-login');
    const btnText = btnLogin.querySelector('span');
    const btnSpinner = btnLogin.querySelector('.fa-spinner');

    // Verificar sesión al cargar
    verificarSesion();

    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        
        // Obtener valores
        const nombre = document.getElementById('nombre').value.trim();
        const apellidos = document.getElementById('apellidos').value.trim();
        const password = document.getElementById('password').value;
        const cargo = document.getElementById('cargo').value;

        // Validar
        if (!nombre || !apellidos || !password || !cargo) {
            mostrarError('Todos los campos son obligatorios');
            return;
        }

        // Mostrar loading
        setLoading(true);

        try {
            // Enviar datos al backend
            const formData = new FormData();
            formData.append('nombre', nombre);
            formData.append('apellidos', apellidos);
            formData.append('password', password);
            formData.append('cargo', cargo);

            const response = await fetch(`${BACKEND_URL}/login_procesar.php`, {
                method: 'POST',
                body: formData,
                credentials: 'include' // Importante para cookies de sesión
            });

            // Verificar si hubo redirección (login exitoso)
            if (response.ok) {
                // Login exitoso - redirigir al dashboard
                window.location.href = 'pages/dashboard.html';
            } else {
                const data = await response.json().catch(() => ({}));
                mostrarError(data.error || 'Error en el login');
            }
        } catch (error) {
            console.error('Error:', error);
            mostrarError('Error de conexión. Verifica tu internet.');
        } finally {
            setLoading(false);
        }
    });

    function mostrarError(mensaje) {
        errorMessage.textContent = mensaje;
        errorMessage.style.display = 'block';
        setTimeout(() => {
            errorMessage.style.display = 'none';
        }, 5000);
    }

    function setLoading(loading) {
        if (loading) {
            btnText.style.display = 'none';
            btnSpinner.style.display = 'inline-block';
            btnLogin.disabled = true;
        } else {
            btnText.style.display = 'inline-block';
            btnSpinner.style.display = 'none';
            btnLogin.disabled = false;
        }
    }

    async function verificarSesion() {
        try {
            const response = await fetch(`${BACKEND_URL}/check_session.php`, {
                credentials: 'include'
            });
            const data = await response.json();
            if (data.logueado === true) {
                window.location.href = 'pages/dashboard.html';
            }
        } catch (error) {
            console.log('No hay sesión activa');
        }
    }
});
