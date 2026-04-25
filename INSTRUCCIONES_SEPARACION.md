# Sistema Escolar - Separación Frontend/Backend

## Estructura del Proyecto

Este proyecto ha sido separado en dos componentes principales:

### 📁 Backend (Para Hosting)
Contiene toda la lógica del servidor, procesamiento de datos y acceso a base de datos.

**Ubicación:** `/backend/`

```
backend/
├── api/                    # Endpoints y procesamiento
│   ├── login_procesar.php
│   ├── logout.php
│   ├── check_session.php
│   ├── auth.php
│   ├── dashboard_procesar.php
│   ├── notificar_procesar.php
│   ├── buscar_usuario.php
│   ├── buscar_actividad.php
│   └── ... (todos los archivos de procesamiento)
├── config/                 # Configuración
│   └── db.php             # Conexión a base de datos
├── includes/              # Archivos compartidos
│   └── config_sesion.php
├── models/                # Modelos de datos (opcional)
└── controllers/           # Controladores (opcional)
```

### 📁 Frontend (Para Web y Móvil)
Contiene la interfaz de usuario, estilos y JavaScript.

**Ubicación:** `/frontend/`

```
frontend/
├── css/                   # Hojas de estilo
│   ├── dashboard_estilos.css
│   ├── notificar_estilos.css
│   └── iconos_vectoriales.css
├── js/                    # JavaScript
│   └── notificar.js
├── pages/                 # Vistas HTML/PHP
│   ├── login.php
│   └── dashboard.php
├── assets/                # Recursos estáticos
└── components/            # Componentes reutilizables
```

## 🚀 Implementación en el Hosting

### Paso 1: Subir Backend
1. Sube todo el contenido de `/backend/` a tu hosting
2. Asegúrate de que `config/db.php` tenga las credenciales correctas
3. Verifica que todos los archivos PHP tengan permisos adecuados (644)

### Paso 2: Configurar CORS (Opcional para API REST)
Si quieres consumir la API desde diferentes dominios, añade esto al inicio de cada archivo del backend:

```php
<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
```

### Paso 3: Frontend para Web
Los archivos del frontend pueden servir directamente desde el hosting o desde un CDN.

### Paso 4: Frontend para Móvil
Para dispositivos móviles, tienes dos opciones:

#### Opción A: WebView
- Crea una app nativa con WebView que cargue la URL del frontend
- Usa herramientas como Apache Cordova o Capacitor

#### Opción B: PWA (Progressive Web App)
- Añade un manifest.json al frontend
- Implementa service workers para funcionalidad offline
- Los usuarios pueden "instalar" la web app en sus dispositivos

## 📱 Adaptación para Móviles

El frontend ya incluye diseño responsivo con:
- Meta viewport configurado
- CSS adaptativo
- Touch-friendly UI

## 🔧 Endpoints del Backend

### Autenticación
- `POST /api/login_procesar.php` - Iniciar sesión
- `POST /api/logout.php` - Cerrar sesión
- `GET /api/check_session.php` - Verificar sesión

### Dashboard
- `GET /api/dashboard_procesar.php` - Obtener datos del dashboard
- `GET /api/buscar_usuario.php` - Buscar usuarios
- `GET /api/buscar_actividad.php` - Buscar actividades

### Notificaciones
- `POST /api/notificar_procesar.php` - Enviar notificaciones
- `GET /api/marcar_leido.php` - Marcar como leído

## ⚠️ Notas Importantes

1. **Seguridad**: Asegúrate de usar HTTPS en producción
2. **Base de Datos**: Las credenciales están en `backend/config/db.php`
3. **Sesiones**: El backend usa sesiones PHP tradicionales
4. **API JSON**: Para una integración más moderna con móviles, considera convertir las respuestas a JSON

## 🔄 Flujo de Funcionamiento

```
Frontend (Web/Móvil)
       ↓
   Solicitud HTTP
       ↓
Backend (Hosting)
       ↓
   Procesamiento PHP
       ↓
   Base de Datos
       ↓
   Respuesta HTML/JSON
       ↓
Frontend (Renderizado)
```

## 📞 Soporte

Para cualquier problema con la implementación, revisa:
1. Logs de error del servidor
2. Consola del navegador
3. Conexión a la base de datos
