import 'package:flutter/material.dart';
import 'package:font_awesome_flutter/font_awesome_flutter.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../services/api_service.dart';

class DashboardScreen extends StatefulWidget {
  const DashboardScreen({super.key});

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  String _nombreUsuario = '';
  String _apellidosUsuario = '';
  String _cargoUsuario = '';
  int _meritos = 0;
  int _demeritos = 0;
  List<dynamic> _actividades = [];
  bool _isLoading = true;
  int _noLeidos = 0;

  @override
  void initState() {
    super.initState();
    _cargarDatos();
  }

  Future<void> _cargarDatos() async {
    setState(() => _isLoading = true);

    try {
      final prefs = await SharedPreferences.getInstance();
      _nombreUsuario = prefs.getString('usuario_nombre') ?? '';
      _apellidosUsuario = prefs.getString('usuario_apellidos') ?? '';
      _cargoUsuario = prefs.getString('usuario_cargo') ?? '';

      final result = await ApiService.obtenerDatosDashboard();

      if (result['success'] == true && result['data'] != null) {
        setState(() {
          if (_cargoUsuario == 'estudiante') {
            _meritos = result['data']['balance']?['meritos'] ?? 0;
            _demeritos = result['data']['balance']?['demeritos'] ?? 0;
            _actividades = result['data']['actividades'] ?? [];
            _noLeidos = result['data']['no_leidos'] ?? 0;
          }
        });
      }
    } catch (e) {
      debugPrint('Error cargando datos: $e');
    } finally {
      if (mounted) {
        setState(() => _isLoading = false);
      }
    }
  }

  Future<void> _cerrarSesion() async {
    final confirm = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Cerrar Sesión'),
        content: const Text('¿Estás seguro que deseas cerrar sesión?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Cancelar'),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(context, true),
            style: ElevatedButton.styleFrom(backgroundColor: Colors.red),
            child: const Text('Salir'),
          ),
        ],
      ),
    );

    if (confirm == true) {
      await ApiService.logout();
      if (mounted) {
        Navigator.of(context).pushReplacementNamed('/login');
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: CustomScrollView(
        slivers: [
          // AppBar
          SliverAppBar(
            floating: true,
            backgroundColor: const Color(0xFF1e3c72),
            title: Row(
              children: [
                CircleAvatar(
                  backgroundColor: Colors.white.withOpacity(0.2),
                  child: FaIcon(
                    _cargoUsuario == 'estudiante' 
                        ? FontAwesomeIcons.userGraduate 
                        : FontAwesomeIcons.user,
                    color: Colors.white,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(
                        '$_nombreUsuario $_apellidosUsuario',
                        style: const TextStyle(fontWeight: FontWeight.w600),
                        overflow: TextOverflow.ellipsis,
                      ),
                      Text(
                        _cargoUsuario.toUpperCase(),
                        style: TextStyle(fontSize: 12, color: Colors.white.withOpacity(0.8)),
                      ),
                    ],
                  ),
                ),
                if (_cargoUsuario == 'estudiante' && _noLeidos > 0)
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                    decoration: BoxDecoration(
                      color: Colors.red,
                      borderRadius: BorderRadius.circular(12),
                    ),
                    child: Text(
                      '$_noLeidos',
                      style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold),
                    ),
                  ),
                PopupMenuButton<String>(
                  icon: const Icon(Icons.more_vert),
                  onSelected: (value) {
                    if (value == 'perfil') {
                      Navigator.pushNamed(context, '/perfil');
                    } else if (value == 'configuracion') {
                      Navigator.pushNamed(context, '/configuracion');
                    } else if (value == 'logout') {
                      _cerrarSesion();
                    }
                  },
                  itemBuilder: (_) => [
                    const PopupMenuItem(value: 'perfil', child: Text('Mi Perfil')),
                    const PopupMenuItem(value: 'configuracion', child: Text('Configuración')),
                    const PopupMenuDivider(),
                    const PopupMenuItem(value: 'logout', child: Text('Cerrar Sesión')),
                  ],
                ),
              ],
            ),
          ),

          // Contenido
          if (_isLoading)
            const SliverFillRemaining(
              child: Center(child: CircularProgressIndicator()),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.all(16),
              sliver: SliverList(
                delegate: SliverChildListDelegate([
                  // Balance Cards (solo estudiantes)
                  if (_cargoUsuario == 'estudiante') ...[
                    Row(
                      children: [
                        Expanded(
                          child: _buildBalanceCard(
                            'Méritos',
                            _meritos,
                            Colors.green,
                            FontAwesomeIcons.star,
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: _buildBalanceCard(
                            'Deméritos',
                            _demeritos,
                            Colors.red,
                            FontAwesomeIcons.triangleExclamation,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Título de actividades
                  Padding(
                    padding: const EdgeInsets.only(bottom: 12),
                    child: Row(
                      children: [
                        const Icon(Icons.history, size: 20),
                        const SizedBox(width: 8),
                        const Text(
                          'Actividades Recientes',
                          style: TextStyle(fontSize: 18, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),

                  // Lista de actividades
                  if (_actividades.isEmpty)
                    Card(
                      child: Padding(
                        padding: const EdgeInsets.all(32),
                        child: Center(
                          child: Column(
                            children: [
                              Icon(Icons.inbox_outlined, size: 48, color: Colors.grey[400]),
                              const SizedBox(height: 16),
                              Text(
                                'No hay actividades registradas',
                                style: TextStyle(color: Colors.grey[600]),
                              ),
                            ],
                          ),
                        ),
                      ),
                    )
                  else
                    ListView.builder(
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      itemCount: _actividades.length > 50 ? 50 : _actividades.length,
                      itemBuilder: (_, index) {
                        final actividad = _actividades[index];
                        return _buildActividadCard(actividad);
                      },
                    ),
                ]),
              ),
            ),
        ],
      ),
      // Botón flotante para notificar (solo no estudiantes)
      floatingActionButton: _cargoUsuario != 'estudiante'
          ? FloatingActionButton.extended(
              onPressed: () => Navigator.pushNamed(context, '/notificar'),
              backgroundColor: const Color(0xFF1e3c72),
              icon: const FaIcon(FontAwesomeIcons.bell),
              label: const Text('Notificar'),
            )
          : null,
    );
  }

  Widget _buildBalanceCard(String titulo, int valor, Color color, IconData icon) {
    return Card(
      elevation: 4,
      child: Container(
        padding: const EdgeInsets.all(20),
        decoration: BoxDecoration(
          gradient: LinearGradient(
            colors: [color.withOpacity(0.1), color.withOpacity(0.2)],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
          borderRadius: BorderRadius.circular(16),
        ),
        child: Column(
          children: [
            FaIcon(icon, size: 32, color: color),
            const SizedBox(height: 12),
            Text(
              valor.toString(),
              style: TextStyle(
                fontSize: 32,
                fontWeight: FontWeight.bold,
                color: color,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              titulo,
              style: TextStyle(color: Colors.grey[700], fontSize: 14),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActividadCard(Map<String, dynamic> actividad) {
    final esMerito = actividad['tipo'] == 'merito';
    final leido = actividad['leido'] == 1;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ListTile(
        contentPadding: const EdgeInsets.all(16),
        leading: Container(
          width: 48,
          height: 48,
          decoration: BoxDecoration(
            color: esMerito ? Colors.green.withOpacity(0.1) : Colors.red.withOpacity(0.1),
            borderRadius: BorderRadius.circular(12),
          ),
          child: FaIcon(
            esMerito ? FontAwesomeIcons.star : FontAwesomeIcons.triangleExclamation,
            color: esMerito ? Colors.green : Colors.red,
            size: 24,
          ),
        ),
        title: Text(
          actividad['falta_causa'] ?? '',
          style: TextStyle(
            fontWeight: leido ? FontWeight.normal : FontWeight.bold,
            color: leido ? null : const Color(0xFF1e3c72),
          ),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const SizedBox(height: 4),
            Text('${actividad['categoria'] ?? ''} • ${actividad['cantidad']} pts'),
            const SizedBox(height: 4),
            Text(
              '${actividad['fecha']} - ${actividad['hora']}',
              style: TextStyle(fontSize: 12, color: Colors.grey[600]),
            ),
          ],
        ),
        isThreeLine: true,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
    );
  }
}
