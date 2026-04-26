// Configuración de la URL del backend
const BACKEND_URL = '../backend/api';

document.getElementById('loginForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = {
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
            body: new URLSearchParams(formData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Guardar datos del usuario en localStorage para el frontend
            localStorage.setItem('usuario_id', data.usuario.id);
            localStorage.setItem('usuario_nombre', data.usuario.nombre);
            localStorage.setItem('usuario_apellidos', data.usuario.apellidos);
            localStorage.setItem('usuario_cargo', data.usuario.cargo);
            localStorage.setItem('logueado', 'true');
            
            window.location.href = data.redirect;
        } else {
            errorDiv.textContent = data.message;
            errorDiv.style.display = 'block';
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Iniciar Sesión';
        }
    } catch (error) {
        console.error('Error:', error);
        errorDiv.textContent = 'Error de conexión con el servidor';
        errorDiv.style.display = 'block';
        submitBtn.disabled = false;
        submitBtn.innerHTML = 'Iniciar Sesión';
    }
});

// Verificar si ya hay sesión activa al cargar la página
window.addEventListener('load', function() {
    const logueado = localStorage.getItem('logueado');
    if (logueado === 'true') {
        window.location.href = 'dashboard.html';
    }
});
