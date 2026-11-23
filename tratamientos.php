<?php
require_once __DIR__ . '/includes/auth.php';

$titulo = 'Tratamientos · ' . SITIO_NOMBRE;
$descripcion = 'Odontología estética, ortodoncia, implantología, endodoncia, periodoncia, cirugía oral y odontología general en Bogotá.';
$tratamientos = tratamientos_activos();

require __DIR__ . '/includes/header.php';
?>

<section class="seccion seccion--ras">
  <div class="envoltura">
    <div style="max-width:40rem">
      <h1 style="font-size:clamp(2.25rem,1.8rem+2vw,3.25rem)">Tratamientos</h1>
      <p class="entrada">
        Cada tratamiento empieza por una valoración y un plan que usted puede ver
        antes de decidir. Estos son los siete que realizamos en el consultorio.
      </p>
    </div>
  </div>
</section>

<section class="seccion" style="padding-top:0">
  <div class="envoltura">
    <div class="tratamientos">
      <?php foreach ($tratamientos as $i => $t): ?>
        <a class="pieza <?= $i === 0 ? 'pieza--mayor' : '' ?>"
           href="<?= base_url('tratamiento.php?t=' . urlencode($t['slug'])) ?>">
          <div class="pieza__foto">
            <img src="<?= base_url(e($t['imagen'])) ?>" alt="<?= e($t['nombre']) ?>" loading="lazy">
          </div>
          <div class="pieza__cuerpo">
            <h3><?= e($t['nombre']) ?></h3>
            <p><?= e($t['resumen']) ?></p>
            <div class="pieza__pie">
              <span>Conocer el tratamiento</span>
              <span class="tenue menudo"><?= (int) $t['duracion_min'] ?> min</span>
            </div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="seccion seccion--lavanda">
  <div class="envoltura centrado">
    <h2 style="max-width:30rem;margin-inline:auto">¿No sabe cuál necesita?</h2>
    <p class="entrada" style="max-width:34rem;margin:0 auto 2rem">
      Agende una valoración. Revisamos su caso y le decimos qué conviene,
      sin compromiso de tratamiento.
    </p>
    <div class="fila" style="justify-content:center">
      <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar valoración</a>
      <a class="boton boton--claro" href="<?= e(whatsapp_url('Hola, quisiera una orientación sobre qué tratamiento necesito.')) ?>"
         target="_blank" rel="noopener">Preguntar por WhatsApp</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
