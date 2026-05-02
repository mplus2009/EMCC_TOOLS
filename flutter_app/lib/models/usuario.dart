import 'dart:convert';

class Usuario {
  final int id;
  final String ci;
  final String nombre;
  final String apellidos;
  final String rol;
  final String? email;
  final String? grado;
  final int? peloton;
  final String? ocupacion;

  Usuario({
    required this.id,
    required this.ci,
    required this.nombre,
    required this.apellidos,
    required this.rol,
    this.email,
    this.grado,
    this.peloton,
    this.ocupacion,
  });

  String get nombreCompleto => '$nombre $apellidos'.trim();

  factory Usuario.fromJson(Map<String, dynamic> json) {
    return Usuario(
      id: json['id'] ?? 0,
      ci: json['ci'] ?? '',
      nombre: json['nombre'] ?? '',
      apellidos: json['apellidos'] ?? '',
      rol: json['rol'] ?? 'estudiante',
      email: json['email'],
      grado: json['grado'],
      peloton: json['peloton'],
      ocupacion: json['ocupacion'],
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'ci': ci,
      'nombre': nombre,
      'apellidos': apellidos,
      'rol': rol,
      'email': email,
      'grado': grado,
      'peloton': peloton,
      'ocupacion': ocupacion,
    };
  }
}

class EstadisticasEstudiante {
  final int meritosSemana;
  final int demeritosSemana;
  final int balanceSemana;
  final int meritosTotal;
  final int demeritosTotal;
  final int balanceTotal;

  EstadisticasEstudiante({
    required this.meritosSemana,
    required this.demeritosSemana,
    required this.balanceSemana,
    required this.meritosTotal,
    required this.demeritosTotal,
    required this.balanceTotal,
  });

  factory EstadisticasEstudiante.fromJson(Map<String, dynamic> json) {
    return EstadisticasEstudiante(
      meritosSemana: json['meritos_semana'] ?? 0,
      demeritosSemana: json['demeritos_semana'] ?? 0,
      balanceSemana: json['balance_semana'] ?? 0,
      meritosTotal: json['meritos_total'] ?? 0,
      demeritosTotal: json['demeritos_total'] ?? 0,
      balanceTotal: json['balance_total'] ?? 0,
    );
  }
}

class Actividad {
  final int id;
  final int destinatarioId;
  final int notificadorId;
  final String tipo;
  final String categoria;
  final String faltaCausa;
  final int cantidad;
  final DateTime fecha;
  final String hora;
  final String? observaciones;
  final String? notificador;
  final String? alegacion;
  final bool leido;

  Actividad({
    required this.id,
    required this.destinatarioId,
    required this.notificadorId,
    required this.tipo,
    required this.categoria,
    required this.faltaCausa,
    required this.cantidad,
    required this.fecha,
    required this.hora,
    this.observaciones,
    this.notificador,
    this.alegacion,
    this.leido = false,
  });

  bool get esMerito => tipo == 'merito';

  factory Actividad.fromJson(Map<String, dynamic> json) {
    return Actividad(
      id: json['id'] ?? 0,
      destinatarioId: json['destinatario_id'] ?? 0,
      notificadorId: json['notificador_id'] ?? 0,
      tipo: json['tipo'] ?? 'merito',
      categoria: json['categoria'] ?? '',
      faltaCausa: json['falta_causa'] ?? '',
      cantidad: json['cantidad'] ?? 1,
      fecha: DateTime.parse(json['fecha'] ?? DateTime.now().toIso8601String()),
      hora: json['hora'] ?? '00:00',
      observaciones: json['observaciones'],
      notificador: json['notificador'],
      alegacion: json['alegacion'],
      leido: json['leido'] == 1 || json['leido'] == true,
    );
  }
}

class CatalogoItem {
  final int id;
  final String categoria;
  final String descripcion;
  final int cantidad;
  final String tipo;

  CatalogoItem({
    required this.id,
    required this.categoria,
    required this.descripcion,
    required this.cantidad,
    required this.tipo,
  });

  factory CatalogoItem.fromJson(Map<String, dynamic> json) {
    return CatalogoItem(
      id: json['id'] ?? 0,
      categoria: json['categoria'] ?? '',
      descripcion: json['descripcion'] ?? '',
      cantidad: json['cantidad'] ?? 1,
      tipo: json['tipo'] ?? 'merito',
    );
  }
}

class EstudianteBusqueda {
  final int id;
  final String nombre;
  final String apellidos;
  final String ci;
  final String grado;
  final int peloton;
  final int meritos;
  final int demeritos;
  final int balance;

  EstudianteBusqueda({
    required this.id,
    required this.nombre,
    required this.apellidos,
    required this.ci,
    required this.grado,
    required this.peloton,
    required this.meritos,
    required this.demeritos,
    required this.balance,
  });

  String get nombreCompleto => '$nombre $apellidos'.trim();

  factory EstudianteBusqueda.fromJson(Map<String, dynamic> json) {
    return EstudianteBusqueda(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? '',
      apellidos: json['apellidos'] ?? '',
      ci: json['CI'] ?? '',
      grado: json['grado'] ?? '',
      peloton: json['peloton'] ?? 1,
      meritos: json['meritos'] ?? 0,
      demeritos: json['demeritos'] ?? 0,
      balance: json['balance'] ?? 0,
    );
  }
}

class HorarioClase {
  final String diaSemana;
  final String horaInicio;
  final String horaFin;
  final String? asignaturaNombre;
  final String tipoEvento;

  HorarioClase({
    required this.diaSemana,
    required this.horaInicio,
    required this.horaFin,
    required this.asignaturaNombre,
    this.tipoEvento = 'asignatura',
  });

  factory HorarioClase.fromJson(Map<String, dynamic> json) {
    return HorarioClase(
      diaSemana: json['dia_semana'] ?? 'Lunes',
      horaInicio: json['hora_inicio'] ?? '00:00',
      horaFin: json['hora_fin'] ?? '00:00',
      asignaturaNombre: json['asignatura_nombre'],
      tipoEvento: json['tipo_evento'] ?? 'asignatura',
    );
  }
}

class AsignaturaActual {
  final String asignatura;
  final String horaInicio;
  final String horaFin;
  final String tipoEvento;

  AsignaturaActual({
    required this.asignatura,
    required this.horaInicio,
    required this.horaFin,
    this.tipoEvento = 'asignatura',
  });

  factory AsignaturaActual.fromJson(Map<String, dynamic> json) {
    return AsignaturaActual(
      asignatura: json['asignatura'] ?? 'Sin asignatura',
      horaInicio: json['hora_inicio'] ?? '00:00',
      horaFin: json['hora_fin'] ?? '00:00',
      tipoEvento: json['tipo_evento'] ?? 'asignatura',
    );
  }
}
