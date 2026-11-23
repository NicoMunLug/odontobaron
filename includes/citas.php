<?php
/**
 * Agenda: disponibilidad, reserva, reprogramación y cancelación.
 */
require_once __DIR__ . '/functions.php';

/**
 * Horas libres de un odontólogo en una fecha.
 * Cruza los horarios de atención con las citas ya tomadas y los bloqueos.
 */
function horas_disponibles(int $odontologoId, string $fecha, ?int $excluirCita = null): array
{
    $hoy = date('Y-m-d');
    if ($fecha < $hoy || $fecha > date('Y-m-d', strtotime('+' . CITA_DIAS_MAX . ' days'))) {
        return [];
    }

    // día bloqueado
    $bloqueado = valor(
        'SELECT COUNT(*) FROM bloqueos
         WHERE ? BETWEEN fecha_inicio AND fecha_fin
           AND (odontologo_id IS NULL OR odontologo_id = ?)',
        [$fecha, $odontologoId]
    );
    if ($bloqueado > 0) {
        return [];
    }

    $diaSemana = (int) date('N', strtotime($fecha));
    $franjas = filas(
        'SELECT hora_inicio, hora_fin FROM horarios
         WHERE odontologo_id = ? AND dia_semana = ? AND activo = 1
         ORDER BY hora_inicio',
        [$odontologoId, $diaSemana]
    );
    if (!$franjas) {
        return [];
    }

    $sql = 'SELECT hora FROM citas
            WHERE odontologo_id = ? AND fecha = ? AND estado IN (\'pendiente\',\'confirmada\')';
    $par = [$odontologoId, $fecha];
    if ($excluirCita !== null) {
        $sql .= ' AND id <> ?';
        $par[] = $excluirCita;
    }
    $ocupadas = array_map(fn($r) => substr($r['hora'], 0, 5), filas($sql, $par));

    $paso  = CITA_DURACION_MIN * 60;
    $libre = [];
    $limiteHoy = time() + CITA_HORAS_ANTICIPACION * 3600;

    foreach ($franjas as $f) {
        $t   = strtotime($fecha . ' ' . $f['hora_inicio']);
        $fin = strtotime($fecha . ' ' . $f['hora_fin']);
        for (; $t + $paso <= $fin; $t += $paso) {
            $hhmm = date('H:i', $t);
            if (in_array($hhmm, $ocupadas, true)) {
                continue;
            }
            if ($t < $limiteHoy) {
                continue;   // respeta la anticipación mínima
            }
            $libre[] = $hhmm;
        }
    }
    return $libre;
}

/** Primeros N días con al menos una hora libre. */
function proximos_dias_con_cupo(int $odontologoId, int $cuantos = 14): array
{
    $dias = [];
    for ($i = 0; $i <= CITA_DIAS_MAX && count($dias) < $cuantos; $i++) {
        $fecha = date('Y-m-d', strtotime("+$i days"));
        $horas = horas_disponibles($odontologoId, $fecha);
        if ($horas) {
            $dias[$fecha] = $horas;
        }
    }
    return $dias;
}

/**
 * Crea una cita. Verifica la disponibilidad en el momento de confirmar,
 * y la clave única de la tabla impide una doble reserva simultánea.
 */
function crear_cita(array $d): array
{
    $pacienteId    = (int) $d['paciente_id'];
    $odontologoId  = (int) $d['odontologo_id'];
    $tratamientoId = (int) $d['tratamiento_id'];
    $sedeId        = (int) $d['sede_id'];
    $fecha         = $d['fecha'];
    $hora          = substr($d['hora'], 0, 5);
    $notas         = trim($d['notas'] ?? '');

    if (!in_array($hora, horas_disponibles($odontologoId, $fecha), true)) {
        return ['ok' => false, 'error' => 'Ese horario ya no está disponible. Elija otro, por favor.'];
    }

    $codigo = nuevo_codigo_cita();
    try {
        q('INSERT INTO citas (codigo, paciente_id, odontologo_id, tratamiento_id, sede_id,
                fecha, hora, estado, notas_paciente)
           VALUES (?,?,?,?,?,?,?,?,?)',
          [$codigo, $pacienteId, $odontologoId, $tratamientoId, $sedeId,
           $fecha, $hora . ':00', 'pendiente', $notas ?: null]);
    } catch (PDOException $e) {
        // violación de la clave única (odontologo, fecha, hora)
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Otra persona acaba de tomar ese horario. Elija otro, por favor.'];
        }
        throw $e;
    }

    $id = (int) db()->lastInsertId();
    notificar_cita($id, 'creada');
    return ['ok' => true, 'id' => $id, 'codigo' => $codigo];
}

/** ¿Falta más del plazo mínimo para la cita? */
function cita_modificable(array $cita): bool
{
    if (!in_array($cita['estado'], ['pendiente', 'confirmada'], true)) {
        return false;
    }
    $cuando = strtotime($cita['fecha'] . ' ' . $cita['hora']);
    return $cuando - time() >= CITA_HORAS_ANTICIPACION * 3600;
}

function cancelar_cita(int $citaId, string $motivo = ''): array
{
    $cita = fila('SELECT * FROM citas WHERE id = ?', [$citaId]);
    if (!$cita) {
        return ['ok' => false, 'error' => 'No encontramos la cita.'];
    }
    if (!cita_modificable($cita)) {
        return ['ok' => false, 'error' =>
            'Esta cita ya no se puede cancelar en línea. Escríbanos por WhatsApp y la gestionamos.'];
    }
    q('UPDATE citas SET estado = ?, motivo_cancelacion = ?, cancelada_en = ? WHERE id = ?',
      ['cancelada', $motivo ?: null, date('Y-m-d H:i:s'), $citaId]);

    notificar_cita($citaId, 'cancelada');
    return ['ok' => true];
}

/** Reprograma liberando el horario anterior y tomando el nuevo en una sola operación. */
function reprogramar_cita(int $citaId, string $fecha, string $hora): array
{
    $cita = fila('SELECT * FROM citas WHERE id = ?', [$citaId]);
    if (!$cita) {
        return ['ok' => false, 'error' => 'No encontramos la cita.'];
    }
    if (!cita_modificable($cita)) {
        return ['ok' => false, 'error' =>
            'Esta cita ya no se puede reprogramar en línea. Escríbanos por WhatsApp y la gestionamos.'];
    }
    $hora = substr($hora, 0, 5);
    if (!in_array($hora, horas_disponibles((int) $cita['odontologo_id'], $fecha, $citaId), true)) {
        return ['ok' => false, 'error' => 'Ese horario ya no está disponible. Elija otro, por favor.'];
    }

    try {
        q('UPDATE citas SET fecha = ?, hora = ?, estado = ?, recordatorio_enviado = 0 WHERE id = ?',
          [$fecha, $hora . ':00', 'pendiente', $citaId]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['ok' => false, 'error' => 'Otra persona acaba de tomar ese horario. Elija otro, por favor.'];
        }
        throw $e;
    }

    notificar_cita($citaId, 'reprogramada');
    return ['ok' => true];
}

function cambiar_estado_cita(int $citaId, string $estado): bool
{
    if (!in_array($estado, ['pendiente', 'confirmada', 'atendida', 'cancelada', 'no_asistio'], true)) {
        return false;
    }
    q('UPDATE citas SET estado = ? WHERE id = ?', [$estado, $citaId]);
    if ($estado === 'confirmada') {
        notificar_cita($citaId, 'confirmada');
    }
    return true;
}

/** Cita con los datos del paciente, el tratamiento y la sede. */
function cita_completa(int $citaId): ?array
{
    return fila(
        'SELECT c.*, t.nombre AS tratamiento, t.duracion_min, s.nombre AS sede, s.direccion,
                u.nombre AS paciente_nombre, u.apellido AS paciente_apellido,
                u.email AS paciente_email, u.telefono AS paciente_telefono,
                d.nombre AS odontologo_nombre, d.apellido AS odontologo_apellido
         FROM citas c
         JOIN tratamientos t ON t.id = c.tratamiento_id
         JOIN sedes s        ON s.id = c.sede_id
         JOIN usuarios u     ON u.id = c.paciente_id
         JOIN usuarios d     ON d.id = c.odontologo_id
         WHERE c.id = ?', [$citaId]);
}

function citas_de_paciente(int $pacienteId, bool $soloProximas = false): array
{
    $sql = 'SELECT c.*, t.nombre AS tratamiento, s.nombre AS sede, s.direccion,
                   d.nombre AS odontologo_nombre, d.apellido AS odontologo_apellido
            FROM citas c
            JOIN tratamientos t ON t.id = c.tratamiento_id
            JOIN sedes s        ON s.id = c.sede_id
            JOIN usuarios d     ON d.id = c.odontologo_id
            WHERE c.paciente_id = ?';
    $par = [$pacienteId];
    if ($soloProximas) {
        $sql .= " AND c.fecha >= ? AND c.estado IN ('pendiente','confirmada')";
        $par[] = date('Y-m-d');
    }
    $sql .= ' ORDER BY c.fecha DESC, c.hora DESC';
    return filas($sql, $par);
}

/**
 * Notificaciones por correo. Si el envío falla, la cita igual queda
 * registrada: el correo nunca condiciona la reserva.
 */
function notificar_cita(int $citaId, string $evento): void
{
    $c = cita_completa($citaId);
    if (!$c) {
        return;
    }

    $cuando = fecha_larga($c['fecha']) . ' a las ' . hora_legible($c['hora']);
    $titulos = [
        'creada'       => 'Recibimos su solicitud de cita',
        'confirmada'   => 'Su cita quedó confirmada',
        'reprogramada' => 'Su cita cambió de fecha',
        'cancelada'    => 'Su cita fue cancelada',
    ];
    $intro = [
        'creada'       => 'Tenemos su solicitud. Le confirmamos el horario muy pronto.',
        'confirmada'   => 'La esperamos en el consultorio.',
        'reprogramada' => 'Esta es su nueva fecha.',
        'cancelada'    => 'La cita quedó cancelada. Puede agendar otra cuando quiera.',
    ];

    $html = '<p>Hola ' . e($c['paciente_nombre']) . ', ' . e($intro[$evento] ?? '') . '</p>
        <table style="border-collapse:collapse;margin:16px 0;font-size:15px">
          <tr><td style="padding:4px 16px 4px 0;color:#6B6478">Tratamiento</td>
              <td style="padding:4px 0"><strong>' . e($c['tratamiento']) . '</strong></td></tr>
          <tr><td style="padding:4px 16px 4px 0;color:#6B6478">Cuándo</td>
              <td style="padding:4px 0">' . e($cuando) . '</td></tr>
          <tr><td style="padding:4px 16px 4px 0;color:#6B6478">Dónde</td>
              <td style="padding:4px 0">' . e($c['sede'] . ' · ' . $c['direccion']) . '</td></tr>
          <tr><td style="padding:4px 16px 4px 0;color:#6B6478">Código</td>
              <td style="padding:4px 0">' . e($c['codigo']) . '</td></tr>
        </table>';

    enviar_correo($c['paciente_email'],
        ($titulos[$evento] ?? 'Su cita') . ' · ' . SITIO_NOMBRE,
        plantilla_correo($titulos[$evento] ?? 'Su cita', $html));
}
