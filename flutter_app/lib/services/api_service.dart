import 'dart:convert';
import 'package:http/http.dart' as http;
import '../models/usuario.dart';

class ApiService {
  // CAMBIAR ESTA URL POR LA DE TU SERVIDOR
  static const String baseUrl = 'http://tarjeta de reporte.infinityfree.me/backend/api';
  
  String? _token;
  
  // Singleton
  static final ApiService _instance = ApiService._internal();
  factory ApiService() => _instance;
  ApiService._internal();
  
  // Getters y setters del token
  String? get token => _token;
  set token(String? value) => _token = value;
  
  // Headers para requests autenticados
  Map<String, String> get _headers => {
    'Content-Type': 'application/x-www-form-urlencoded',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };
  
  Map<String, String> get _jsonHeaders => {
    'Content-Type': 'application/json',
    if (_token != null) 'Authorization': 'Bearer $_token',
  };
  
  // ==================== AUTENTICACIÓN ====================
  
  Future<Map<String, dynamic>> login(String ci, String password) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth.php'),
        body: {'accion': 'login', 'ci': ci, 'password': password},
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        _token = data['data']['token'];
        return data;
      } else {
        throw Exception(data['message'] ?? 'Error al iniciar sesión');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<Map<String, dynamic>> loginQR(String qrData) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/auth.php'),
        body: {'accion': 'login_qr', 'qr_data': qrData},
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        _token = data['data']['token'];
        return data;
      } else {
        throw Exception(data['message'] ?? 'Error al iniciar sesión con QR');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<void> logout() async {
    try {
      await http.post(
        Uri.parse('$baseUrl/auth.php'),
        headers: _headers,
        body: {'accion': 'logout'},
      );
      _token = null;
    } catch (e) {
      _token = null;
    }
  }
  
  Future<bool> verificarSesion() async {
    try {
      if (_token == null) return false;
      
      final response = await http.get(
        Uri.parse('$baseUrl/auth.php?accion=verificar_sesion'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      return data['success'] == true;
    } catch (e) {
      return false;
    }
  }
  
  // ==================== DASHBOARD ====================
  
  Future<Map<String, dynamic>> obtenerDatosDashboard() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/dashboard.php?accion=obtener_datos'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        return data['data'];
      } else {
        throw Exception(data['message'] ?? 'Error al obtener datos');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<List<EstudianteBusqueda>> buscarEstudiante(String termino) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/dashboard.php?accion=buscar_estudiante&q=$termino'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        List<dynamic> resultados = data['data'];
        return resultados.map((e) => EstudianteBusqueda.fromJson(e)).toList();
      } else {
        return [];
      }
    } catch (e) {
      return [];
    }
  }
  
  Future<List<Actividad>> obtenerActividades({
    String filtro = 'semana',
    String tipo = 'todas',
  }) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/dashboard.php?accion=obtener_actividades&filtro=$filtro&tipo=$tipo'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        List<dynamic> actividades = data['data'];
        return actividades.map((e) => Actividad.fromJson(e)).toList();
      } else {
        return [];
      }
    } catch (e) {
      return [];
    }
  }
  
  Future<void> marcarTutorialVisto() async {
    try {
      await http.post(
        Uri.parse('$baseUrl/dashboard.php'),
        headers: _jsonHeaders,
        body: json.encode({'accion': 'marcar_tutorial_visto'}),
      );
    } catch (e) {
      // Ignorar errores
    }
  }
  
  // ==================== NOTIFICACIONES ====================
  
  Future<Map<String, dynamic>> obtenerCatalogos() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/notificaciones.php?accion=obtener_catalogos'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        return data['data'];
      } else {
        throw Exception(data['message'] ?? 'Error al obtener catálogos');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<Map<String, dynamic>> crearNotificacion({
    required List<Map<String, dynamic>> destinatarios,
    required List<Map<String, dynamic>> actividades,
    required String fecha,
    required String hora,
    String observaciones = '',
  }) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/notificaciones.php'),
        headers: _headers,
        body: {
          'accion': 'crear_notificacion',
          'destinatarios': json.encode(destinatarios),
          'actividades': json.encode(actividades),
          'fecha': fecha,
          'hora': hora,
          'observaciones': observaciones,
        },
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        return data;
      } else {
        throw Exception(data['message'] ?? 'Error al crear notificación');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<void> guardarAlegacion(int actividadId, String alegacion) async {
    try {
      final response = await http.post(
        Uri.parse('$baseUrl/notificaciones.php'),
        headers: _headers,
        body: {
          'accion': 'guardar_alegacion',
          'actividad_id': actividadId.toString(),
          'alegacion': alegacion,
        },
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] != true) {
        throw Exception(data['message'] ?? 'Error al guardar alegación');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<List<CatalogoItem>> buscarActividad(String termino, String tipo) async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/notificaciones.php?accion=buscar_actividad&q=$termino&tipo=$tipo'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        List<dynamic> resultados = data['data'];
        return resultados.map((e) => CatalogoItem.fromJson(e)).toList();
      } else {
        return [];
      }
    } catch (e) {
      return [];
    }
  }
  
  // ==================== HORARIOS ====================
  
  Future<Map<String, dynamic>> obtenerHorario({String? grado, int? peloton}) async {
    try {
      String url = '$baseUrl/horarios.php?accion=obtener_horario';
      if (grado != null) url += '&grado=$grado';
      if (peloton != null) url += '&peloton=$peloton';
      
      final response = await http.get(
        Uri.parse(url),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true) {
        return data['data'];
      } else {
        throw Exception(data['message'] ?? 'Error al obtener horario');
      }
    } catch (e) {
      throw Exception('Error de conexión: ${e.toString()}');
    }
  }
  
  Future<AsignaturaActual?> obtenerAsignaturaActual() async {
    try {
      final response = await http.get(
        Uri.parse('$baseUrl/horarios.php?accion=obtener_asignatura_actual'),
        headers: _headers,
      );
      
      final data = json.decode(response.body);
      
      if (data['success'] == true && data['data'] != null) {
        return AsignaturaActual.fromJson(data['data']);
      } else {
        return null;
      }
    } catch (e) {
      return null;
    }
  }
}
