# 📁 PROYECTO SEPARADO: BACKEND Y FRONTEND

## ✅ Estructura Final

### Backend (PHP) - `/workspace/backend_final/`
```
backend_final/
├── api/
│   ├── login_procesar.php    # API de login con CORS
│   ├── logout.php            # API de logout
│   ├── check_session.php     # Verificar sesión
│   └── ... (37 archivos PHP)
├── config/
│   └── db.php                # Configuración de base de datos
└── includes/
```

### Frontend (HTML/CSS/JS) - `/workspace/frontend_final/`
```
frontend_final/
├── html/
│   ├── index.html            # Página de login
│   └── dashboard.html        # Dashboard principal
├── css/
│   ├── login.css             # Estilos del login
│   ├── dashboard.css         # Estilos del dashboard
│   └── notificar_estilos.css # Estilos de notificaciones
└── js/
│   ├── login.js              # Lógica del login
│   └── dashboard.js          # Lógica del dashboard
```

---

## 🚀 INSTALACIÓN

### 1. Backend en InfinityFree

1. Sube TODA la carpeta `backend_final` a:
   ```
   htdocs/backend/
   ```
   en tu hosting de InfinityFree.

2. **IMPORTANTE**: Edita `/backend_final/config/db.php` y cambia:
   ```php
   $password_db = 'TU_CONTRASEÑA_AQUI'; // ⚠️ CAMBIA ESTO INMEDIATAMENTE
   ```

3. La URL del backend será:
   ```
   https://tarjeta-de-reporte.infinityfree.me/backend/api/
   ```

### 2. Frontend en GitHub Pages

1. Copia el contenido de `frontend_final/` a tu repositorio de GitHub.

2. Activa GitHub Pages en la rama `main` o `master`.

3. La URL del frontend será:
   ```
   https://tu-usuario.github.io/tu-repo/
   ```

### 3. Frontend en KSWEB (Local para pruebas)

1. Copia `frontend_final/` a la carpeta `htdocs` de KSWEB.

2. Accede desde tu móvil:
   ```
   http://192.168.x.x:8080/html/index.html
   ```

---

## ⚙️ CONFIGURACIÓN DE CORS

El backend ya tiene CORS configurado para aceptar peticiones desde cualquier origen (`*`). 

**Para mayor seguridad en producción**, edita cada archivo PHP del backend y cambia:
```php
header('Access-Control-Allow-Origin: *');
```
por:
```php
header('Access-Control-Allow-Origin: https://tu-usuario.github.io');
```

---

## 🔐 ERRORES CRÍTICOS QUE DEBES CORREGIR

### 🔴 URGENTE - Seguridad

1. **CONTRASEÑA DE BASE DE DATOS EXPUESTA**
   - Archivo: `backend_final/config/db.php`
   - Línea: `$password_db = 'TU_CONTRASEÑA_AQUI';`
   - **ACCIÓN**: Cambia inmediatamente por la contraseña real de tu base de datos en InfinityFree.

2. **CORS DEMASIADO PERMISIVO**
   - El backend acepta peticiones desde cualquier dominio (`*`)
   - **ACCIÓN**: En producción, especifica solo tu dominio de GitHub Pages.

3. **CONTRASEÑAS EN TEXTO PLANO**
   - El código acepta contraseñas sin hashear para compatibilidad
   - **ACCIÓN**: Implementa `password_hash()` al registrar usuarios nuevos.

### 🟠 IMPORTANTE - Funcionalidad

4. **RUTAS RELATIVAS EN JAVASCRIPT**
   - Las URLs del backend están hardcodeadas en `login.js` y `dashboard.js`
   - **ACCIÓN**: Si cambias la URL del backend, edita:
     ```javascript
     const BACKEND_URL = 'https://tarjeta-de-reporte.infinityfree.me/backend/api';
     ```

5. **LOCALSTORAGE VULNERABLE**
   - Los datos del usuario se guardan en localStorage (accesible desde JS)
   - **ACCIÓN**: Para apps móviles nativas, implementa JWT con tokens seguros.

6. **SIN RATE LIMITING**
   - No hay protección contra fuerza bruta en el login
   - **ACCIÓN**: Implementa un límite de intentos fallidos.

7. **ARCHIVOS DEL BACKEND SIN PROTEGER**
   - Algunos archivos PHP pueden ser accedidos directamente
   - **ACCIÓN**: Añade validación de sesión en todos los endpoints.

---

## 🧪 PRUEBAS

### Probar Login

1. Abre `frontend_final/html/index.html` en tu navegador.
2. Ingresa tus credenciales.
3. Deberías ser redirigido a `dashboard.html`.

### Probar Conexión Backend-Frontend

Abre la consola del navegador (F12) y verifica que no haya errores de CORS.

---

## 📝 NOTAS

- El frontend usa `localStorage` para mantener la sesión entre páginas.
- El backend usa sesiones PHP tradicionales (`$_SESSION`).
- Para aplicaciones móviles nativas, considera migrar a JWT.
- Todos los archivos del frontend son estáticos (HTML, CSS, JS) sin PHP.

---

## 🆘 SOPORTE

Si tienes problemas de conexión:

1. Verifica que el backend esté accesible desde:
   ```
   https://tarjeta-de-reporte.infinityfree.me/backend/api/check_session.php
   ```

2. Verifica la consola del navegador para errores de CORS.

3. Asegúrate de que las rutas en `login.js` y `dashboard.js` sean correctas.
