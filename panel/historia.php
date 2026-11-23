<?php
require_once __DIR__ . '/../includes/auth.php';
exigir_sesion();
$u = usuario_actual();
if (es_personal()) { redirigir('admin/index.php'); }

$titulo = 'Mi historia clínica · ' . SITIO_NOMBRE;
$registros = filas(
  'SELECT h.*, d.nombre AS doc_nombre, d.apellido AS doc_apellido
   FROM historia_clinica h JOIN usuarios d ON d.id = h.odontologo_id
   WHERE h.paciente_id = ? ORDER BY h.fecha DESC', [$u['id']]);

require __DIR__ . '/../includes/header.php';
?>
<section class="seccion">
  <div class="envoltura" style="max-width:48rem">
    <p class="menudo"><a class="enlace" href="<?= base_url('panel/index.php') ?>">Mis citas</a></p>
    <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Mi historia clínica</h1>
    <p class="entrada">Lo que se registró en cada sesión. Solo puede consultarla, no modificarla.</p>

    <?php if (!$registros): ?>
      <div class="vacio">
        <h3>Todavía no hay registros</h3>
        <p>Aparecerán aquí después de su primera consulta.</p>
      </div>
    <?php else: ?>
      <?php foreach ($registros as $r): ?>
        <article class="tarjeta" style="margin-bottom:1rem">
          <div class="fila fila--entre" style="margin-bottom:.75rem">
            <strong><?= e(fecha_larga($r['fecha'])) ?>, <?= date('Y', strtotime($r['fecha'])) ?></strong>
            <span class="tenue menudo">Dra. <?= e($r['doc_nombre'] . ' ' . $r['doc_apellido']) ?></span>
          </div>
          <?php foreach ([
              'Motivo de consulta' => $r['motivo'],
              'Diagnóstico'        => $r['diagnostico'],
              'Tratamiento realizado' => $r['tratamiento_realizado'],
              'Fórmula médica'     => $r['formula_medica'],
              'Observaciones'      => $r['observaciones'],
          ] as $rotulo => $valor): ?>
            <?php if (trim((string) $valor) !== ''): ?>
              <p style="margin-bottom:.6rem">
                <span class="tenue menudo" style="display:block"><?= e($rotulo) ?></span>
                <?= nl2br(e($valor)) ?>
              </p>
            <?php endif; ?>
          <?php endforeach; ?>
        </article>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/../includes/footer.php'; ?>
