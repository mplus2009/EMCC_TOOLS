# Sistema de Gestión Escolar - Separación Frontend/Backend

## 📋 Descripción

Este proyecto separa la aplicación web original en dos componentes independientes:
- **Backend**: API REST en PHP para el servidor
- **Frontend**: Aplicación móvil en Flutter (convertible a APK)

---

## 🗂️ Estructura del Proyecto

```
/workspace/
├── backend/                    # Backend PHP para el servidor
│   ├── api/
│   │   ├── auth.php           # Autenticación (login, logout, QR)
│   │   ├── dashboard.php      # Operaciones del dashboard
│   │   └── notificaciones.php # Registro de méritos/deméritos
│   ├── config/
│   │   └── database.php       # Configuración de base de datos
│   ├── models/                # Modelos de datos (opcional)
│   └── controllers/           # Controladores adicionales (opcional)
│
├── flutter_app/               # Aplicación Flutter (Frontend)
│   ├── lib/
│   │   ├── main.dart          # Punto de entrada
│   │   ├── screens/
│   │   │   ├── login_screen.dart
│   │   │   ├── dashboard_screen.dart
│   │   │   └── ...            # Más pantallas
│   │   ├── services/
│   │   │   └── api_service.dart  # Servicio de conexión al backend
│   │   ├── models/
│   │   │   └── usuario.dart      # Modelos de datos
│   │   └── widgets/              # Widgets reutilizables
│   ├── assets/
│   │   └── styles/
│   └── pubspec.yaml
│
└── (archivos originales PHP se mantienen para compatibilidad)
```

---

## 🚀 Backend PHP

### Requisitos
- Servidor web (Apache, Nginx)
- PHP 7.4 o superior
- MySQL/MariaDB
- Extensión mysqli habilitada

### Instalación en el Servidor

1. **Subir archivos del backend**:
   ```bash
   # Copiar la carpeta backend completa a tu servidor
   cp -r /workspace/backend /var/www/html/
   ```

2. **Configurar la base de datos**:
   Editar `/backend/config/database.php`:
   ```php
   $host = 'tu_host';
   $usuario_db = 'tu_usuario';
   $password_db = 'tu_contraseña';
   $nombre_db = 'tu_base_de_datos';
   ```

3. **Configurar CORS** (si es necesario):
   Los archivos API ya incluyen headers CORS para permitir conexiones desde la app móvil.

4. **Permisos**:
   ```bash
   chmod -R 755 /var/www/html/backend
   chmod -R 644 /var/www/html/backend/config/database.php
   ```

### Endpoints de la API

#### Autenticación (`/api/auth.php`)
- `?accion=login` - POST: Iniciar sesión
- `?accion=logout` - GET: Cerrar sesión
- `?accion=verificar_sesion` - GET: Verificar sesión activa
- `?accion=login_qr` - POST: Login con código QR

#### Dashboard (`/api/dashboard.php`)
- `?accion=obtener_datos` - GET: Obtener datos del dashboard
- `?accion=obtener_actividades` - GET: Listar actividades
- `?accion=buscar_usuario` - GET: Buscar usuarios
- `?accion=buscar_actividad` - GET: Buscar actividades del catálogo
- `?accion=marcar_leido` - POST: Marcar actividad como leída
- `?accion=guardar_alegacion` - POST: Guardar alegación
- `?accion=obtener_perfil` - GET: Obtener perfil de usuario

#### Notificaciones (`/api/notificaciones.php`)
- `?accion=registrar` - POST: Registrar nueva notificación
- `?accion=obtener_catalogo` - GET: Obtener catálogo de méritos/deméritos

### Formato de Respuesta

Todas las respuestas son JSON:
```json
{
  "success": true/false,
  "message": "Descripción del resultado",
  "data": { ... },  // Datos (cuando success=true)
  "code": "CODIGO_ERROR"  // Código de error (cuando success=false)
}
```

---

## 📱 Frontend Flutter

### Requisitos
- Flutter SDK 3.0 o superior
- Android Studio / VS Code
- Para compilar APK: Android SDK

### Configuración Inicial

1. **Navegar al directorio Flutter**:
   ```bash
   cd /workspace/flutter_app
   ```

2. **Instalar dependencias**:
   ```bash
   flutter pub get
   ```

3. **Configurar URL del backend**:
   Editar `lib/services/api_service.dart`:
   ```dart
   static const String baseUrl = 'http://tuservidor.com/backend/api';
   
   // Para pruebas locales:
   // Android emulator: http://10.0.2.2/backend/api
   // iOS simulator: http://localhost/backend/api
   ```

### Ejecutar en Desarrollo

```bash
# En emulador/dispositivo conectado
flutter run

# En Chrome (web)
flutter run -d chrome
```

### Compilar APK

```bash
# APK de depuración
flutter build apk --debug

# APK de release (producción)
flutter build apk --release

# APK dividido por arquitectura (más pequeño)
flutter build apk --split-per-abi
```

Los APKs se generan en: `build/app/outputs/flutter-apk/`

### Compilar para Producción

```bash
# Release optimizado
flutter build apk --release --tree-shake-icons

# App Bundle (para Google Play)
flutter build appbundle --release
```

---

## 🔐 Seguridad

### Recomendaciones para Producción

1. **Usar HTTPS**: Configurar SSL en el servidor
2. **Validar tokens**: Implementar JWT para sesiones más seguras
3. **Rate limiting**: Limitar peticiones por IP
4. **Sanitizar inputs**: Ya implementado en el backend
5. **No exponer credenciales**: Mantener `database.php` fuera del root público

---

## 🧪 Pruebas

### Probar la API

```bash
# Test de login
curl -X POST http://tuservidor.com/backend/api/auth.php \
  -d "accion=login&nombre=JUAN&apellidos=PEREZ&password=123&cargo=estudiante"

# Test de obtener datos
curl http://tuservidor.com/backend/api/dashboard.php?accion=obtener_datos
```

### Probar la App Flutter

1. Iniciar el backend en un servidor local
2. Configurar la URL en `api_service.dart`
3. Ejecutar `flutter run` en un emulador o dispositivo físico

---

## 📝 Funcionalidades Implementadas

### Backend
- ✅ Login con nombre, apellidos, contraseña y cargo
- ✅ Login con código QR
- ✅ Cierre de sesión
- ✅ Verificación de sesión activa
- ✅ Obtención de datos del dashboard
- ✅ Listado de actividades (méritos/deméritos)
- ✅ Búsqueda de usuarios
- ✅ Búsqueda de actividades del catálogo
- ✅ Marcar actividades como leídas
- ✅ Guardar alegaciones
- ✅ Registro de nuevas notificaciones
- ✅ Obtener perfil de usuario

### Frontend Flutter
- ✅ Pantalla de splash con verificación de sesión
- ✅ Login con formulario completo
- ✅ Botón para escanear QR (ruta preparada)
- ✅ Dashboard con balance de méritos/deméritos
- ✅ Lista de actividades recientes
- ✅ Indicador de no leídos
- ✅ Menú de usuario (Perfil, Configuración, Logout)
- ✅ Botón flotante "Notificar" (para no estudiantes)
- ✅ Diseño responsive y adaptado a móviles
- ✅ Mismo estilo visual que la versión web original

---

## 🔄 Migración desde la Versión Original

El código original PHP se mantiene intacto para:
- Compatibilidad con la versión web existente
- Referencia durante la migración
- Posible uso híbrido durante la transición

### Pasos para Migración Completa

1. Desplegar el nuevo backend PHP
2. Probar todos los endpoints de la API
3. Configurar la app Flutter con la URL correcta
4. Compilar y probar el APK
5. Redirigir tráfico gradualmente a la nueva app
6. Monitorear errores y ajustar según sea necesario

---

## 🛠️ Solución de Problemas

### Error de Conexión en Flutter
- Verificar que la URL del backend sea correcta
- Asegurar que el servidor permita CORS
- Comprobar conexión a internet del dispositivo

### Error 401 No Autorizado
- Verificar que la sesión esté activa
- Revisar que las credenciales sean correctas
- Comprobar que el usuario exista en la base de datos

### Errores de Base de Datos
- Verificar credenciales en `database.php`
- Asegurar que las tablas existan
- Comprobar permisos del usuario de BD

---

## 📞 Soporte

Para problemas específicos del código original, revisar los archivos PHP originales en la raíz del workspace.

---

## 📄 Licencia

Este proyecto mantiene la misma licencia que el código original.

---

**Nota**: Esta separación mantiene todas las funciones y el estilo visual original sin pérdida de funcionalidad. El backend está optimizado para servidores de producción y el frontend Flutter está listo para convertirse en APK.
