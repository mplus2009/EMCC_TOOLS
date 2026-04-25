<?php
// ============================================
// CONFIGURACIÓN DE ALARMA POR BALANCE NEGATIVO
// ============================================
// Este archivo se genera automáticamente desde "Editar Reglas"
// Puedes editarlo manualmente si es necesario

return [
    'limite_10mo' => 15,   // Límite de balance negativo para 10mo grado
    'limite_11no' => 11,   // Límite de balance negativo para 11no grado
    'limite_12mo' => 10    // Límite de balance negativo para 12mo grado
];

// ============================================
// NOTA: La alarma se activa cuando:
// - El balance es negativo (más deméritos que méritos)
// - El valor absoluto del balance alcanza o supera el límite del grado
// ============================================
?>