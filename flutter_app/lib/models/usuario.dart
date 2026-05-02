class Usuario {
  final int id;
  final String nombre;
  final String apellidos;
  final String? ci;
  final String cargo;
  final String? grado;
  final String? grupo;

  Usuario({
    required this.id,
    required this.nombre,
    required this.apellidos,
    this.ci,
    required this.cargo,
    this.grado,
    this.grupo,
  });

  factory Usuario.fromJson(Map<String, dynamic> json) {
    return Usuario(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? '',
      apellidos: json['apellidos'] ?? '',
      ci: json['ci'],
      cargo: json['cargo'] ?? 'estudiante',
      grado: json['grado'],
      grupo: json['grupo'],
    );
  }

  String get nombreCompleto => '$nombre $apellidos';
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'nombre': nombre,
      'apellidos': apellidos,
      'ci': ci,
      'cargo': cargo,
      'grado': grado,
      'grupo': grupo,
    };
  }
}

class Actividad {
  final int id;
  final String idStar;
  final String idEnd;
  final String tipo;
  final String categoria;
  final String faltaCausa;
  final int cantidad;
  final String fecha;
  final String hora;
  final String observaciones;
  final int leido;
  final String? alegacion;

  Actividad({
    required this.id,
    required this.idStar,
    required this.idEnd,
    required this.tipo,
    required this.categoria,
    required this.faltaCausa,
    required this.cantidad,
    required this.fecha,
    required this.hora,
    required this.observaciones,
    this.leido = 0,
    this.alegacion,
  });

  factory Actividad.fromJson(Map<String, dynamic> json) {
    return Actividad(
      id: json['id'] ?? 0,
      idStar: json['id_star'] ?? '',
      idEnd: json['id_end'] ?? '',
      tipo: json['tipo'] ?? '',
      categoria: json['categoria'] ?? '',
      faltaCausa: json['falta_causa'] ?? '',
      cantidad: json['cantidad'] ?? 0,
      fecha: json['fecha'] ?? '',
      hora: json['hora'] ?? '',
      observaciones: json['observaciones'] ?? '',
      leido: json['leido'] ?? 0,
      alegacion: json['alegacion'],
    );
  }

  bool get esMerito => tipo == 'merito';
  bool get esDemerito => tipo == 'demerito';
  
  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'id_star': idStar,
      'id_end': idEnd,
      'tipo': tipo,
      'categoria': categoria,
      'falta_causa': faltaCausa,
      'cantidad': cantidad,
      'fecha': fecha,
      'hora': hora,
      'observaciones': observaciones,
      'leido': leido,
    };
  }
}

class CatalogoActividad {
  final int id;
  final String nombre;
  final String descripcion;
  final int? demeritos10mo;
  final int? demeritos11_12;

  CatalogoActividad({
    required this.id,
    required this.nombre,
    required this.descripcion,
    this.demeritos10mo,
    this.demeritos11_12,
  });

  factory CatalogoActividad.fromJson(Map<String, dynamic> json) {
    return CatalogoActividad(
      id: json['id'] ?? 0,
      nombre: json['nombre'] ?? '',
      descripcion: json['descripcion'] ?? '',
      demeritos10mo: json['demeritos_10mo'],
      demeritos11_12: json['demeritos_11_12'],
    );
  }
}
