# Sistema Escolar - Separación Frontend/Backend Completada

## 📋 Resumen

El proyecto ha sido separado en dos componentes independientes:

### 🔙 **BACKEND (PHP - Para el Servidor)**
- **Ubicación:** `/workspace/backend/`
- **Contiene:** Toda la lógica de servidor, procesamiento de datos, acceso a base de datos
- **Tecnología:** PHP puro
- **Se sube a:** Tu hosting/servidor

### 🎨 **FRONTEND (HTML/CSS/JS - Para Web y Móvil)**
- **Ubicación:** `/workspace/frontend/`
- **Contiene:** Interfaz de usuario, estilos, JavaScript
- **Tecnología:** HTML5, CSS3, JavaScript puro (SIN PHP)
- **Se usa en:** Sitio web estático o aplicación móvil

---

## 📁 Estructura del Proyecto

```
/workspace/
├── backend/                    # SUBIR AL SERVIDOR
│   ├── api/                    # Endpoints y procesamiento
│   │   ├── login_procesar.php
│   │   ├── logout.php
│   │   ├── check_session.php
│   │   ├── dashboard_procesar.php
│   │   ├── notificar_procesar.php
│   │   ├── buscar_usuario.php
│   │   ├── buscar_actividad.php
│   │   └── ... (todos los archivos de procesamiento)
│   ├── config/                 # Configuración
│   │   └── db.php             # Conexión a base de datos
│   └── includes/              # Archivos compartidos
│       └── config_sesion.php
│
├── frontend/                   # PARA WEB/MÓVIL
│   ├── index.html             # Página principal (redirecciona)
│   ├── login.html             # Login (HTML puro)
│   ├── css/                   # Hojas de estilo
│   │   ├── login_estilos.css
│   │   ├── dashboard_estilos.css
│   │   ├── notificar_estilos.css
│   │   └── iconos_vectoriales.css
│   ├── js/                    # JavaScript
│   │   ├── login.js
│   │   └── notificar.js
│   └── pages/                 # Vistas HTML
│       ├── dashboard.html
│       └── ...
│
└── SEPARACION_FRONTEND_BACKEND.md  # Este archivo
```

---

## 🚀 Implementación

### Paso 1: Subir Backend al Servidor

1. **Sube todo el contenido de `/backend/`** a tu hosting
2. **Configura las credenciales de base de datos** en `backend/config/db.php`:
   ```php
   $host = 'tu_host';
   $usuario_db = 'tu_usuario';
   $password_db = 'tu_contraseña';
   $nombre_db = 'tu_base_de_datos';
   ```
3. **Verifica permisos** (644 para archivos PHP)

### Paso 2: Configurar CORS en el Backend

Para que el frontend pueda consumir la API desde diferentes dominios, añade esto al inicio de CADA archivo PHP del backend:

```php
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Credentials: true");
```

O crea un archivo `.htaccess` en la carpeta del backend:
```apache
Header set Access-Control-Allow-Origin "*"
Header set Access-Control-Allow-Methods "POST, GET, OPTIONS"
Header set Access-Control-Allow-Headers "Content-Type"
Header set Access-Control-Allow-Credentials "true"
```

### Paso 3: Configurar el Frontend

1. **Edita `/frontend/js/login.js`** y cambia la URL del backend:
   ```javascript
   const BACKEND_URL = 'https://tusitio.com/backend/api';
   ```

2. **Sube el frontend** a:
   - Un hosting estático (Netlify, Vercel, GitHub Pages)
   - O el mismo servidor pero en otro dominio/subdominio
   - O intégralo en una app móvil (WebView, Cordova, Capacitor)

---

## 🔗 Endpoints del Backend

### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/login_procesar.php` | Iniciar sesión |
| POST | `/api/logout.php` | Cerrar sesión |
| GET | `/api/check_session.php` | Verificar sesión |

### Dashboard
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/dashboard_procesar.php` | Obtener datos del dashboard |
| GET | `/api/buscar_usuario.php` | Buscar usuarios |
| GET | `/api/buscar_actividad.php` | Buscar actividades |

### Notificaciones
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/notificar_procesar.php` | Enviar notificaciones |
| GET | `/api/marcar_leido.php` | Marcar como leído |

---

## 📱 Uso en Aplicaciones Móviles

### Opción A: WebView
```javascript
// El frontend HTML/JS se carga en una WebView nativa
// Usa Apache Cordova o Capacitor
```

### Opción B: PWA (Progressive Web App)
1. Añade `manifest.json` al frontend
2. Implementa service workers
3. Los usuarios pueden instalar la web app

---

## ⚠️ Notas Importantes

1. **HTTPS obligatorio** en producción para seguridad de sesiones
2. **CORS configurado correctamente** para permitir peticiones cruzadas
3. **Cookies de sesión** deben estar habilitadas (`credentials: 'include'`)
4. **La base de datos** solo es accesible desde el backend
5. **El frontend NO tiene acceso directo** a la base de datos

---

## 🔄 Flujo de Funcionamiento

```
┌─────────────────┐
│   FRONTEND      │
│ (HTML/CSS/JS)   │
│ Web / Móvil     │
└────────┬────────┘
         │ HTTP/Fetch
         ▼
┌─────────────────┐
│    BACKEND      │
│     (PHP)       │
│   En Servidor   │
└────────┬────────┘
         │ SQL
         ▼
┌─────────────────┐
│  BASE DE DATOS  │
│    MySQL        │
└─────────────────┘
```

---

## 🛠️ Ejemplo de Código Frontend

### login.html
```html
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login</title>
    <link rel="stylesheet" href="css/login_estilos.css">
</head>
<body>
    <form id="login-form">
        <input type="text" id="nombre" placeholder="Nombre">
        <input type="password" id="password" placeholder="Contraseña">
        <select id="cargo">
            <option value="estudiante">Estudiante</option>
            <option value="profesor">Profesor</option>
        </select>
        <button type="submit">Entrar</button>
    </form>
    <script src="js/login.js"></script>
</body>
</html>
```

### login.js
```javascript
const BACKEND_URL = 'https://tusitio.com/backend/api';

document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('nombre', document.getElementById('nombre').value);
    formData.append('password', document.getElementById('password').value);
    formData.append('cargo', document.getElementById('cargo').value);

    const response = await fetch(`${BACKEND_URL}/login_procesar.php`, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    });

    if (response.ok) {
        window.location.href = 'pages/dashboard.html';
    }
});
```

---

## ✅ Checklist de Implementación

- [ ] Backend subido al servidor
- [ ] Credenciales de BD configuradas
- [ ] CORS habilitado en backend
- [ ] URL del backend actualizada en frontend JS
- [ ] Frontend accesible vía web/móvil
- [ ] HTTPS activado
- [ ] Pruebas de login realizadas
- [ ] Pruebas de sesión realizadas

---

## 📞 Soporte

Para problemas:
1. Revisa logs del servidor
2. Verifica consola del navegador (F12)
3. Comprueba conexión a la base de datos
4. Valida configuración CORS

