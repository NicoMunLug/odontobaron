<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/citas.php';

$titulo = 'Agendar cita · ' . SITIO_NOMBRE;
$descripcion = 'Reserve su cita en línea con la Dra. Alejandra Barón. Vea los horarios libres y elija el que mejor le quede.';

$sede   = sede_principal();
$doctor = fila("SELECT * FROM usuarios WHERE rol = 'odontologo' AND activo = 1 ORDER BY id LIMIT 1");
$tratamientos = tratamientos_activos();
$u = usuario_actual();

$error = null;
sesion_iniciar();

// Retoma una selección que quedó pendiente antes de iniciar sesión
$pendiente = $_SESSION['cita_pendiente'] ?? null;
if ($pendiente && $u) {
    unset($_SESSION['cita_pendiente']);
    $r = crear_cita([
        'paciente_id'    => (int) $u['id'],
        'odontologo_id'  => (int) $doctor['id'],
        'tratamiento_id' => (int) $pendiente['tratamiento_id'],
        'sede_id'        => (int) $sede['id'],
        'fecha'          => $pendiente['fecha'],
        'hora'           => $pendiente['hora'],
        'notas'          => $pendiente['notas'] ?? '',
    ]);
    if ($r['ok']) {
        flash('exito', 'Su cita quedó registrada con el código ' . $r['codigo'] . '.');
        redirigir('panel/index.php');
    }
    $error = $r['error'];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $datos = [
        'tratamiento_id' => (int) ($_POST['tratamiento_id'] ?? 0),
        'fecha'          => $_POST['fecha'] ?? '',
        'hora'           => $_POST['hora'] ?? '',
        'notas'          => trim($_POST['notas'] ?? ''),
    ];

    if (!$datos['tratamiento_id'] || !$datos['fecha'] || !$datos['hora']) {
        $error = 'Elija el tratamiento, el día y la hora para continuar.';
    } elseif (!$u) {
        // guarda la selección y pide identificarse
        $_SESSION['cita_pendiente'] = $datos;
        $_SESSION['destino'] = base_url('agendar.php');
        flash('aviso', 'Ingrese o cree su cuenta y guardamos la cita que acaba de elegir.');
        redirigir('ingresar.php');
    } else {
        $r = crear_cita($datos + [
            'paciente_id'   => (int) $u['id'],
            'odontologo_id' => (int) $doctor['id'],
            'sede_id'       => (int) $sede['id'],
        ]);
        if ($r['ok']) {
            flash('exito', 'Listo. Su cita quedó registrada con el código ' . $r['codigo'] . '. Le confirmamos el horario muy pronto.');
            redirigir('panel/index.php');
        }
        $error = $r['error'];
    }
}

$agenda = $doctor ? proximos_dias_con_cupo((int) $doctor['id'], 14) : [];
$slugElegido = $_GET['t'] ?? ($pendiente['tratamiento_id'] ?? '');

require __DIR__ . '/includes/header.php';
?>

<section class="seccion seccion--ras">
  <div class="envoltura" style="max-width:52rem">
    <h1 style="font-size:clamp(2.25rem,1.8rem+2vw,3.25rem)">Agendar cita</h1>
    <p class="entrada">
      Elija el tratamiento y el horario que le sirva. Le confirmamos por correo
      y le enviamos un recordatorio el día anterior.
    </p>
  </div>
</section>

<section class="seccion" style="padding-top:0">
  <div class="envoltura" style="max-width:52rem">

    <?php if ($error): ?>
      <div class="aviso aviso--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$doctor || !$sede): ?>
      <div class="vacio">
        <h3>La agenda no está disponible</h3>
        <p>Escríbanos por WhatsApp y le damos una cita de una vez.</p>
        <a class="boton" href="<?= e(whatsapp_url('Hola, quiero agendar una cita.')) ?>" target="_blank" rel="noopener">Escribir por WhatsApp</a>
      </div>
    <?php elseif (!$agenda): ?>
      <div class="vacio">
        <h3>No hay horarios libres en los próximos días</h3>
        <p>Escríbanos y buscamos un espacio que le sirva.</p>
        <a class="boton" href="<?= e(whatsapp_url('Hola, no veo horarios libres en la web. ¿Tienen disponibilidad?')) ?>"
           target="_blank" rel="noopener">Escribir por WhatsApp</a>
      </div>
    <?php else: ?>

    <form method="post" class="tarjeta" data-agenda='<?= e(json_encode($agenda)) ?>'>
      <?= csrf_campo() ?>
      <input type="hidden" name="fecha" value="">
      <input type="hidden" name="hora" value="">

      <label class="campo">
        <span>Tratamiento</span>
        <select name="tratamiento_id" required>
          <?php foreach ($tratamientos as $t): ?>
            <option value="<?= (int) $t['id'] ?>"
              <?= ((string) $slugElegido === $t['slug'] || (int) $slugElegido === (int) $t['id']) ? 'selected' : '' ?>>
              <?= e($t['nombre']) ?> · <?= (int) $t['duracion_min'] ?> min
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <div class="campo">
        <span class="etiqueta">Día</span>
        <div class="dias">
          <?php foreach ($agenda as $fecha => $horas): ?>
            <?php $ts = strtotime($fecha); ?>
            <button type="button" class="dia" aria-pressed="false"
                    data-fecha="<?= e($fecha) ?>"
                    data-legible="<?= e(fecha_larga($fecha)) ?>">
              <small><?= e(dia_abrev($fecha)) ?></small>
              <strong><?= date('j', $ts) ?></strong>
              <small><?= e(mes_abrev($fecha)) ?></small>
            </button>
          <?php endforeach; ?>
        </div>
        <span class="campo__ayuda">Mostramos los días con cupo en los próximos <?= CITA_DIAS_MAX ?> días.</span>
      </div>

      <div class="campo">
        <span class="etiqueta">Hora <span class="tenue" data-resumen-dia></span></span>
        <div class="horas" data-horas></div>
      </div>

      <label class="campo">
        <span>¿Algo que debamos saber? <span class="tenue">(opcional)</span></span>
        <textarea name="notas" rows="3" maxlength="400"
                  placeholder="Por ejemplo: es mi primera vez, tengo una molestia en un diente, vengo por control…"></textarea>
      </label>

      <?php if (!$u): ?>
        <div class="aviso aviso--aviso">
          <span>Al continuar le pediremos ingresar o crear su cuenta, y guardamos la cita que eligió.</span>
        </div>
      <?php endif; ?>

      <button class="boton boton--ancho" type="submit" data-enviar disabled>
        <?= $u ? 'Confirmar mi cita' : 'Continuar' ?>
      </button>
      <p class="campo__ayuda centrado" style="margin-top:.9rem">
        Puede cancelar o cambiar la cita hasta <?= CITA_HORAS_ANTICIPACION ?> horas antes, desde su cuenta.
      </p>
    </form>

    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
