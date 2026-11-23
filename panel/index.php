<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/citas.php';

exigir_sesion();
if (es_personal()) {
    redirigir('admin/index.php');
}

$u = usuario_actual();
$titulo = 'Mis citas · ' . SITIO_NOMBRE;

// --- acciones -----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $citaId = (int) ($_POST['cita_id'] ?? 0);
    $cita = fila('SELECT * FROM citas WHERE id = ? AND paciente_id = ?', [$citaId, $u['id']]);

    if ($cita && ($_POST['accion'] ?? '') === 'cancelar') {
        $r = cancelar_cita($citaId, trim($_POST['motivo'] ?? ''));
        flash($r['ok'] ? 'exito' : 'error',
              $r['ok'] ? 'La cita quedó cancelada y el horario volvió a quedar libre.' : $r['error']);
    }
    redirigir('panel/index.php');
}

$todas     = citas_de_paciente((int) $u['id']);
$proximas  = array_filter($todas, fn($c) =>
    $c['fecha'] >= date('Y-m-d') && in_array($c['estado'], ['pendiente', 'confirmada'], true));
$historial = array_filter($todas, fn($c) => !in_array($c, $proximas, true));

$saldo = (float) valor(
    'SELECT COALESCE(SUM(c.valor),0) - (
        SELECT COALESCE(SUM(p.valor),0) FROM pagos p WHERE p.paciente_id = ? AND p.estado = \'aprobado\')
     FROM citas c WHERE c.paciente_id = ? AND c.estado = \'atendida\'',
    [$u['id'], $u['id']], 0);

require __DIR__ . '/../includes/header.php';
?>
<section class="seccion seccion--ras">
  <div class="envoltura">
    <div class="rotulo" style="margin-bottom:1.5rem">
      <div>
        <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem);margin-bottom:.2rem">
          Hola, <?= e($u['nombre']) ?>
        </h1>
        <p class="plomo" style="margin:0">Aquí están sus citas y su historia clínica.</p>
      </div>
      <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar otra cita</a>
    </div>
  </div>
</section>

<section class="seccion" style="padding-top:0">
  <div class="envoltura">

    <h2 style="font-size:1.4rem">Próximas citas</h2>
    <?php if (!$proximas): ?>
      <div class="vacio">
        <h3>No tiene citas programadas</h3>
        <p>Cuando agende una, aparecerá aquí con la opción de cambiarla o cancelarla.</p>
        <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar mi cita</a>
      </div>
    <?php else: ?>
      <?php foreach ($proximas as $c): ?>
        <?php $ts = strtotime($c['fecha']); $modificable = cita_modificable($c); ?>
        <article class="cita">
          <div class="cita__fecha">
            <strong><?= date('j', $ts) ?></strong>
            <small><?= e(mes_abrev($c['fecha'])) ?></small>
          </div>
          <div>
            <div class="fila" style="gap:.6rem;margin-bottom:.2rem">
              <strong><?= e($c['tratamiento']) ?></strong>
              <span class="marca-estado estado-<?= e($c['estado']) ?>"><?= e(estado_etiqueta($c['estado'])) ?></span>
            </div>
            <p class="plomo menudo" style="margin:0">
              <?= e(fecha_larga($c['fecha'])) ?> · <?= e(hora_legible($c['hora'])) ?><br>
              <?= e($c['sede'] . ' · ' . $c['direccion']) ?> · código <?= e($c['codigo']) ?>
            </p>
          </div>
          <div class="cita__acciones">
            <?php if ($modificable): ?>
              <a class="boton boton--contorno boton--menudo"
                 href="<?= base_url('panel/reprogramar.php?id=' . (int) $c['id']) ?>">Cambiar</a>
              <form method="post" data-confirmar="¿Seguro que quiere cancelar esta cita?" style="display:inline">
                <?= csrf_campo() ?>
                <input type="hidden" name="cita_id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="accion" value="cancelar">
                <button class="boton boton--contorno boton--menudo" type="submit">Cancelar</button>
              </form>
            <?php else: ?>
              <a class="boton boton--contorno boton--menudo"
                 href="<?= e(whatsapp_url('Hola, necesito cambiar mi cita ' . $c['codigo'] . '.')) ?>"
                 target="_blank" rel="noopener">Escribir para cambiarla</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
      <p class="campo__ayuda" style="margin-top:.9rem">
        Puede cambiar o cancelar en línea hasta <?= CITA_HORAS_ANTICIPACION ?> horas antes de la cita.
      </p>
    <?php endif; ?>

    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(16rem,1fr));gap:1.25rem;margin-top:3rem">
      <a class="tarjeta" href="<?= base_url('panel/historia.php') ?>" style="text-decoration:none;color:inherit">
        <h3 style="font-size:1.15rem">Mi historia clínica</h3>
        <p class="plomo menudo" style="margin:0">Tratamientos realizados y fórmulas médicas.</p>
      </a>
      <div class="tarjeta">
        <h3 style="font-size:1.15rem">Saldo pendiente</h3>
        <p style="font-size:1.6rem;font-weight:300;color:var(--lila-hondo);margin:0">
          <?= $saldo > 0 ? e(dinero($saldo)) : 'Al día' ?>
        </p>
        <?php if ($saldo > 0): ?>
          <p class="plomo menudo" style="margin:.4rem 0 0">De tratamientos ya atendidos.</p>
        <?php endif; ?>
      </div>
      <a class="tarjeta" href="<?= base_url('pagos.php') ?>" style="text-decoration:none;color:inherit">
        <h3 style="font-size:1.15rem">Medios de pago</h3>
        <p class="plomo menudo" style="margin:0">Tarjetas, Nequi, Daviplata, transferencia o efectivo.</p>
      </a>
    </div>

    <?php if ($historial): ?>
      <h2 style="font-size:1.4rem;margin-top:3rem">Historial</h2>
      <?php foreach ($historial as $c): ?>
        <?php $ts = strtotime($c['fecha']); ?>
        <article class="cita">
          <div class="cita__fecha" style="background:var(--hueso)">
            <strong><?= date('j', $ts) ?></strong>
            <small><?= e(mes_abrev($c['fecha'])) ?> <?= date('y', $ts) ?></small>
          </div>
          <div>
            <div class="fila" style="gap:.6rem;margin-bottom:.2rem">
              <strong><?= e($c['tratamiento']) ?></strong>
              <span class="marca-estado estado-<?= e($c['estado']) ?>"><?= e(estado_etiqueta($c['estado'])) ?></span>
            </div>
            <p class="plomo menudo" style="margin:0">
              <?= e(fecha_larga($c['fecha'])) ?> · <?= e(hora_legible($c['hora'])) ?>
            </p>
          </div>
          <div class="cita__acciones">
            <?php if ($c['estado'] === 'atendida'): ?>
              <a class="boton boton--contorno boton--menudo" href="<?= base_url('panel/historia.php') ?>">Ver detalle</a>
            <?php endif; ?>
          </div>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>

  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
