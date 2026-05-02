import 'dart:convert';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class ApiService {
  // CAMBIAR ESTA URL POR LA DE TU SERVIDOR
  static const String baseUrl = 'http://tuservidor.com/backend/api';
  
  // Para pruebas locales usar: http://10.0.2.2/backend/api (Android emulator)
  // o http://localhost/backend/api (iOS simulator/web)
  
  static Future<Map<String, dynamic>> _post(String endpoint, Map<String, dynamic> data) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/$endpoint'),
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: data.map((key, value) => MapEntry(key, value.toString())),
      );
      
      return json.decode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Error de conexión: $e'};
    }
  }
  
  static Future<Map<String, dynamic>> _get(String endpoint, {Map<String, String>? params}) async {
    try {
      Uri uri = Uri.parse('$baseUrl/$endpoint');
      if (params != null) {
        uri = uri.replace(queryParameters: params);
      }
      
      final response = await http.get(uri);
      return json.decode(response.body);
    } catch (e) {
      return {'success': false, 'message': 'Error de conexión: $e'};
    }
  }
  
  // ==================== AUTENTICACIÓN ====================
  
  static Future<Map<String, dynamic>> login({
    required String nombre,
    required String apellidos,
    required String password,
    required String cargo,
  }) async {
    final result = await _post('auth.php', {
      'accion': 'login',
      'nombre': nombre,
      'apellidos': apellidos,
      'password': password,
      'cargo': cargo,
    });
    
    if (result['success'] == true && result['data'] != null) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool('logueado', true);
      await prefs.setInt('usuario_id', result['data']['usuario_id']);
      await prefs.setString('usuario_nombre', result['data']['usuario_nombre']);
      await prefs.setString('usuario_apellidos', result['data']['usuario_apellidos']);
      await prefs.setString('usuario_ci', result['data']['usuario_ci'] ?? '');
      await prefs.setString('usuario_cargo', result['data']['usuario_cargo']);
    }
    
    return result;
  }
  
  static Future<Map<String, dynamic>> loginQR(String qrText) async {
    final result = await _post('auth.php', {
      'accion': 'login_qr',
      'qr_text': qrText,
    });
    
    if (result['success'] == true && result['data'] != null) {
      final prefs = await SharedPreferences.getInstance();
      await prefs.setBool('logueado', true);
      await prefs.setInt('usuario_id', result['data']['usuario_id']);
      await prefs.setString('usuario_nombre', result['data']['usuario_nombre']);
      await prefs.setString('usuario_apellidos', result['data']['usuario_apellidos']);
      await prefs.setString('usuario_ci', result['data']['usuario_ci'] ?? '');
      await prefs.setString('usuario_cargo', result['data']['usuario_cargo']);
    }
    
    return result;
  }
  
  static Future<void> logout() async {
    await _get('auth.php', params: {'accion': 'logout'});
    final prefs = await SharedPreferences.getInstance();
    await prefs.clear();
  }
  
  static Future<bool> verificarSesion() async {
    final prefs = await SharedPreferences.getInstance();
    final logueado = prefs.getBool('logueado') ?? false;
    
    if (!logueado) return false;
    
    final result = await _get('auth.php', params: {'accion': 'verificar_sesion'});
    return result['success'] == true;
  }
  
  // ==================== DASHBOARD ====================
  
  static Future<Map<String, dynamic>> obtenerDatosDashboard() async {
    return await _get('dashboard.php', params: {'accion': 'obtener_datos'});
  }
  
  static Future<Map<String, dynamic>> obtenerActividades() async {
    return await _get('dashboard.php', params: {'accion': 'obtener_actividades'});
  }
  
  static Future<Map<String, dynamic>> buscarUsuario(String query, String cargo) async {
    return await _get('dashboard.php', params: {
      'accion': 'buscar_usuario',
      'q': query,
      'cargo': cargo,
    });
  }
  
  static Future<Map<String, dynamic>> buscarActividad(String tipo) async {
    return await _get('dashboard.php', params: {
      'accion': 'buscar_actividad',
      'tipo': tipo,
    });
  }
  
  static Future<Map<String, dynamic>> marcarLeido(int actividadId) async {
    return await _post('dashboard.php', {
      'accion': 'marcar_leido',
      'actividad_id': actividadId.toString(),
    });
  }
  
  static Future<Map<String, dynamic>> guardarAlegacion(int actividadId, String texto) async {
    return await _post('dashboard.php', {
      'accion': 'guardar_alegacion',
      'actividad_id': actividadId.toString(),
      'texto_alegacion': texto,
    });
  }
  
  static Future<Map<String, dynamic>> obtenerPerfil() async {
    return await _get('dashboard.php', params: {'accion': 'obtener_perfil'});
  }
  
  // ==================== NOTIFICACIONES ====================
  
  static Future<Map<String, dynamic>> registrarNotificacion({
    required List<dynamic> destinatarios,
    required List<dynamic> actividades,
    required String fecha,
    required String hora,
    String observaciones = '',
    String tipoNotificador = 'cuenta',
    String tempNombre = 'Temporal',
  }) async {
    return await _post('notificaciones.php', {
      'accion': 'registrar',
      'destinatarios': json.encode(destinatarios),
      'actividades': json.encode(actividades),
      'fecha': fecha,
      'hora': hora,
      'observaciones': observaciones,
      'tipo_notificador': tipoNotificador,
      'temp_nombre': tempNombre,
    });
  }
  
  static Future<Map<String, dynamic>> obtenerCatalogo(String tipo) async {
    return await _get('notificaciones.php', params: {
      'accion': 'obtener_catalogo',
      'tipo': tipo,
    });
  }
}
