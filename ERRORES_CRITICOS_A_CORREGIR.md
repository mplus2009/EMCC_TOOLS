# ERRORES CRÍTICOS QUE DEBES CORREGIR

## 🔴 CRÍTICO - SEGURIDAD

### 1. CONTRASEÑA DE BASE DE DATOS EXPUESTA
**Ubicación:** `backend/config/db.php`
```php
$password_db = 'mplus2009';  // ¡PELIGRO!
```
**Problema:** La contraseña está en texto plano en el código.
**Solución:** 
- Cambia la contraseña inmediatamente
- Usa variables de entorno o un archivo .env fuera del directorio público
- Ejemplo: `$password_db = getenv('DB_PASSWORD');`

### 2. SIN PROTECCIÓN CONTRA FUERZA BRUTA
**Ubicación:** `backend/api/login_procesar.php`
**Problema:** No hay límite de intentos de login.
**Solución:** Implementa rate limiting (máximo 5 intentos por IP por hora).

### 3. CONTRASEÑAS EN TEXTO PLANO ACEPTADAS
**Ubicación:** `backend/api/login_procesar.php`
```php
if (password_verify($password, $row['password']) || $password === $row['password'])
```
**Problema:** El sistema acepta contraseñas sin hash.
**Solución:** Elimina la comparación directa, usa SOLO password_verify().

### 4. SESIONES PHP INCOMPATIBLES CON MÓVILES
**Problema:** Las sesiones PHP no funcionan bien en aplicaciones móviles nativas.
**Solución:** Implementa autenticación con JWT (JSON Web Tokens).

### 5. SQL INJECTION POTENCIAL
**Ubicación:** Consultas con nombres de tablas dinámicos
```php
$sql = "SELECT * FROM $cargo WHERE..."
```
**Problema:** El nombre de la tabla viene del input del usuario.
**Solución:** Valida estrictamente contra una lista blanca de tablas permitidas.

## 🟠 ALTO - FUNCIONALIDAD

### 6. CORS NO CONFIGURADO PARA DOMINIOS SEPARADOS
**Ubicación:** Todos los archivos API
**Problema:** Si frontend y backend están en dominios diferentes, el navegador bloqueará las peticiones.
**Solución:** Configura correctamente los headers CORS:
```php
header('Access-Control-Allow-Origin: https://tudominio.com');
header('Access-Control-Allow-Credentials: true');
```

### 7. RUTAS RELATIVAS INCORRECTAS
**Ubicación:** `frontend/html/index.html`, `frontend/js/login.js`
**Problema:** Las rutas como `../backend/api` no funcionarán en producción si los dominios son diferentes.
**Solución:** Usa URLs absolutas o configura la variable BACKEND_URL correctamente:
```javascript
const BACKEND_URL = 'https://tarjeta-de-reporte.infinityfree.me/backend/api';
```

### 8. VALIDACIÓN SOLO EN CLIENTE
**Problema:** La validación de formularios se hace solo en JavaScript.
**Solución:** Siempre valida en el backend también, nunca confíes en el cliente.

## 🟡 MEDIO - MEJORAS NECESARIAS

### 9. SIN LOGGING DE AUDITORÍA
**Problema:** No se registran los intentos de login, acciones importantes, etc.
**Solución:** Implementa un sistema de logs para auditoría de seguridad.

### 10. MENSAJES DE ERROR DEMASIADO INFORMATIVOS
**Problema:** Los errores de SQL se muestran directamente al usuario.
**Solución:** Usa mensajes genéricos en producción y logging interno.

### 11. FALTA DE HTTPS
**Problema:** Las credenciales viajan sin cifrar si no usas HTTPS.
**Solución:** Obliga el uso de HTTPS en producción.

### 12. LOCALSTORAGE VULNERABLE A XSS
**Ubicación:** `frontend/js/login.js`, `frontend/js/dashboard.js`
**Problema:** Los datos en localStorage pueden ser robados con XSS.
**Solución:** Considera usar tokens JWT con almacenamiento seguro.

## 📋 ARCHIVOS CREADOS

### Backend (`/workspace/backend_clean/`)
```
backend_clean/
├── config/
│   └── db.php              # Configuración de BD (¡CAMBIA LA CONTRASEÑA!)
├── api/
│   ├── login_procesar.php  # API REST de login
│   ├── check_session.php   # Verificar sesión
│   └── logout.php          # Cerrar sesión
└── README.md               # Documentación
```

### Frontend (`/workspace/frontend_clean/`)
```
frontend_clean/
├── html/
│   ├── index.html          # Login (solo HTML/CSS/JS)
│   └── dashboard.html      # Dashboard (solo HTML/CSS/JS)
├── css/
│   ├── login.css           # Estilos del login
│   └── dashboard.css       # Estilos del dashboard
├── js/
│   ├── login.js            # Lógica del login
│   └── dashboard.js        # Lógica del dashboard
└── README.md               # Documentación
```

## 🚀 CÓMO DESPLEGAR

### En InfinityFree (Backend PHP)
1. Sube el contenido de `backend_clean/` a `htdocs/backend/`
2. Edita `backend/config/db.php` con tus credenciales reales
3. Cambia la contraseña de la base de datos

### Para el Frontend (Sitio Web)
1. Sube el contenido de `frontend_clean/html/` a tu hosting de frontend
2. Sube `frontend_clean/css/` y `frontend_clean/js/` también
3. **IMPORTANTE:** Edita `login.js` y `dashboard.js`:
   ```javascript
   const BACKEND_URL = 'https://tarjeta-de-reporte.infinityfree.me/backend/api';
   ```

### Para Aplicación Móvil
1. El frontend ya es compatible (usa localStorage, no sesiones PHP)
2. Empaqueta los archivos HTML/CSS/JS en tu app
3. Configura la URL del backend en los archivos JS

## ⚠️ NOTA IMPORTANTE

Este código es una BASE. Debes:
1. **CAMBIAR LA CONTRASEÑA** de la base de datos inmediatamente
2. Implementar JWT para autenticación más segura
3. Añadir rate limiting al login
4. Configurar CORS correctamente para tus dominios
5. Usar HTTPS en producción
6. Validar TODOS los inputs en el backend
7. Implementar logging de auditoría

## 📞 PRÓXIMOS PASOS RECOMENDADOS

1. **Inmediato:** Cambiar contraseña de BD
2. **Corto plazo:** Implementar JWT, rate limiting
3. **Medio plazo:** Añadir logging, mejorar validaciones
4. **Largo plazo:** Migrar a framework moderno (Laravel, etc.)
