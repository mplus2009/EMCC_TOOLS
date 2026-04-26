# Frontend - Sistema Escolar (HTML/CSS/JS)

## Estructura de carpetas

```
frontend/
├── html/
│   ├── index.html      # Página de login
│   └── dashboard.html  # Dashboard principal
├── css/
│   ├── login.css       # Estilos del login
│   └── dashboard.css   # Estilos del dashboard
└── js/
    ├── login.js        # Lógica del login
    └── dashboard.js    # Lógica del dashboard
```

## Configuración del Backend

El frontend se conecta al backend en la ruta: `../backend/api`

Para producción, modifica la constante `BACKEND_URL` en los archivos JavaScript:
- `js/login.js`
- `js/dashboard.js`

Ejemplo para producción:
```javascript
const BACKEND_URL = 'https://tarjeta-de-reporte.infinityfree.me/backend/api';
```

## Uso con Aplicación Móvil

Este frontend es compatible con aplicaciones móviles porque:
1. No usa sesiones PHP (usa localStorage)
2. Toda la comunicación con el backend es mediante APIs REST
3. Los datos del usuario se almacenan localmente

## Errores Críticos a Corregir

1. **Credenciales expuestas**: La contraseña de la base de datos está visible en `backend/config/db.php`. ¡DEBES CAMBIARLA!

2. **CORS**: Si el frontend y backend están en dominios diferentes, debes configurar los headers CORS correctamente en el backend.

3. **Seguridad de sesión**: Las sesiones se manejan con localStorage, lo cual es vulnerable a XSS. Considera usar tokens JWT para mayor seguridad.

4. **Validación del lado del cliente**: La validación de formularios se hace solo en JavaScript. Un atacante puede omitirla. Siempre valida en el backend.

5. **Contraseñas en texto plano**: El sistema acepta contraseñas sin hash (comparación directa). Debes forzar el uso de password_hash().

6. **SQL Injection potencial**: Aunque se usan prepared statements, algunas consultas dinámicas con nombres de tablas podrían ser vulnerables.

7. **Falta de HTTPS**: En producción, asegúrate de usar HTTPS para proteger las credenciales.

8. **No hay rate limiting**: El endpoint de login no tiene protección contra fuerza bruta.
