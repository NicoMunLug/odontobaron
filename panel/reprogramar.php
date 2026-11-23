<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/citas.php';

exigir_sesion();
$u = usuario_actual();
$id = (int) ($_GET['id'] ?? $_POST['cita_id'] ?? 0);
$cita = fila('SELECT * FROM citas WHERE id = ? AND paciente_id = ?', [$id, $u['id']]);

if (!$cita) {
    flash('error', 'No encontramos esa cita.');
    redirigir('panel/index.php');
}

$completa = cita_completa($id);
$titulo = 'Cambiar cita · ' . SITIO_NOMBRE;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $r = reprogramar_cita($id, $_POST['fecha'] ?? '', $_POST['hora'] ?? '');
    if ($r['ok']) {
        flash('exito', 'Su cita quedó para otra fecha. Le confirmamos por correo.');
        redirigir('panel/index.php');
    }
    $error = $r['error'];
}

$agenda = proximos_dias_con_cupo((int) $cita['odontologo_id'], 14);
require __DIR__ . '/../includes/header.php';
?>
<section class="seccion">
  <div class="envoltura" style="max-width:46rem">
    <p class="menudo"><a class="enlace" href="<?= base_url('panel/index.php') ?>">Mis citas</a></p>
    <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Cambiar la fecha</h1>
    <p class="entrada">
      <?= e($completa['tratamiento']) ?>, ahora el <?= e(fecha_larga($cita['fecha'])) ?>
      a las <?= e(hora_legible($cita['hora'])) ?>.
    </p>

    <?php if ($error): ?>
      <div class="aviso aviso--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <?php if (!$agenda): ?>
      <div class="vacio">
        <h3>No hay horarios libres</h3>
        <p>Escríbanos y buscamos un espacio que le sirva.</p>
        <a class="boton" href="<?= e(whatsapp_url('Hola, quiero cambiar mi cita ' . $cita['codigo'] . '.')) ?>"
           target="_blank" rel="noopener">Escribir por WhatsApp</a>
      </div>
    <?php else: ?>
      <form method="post" class="tarjeta" data-agenda='<?= e(json_encode($agenda)) ?>'>
        <?= csrf_campo() ?>
        <input type="hidden" name="cita_id" value="<?= (int) $id ?>">
        <input type="hidden" name="fecha" value="">
        <input type="hidden" name="hora" value="">

        <div class="campo">
          <span class="etiqueta">Nuevo día</span>
          <div class="dias">
            <?php foreach ($agenda as $fecha => $horas): $ts = strtotime($fecha); ?>
              <button type="button" class="dia" aria-pressed="false"
                      data-fecha="<?= e($fecha) ?>" data-legible="<?= e(fecha_larga($fecha)) ?>">
                <small><?= e(dia_abrev($fecha)) ?></small>
                <strong><?= date('j', $ts) ?></strong>
                <small><?= e(mes_abrev($fecha)) ?></small>
              </button>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="campo">
          <span class="etiqueta">Nueva hora <span class="tenue" data-resumen-dia></span></span>
          <div class="horas" data-horas></div>
        </div>

        <button class="boton boton--ancho" type="submit" data-enviar disabled>Guardar el cambio</button>
      </form>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
