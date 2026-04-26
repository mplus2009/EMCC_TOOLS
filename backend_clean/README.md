# Backend - Sistema Escolar (PHP API)

## Estructura de carpetas

```
backend/
├── config/
│   └── db.php          # Configuración de base de datos
├── api/
│   ├── login_procesar.php   # API de login
│   ├── check_session.php    # Verificar sesión
│   └── logout.php           # Cerrar sesión
└── includes/                # Funciones compartidas
```

## Configuración

### 1. Base de Datos

Edita `config/db.php` con tus credenciales:

```php
$host = 'tu_host';
$usuario_db = 'tu_usuario';
$password_db = 'tu_contraseña';  // ¡CAMBIA ESTO!
$nombre_db = 'tu_base_de_datos';
```

### 2. CORS para dominios separados

Si el frontend está en un dominio diferente, modifica los headers en cada archivo API:

```php
header('Access-Control-Allow-Origin: https://tudominio.com');
```

## Endpoints API

### POST /api/login_procesar.php
Login de usuario.

**Request:**
```
nombre=Juan&apellidos=Perez&password=123&cargo=estudiante
```

**Response:**
```json
{
    "success": true,
    "message": "Login exitoso",
    "redirect": "dashboard.html",
    "usuario": {
        "id": 1,
        "nombre": "Juan",
        "apellidos": "Perez",
        "cargo": "estudiante"
    }
}
```

### GET /api/check_session.php
Verificar si hay sesión activa.

**Response:**
```json
{
    "logged_in": true,
    "usuario": { ... }
}
```

### POST /api/logout.php
Cerrar sesión.

## Errores Críticos a Corregir

1. **Contraseña expuesta**: La contraseña de la BD está en texto plano en `config/db.php`. Usa variables de entorno.

2. **Sin rate limiting**: Implementa protección contra fuerza bruta en el login.

3. **Sesiones PHP inseguras**: Para APIs REST, considera usar JWT en lugar de sesiones PHP.

4. **Nombres de tabla dinámicos**: Las consultas usan nombres de tablas desde input del usuario. Valida estrictamente.

5. **Error handling**: Los errores de SQL se muestran directamente. Usa logging en producción.

6. **Sin validación de entrada**: Valida y sanitiza todos los inputs.

7. **Falta de autenticación en endpoints**: Algunos endpoints pueden no verificar la sesión.

8. **No hay logs de auditoría**: Implementa logging de acciones importantes.
