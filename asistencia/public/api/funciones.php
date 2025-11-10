<?php
// api/funciones.php
require_once __DIR__ . '/conexion.php';

/**
 * Obtiene todas las marcaciones de un usuario en una fecha
 */
function getMarcacionesByUserDate($pdo, $user_id, $date) {
    $stmt = $pdo->prepare("SELECT * FROM marcaciones WHERE id_usuario = ? AND fecha = ? ORDER BY hora ASC");
    $stmt->execute([$user_id, $date]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Genera el resumen diario para un usuario y fecha.
 * Retorna: inicio, fin, inicio_refrigerio, fin_refrigerio, entrada_campo, salida_campo, tardanza_min, salida_anticipada_min, duracion_trabajo, duracion_break, tiempo_campo
 */
function generarResumenDiario($pdo, $user_id, $date) {
    // obtener horario asignado
    $stmt = $pdo->prepare("SELECT horario_inicio, horario_fin FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usr = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$usr) return null;

    $horario_inicio = $usr['horario_inicio'];
    $horario_fin = $usr['horario_fin'];

    $m = getMarcacionesByUserDate($pdo, $user_id, $date);

    $res = [
        'inicio' => null,
        'fin' => null,
        'inicio_refrigerio' => null,
        'fin_refrigerio' => null,
        'entrada_campo' => null,
        'salida_campo' => null,
        'tardanza_min' => 0,
        'salida_anticipada_min' => 0,
        'duracion_trabajo_min' => 0,
        'duracion_break_min' => 0,
        'tiempo_campo_min' => 0,
        'marcaciones' => $m
    ];

    foreach ($m as $row) {
        switch ($row['tipo']) {
            case 'entrada':
                if (!$res['inicio']) $res['inicio'] = $row['hora'];
                break;
            case 'salida':
                $res['fin'] = $row['hora']; // registra última salida
                break;
            case 'inicio_refrigerio':
                if (!$res['inicio_refrigerio']) $res['inicio_refrigerio'] = $row['hora'];
                break;
            case 'fin_refrigerio':
                $res['fin_refrigerio'] = $row['hora'];
                break;
            case 'entrada_campo':
            case 'entrada_tienda':
                if (!$res['entrada_campo']) $res['entrada_campo'] = $row['hora'];
                break;
            case 'salida_campo':
            case 'salida_tienda':
                $res['salida_campo'] = $row['hora'];
                break;
        }
    }

    // cálculos de minutos (helper)
    $toMin = function($t) {
        if (!$t) return null;
        list($h,$m,$s) = array_pad(explode(":", $t), 3, "00");
        return intval($h)*60 + intval($m);
    };

    $inicioMin = $toMin($res['inicio']);
    $inicioHorarioMin = $toMin($horario_inicio);
    if ($inicioMin !== null && $inicioHorarioMin !== null) {
        $res['tardanza_min'] = max(0, $inicioMin - $inicioHorarioMin);
    }

    $finMin = $toMin($res['fin']);
    $finHorarioMin = $toMin($horario_fin);
    if ($finMin !== null && $finHorarioMin !== null) {
        $res['salida_anticipada_min'] = max(0, $finHorarioMin - $finMin);
    }

    // duracion break
    if ($res['inicio_refrigerio'] && $res['fin_refrigerio']) {
        $res['duracion_break_min'] = $toMin($res['fin_refrigerio']) - $toMin($res['inicio_refrigerio']);
        if ($res['duracion_break_min'] < 0) $res['duracion_break_min'] = 0;
    }

    // tiempo en campo (entrada_campo -> salida_campo)
    if ($res['entrada_campo'] && $res['salida_campo']) {
        $res['tiempo_campo_min'] = $toMin($res['salida_campo']) - $toMin($res['entrada_campo']);
        if ($res['tiempo_campo_min'] < 0) $res['tiempo_campo_min'] = 0;
    }

    // duracion trabajo aproximada (inicio hasta fin) - break
    if ($inicioMin !== null && $finMin !== null) {
        $res['duracion_trabajo_min'] = ($finMin - $inicioMin) - $res['duracion_break_min'];
        if ($res['duracion_trabajo_min'] < 0) $res['duracion_trabajo_min'] = 0;
    }

    return array_merge($res, ['horario_inicio' => $horario_inicio, 'horario_fin' => $horario_fin]);
}
?>
