<?php
require_once __DIR__ . '/includes/auth.php';

$slug = $_GET['t'] ?? '';
$t = tratamiento_por_slug($slug);

if (!$t) {
    http_response_code(404);
    $titulo = 'Tratamiento no encontrado · ' . SITIO_NOMBRE;
    require __DIR__ . '/includes/header.php';
    echo '<section class="seccion"><div class="envoltura vacio">
            <h3>No encontramos ese tratamiento</h3>
            <p>Puede que el enlace haya cambiado.</p>
            <a class="boton" href="' . base_url('tratamientos.php') . '">Ver todos los tratamientos</a>
          </div></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

$titulo = $t['nombre'] . ' · ' . SITIO_NOMBRE;
$descripcion = $t['resumen'];
$otros = array_values(array_filter(tratamientos_activos(), fn($o) => $o['slug'] !== $t['slug']));
$incluye = lineas($t['incluye']);

require __DIR__ . '/includes/header.php';
?>

<section class="seccion seccion--ras">
  <div class="envoltura">
    <p class="menudo"><a class="enlace" href="<?= base_url('tratamientos.php') ?>">Tratamientos</a></p>
    <div class="ficha" style="margin-top:1rem">
      <div>
        <h1 style="font-size:clamp(2.25rem,1.8rem+2vw,3.25rem)"><?= e($t['nombre']) ?></h1>
        <p class="entrada"><?= e($t['resumen']) ?></p>
        <div class="fila" style="margin-top:1.75rem">
          <a class="boton" href="<?= base_url('agendar.php?t=' . urlencode($t['slug'])) ?>">Agendar este tratamiento</a>
        </div>
        <div class="ficha__datos">
          <div class="ficha__dato">
            <b><?= (int) $t['duracion_min'] ?> min</b>
            <span>duración de la sesión</span>
          </div>
          <div class="ficha__dato">
            <b>Sin costo</b>
            <span>la valoración inicial</span>
          </div>
          <?php if ($incluye): ?>
            <div class="ficha__dato">
              <b><?= count($incluye) ?></b>
              <span><?= count($incluye) === 1 ? 'paso incluido' : 'pasos incluidos' ?></span>
            </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="ficha__foto">
        <img src="<?= base_url(e($t['imagen'])) ?>" alt="<?= e($t['nombre']) ?>">
      </div>
    </div>
  </div>
</section>

<section class="seccion" style="padding-top:clamp(1.5rem,1rem+1vw,2.25rem)">
  <div class="envoltura" style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,18rem);gap:clamp(2rem,1rem+4vw,4rem);align-items:start">
    <div class="medida">
      <?= parrafos($t['descripcion']) ?>
    </div>

    <?php if ($incluye): ?>
      <aside class="tarjeta" style="padding:1.75rem">
        <h4 style="margin-bottom:1rem">El tratamiento incluye</h4>
        <ul style="list-style:none;margin:0;padding:0;display:grid;gap:.7rem;font-size:.9375rem">
          <?php foreach ($incluye as $linea): ?>
            <li style="display:flex;gap:.6rem;align-items:flex-start">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="flex:0 0 auto;margin-top:.3rem" aria-hidden="true">
                <path d="M20 6 9 17l-5-5" stroke="var(--lila)" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
              <span class="plomo"><?= e($linea) ?></span>
            </li>
          <?php endforeach; ?>
        </ul>
        <a class="boton boton--ancho" style="margin-top:1.5rem"
           href="<?= base_url('agendar.php?t=' . urlencode($t['slug'])) ?>">Agendar</a>
      </aside>
    <?php endif; ?>
  </div>
</section>

<section class="seccion seccion--lavanda">
  <div class="envoltura">
    <div class="rotulo">
      <h2 style="font-size:clamp(1.6rem,1.3rem+1.4vw,2.25rem)">Otros tratamientos</h2>
      <a class="enlace" href="<?= base_url('tratamientos.php') ?>">Ver todos</a>
    </div>
    <div class="tratamientos">
      <?php foreach (array_slice($otros, 0, 3) as $o): ?>
        <a class="pieza" href="<?= base_url('tratamiento.php?t=' . urlencode($o['slug'])) ?>">
          <div class="pieza__foto">
            <img src="<?= base_url(e($o['imagen'])) ?>" alt="<?= e($o['nombre']) ?>" loading="lazy">
          </div>
          <div class="pieza__cuerpo">
            <h3><?= e($o['nombre']) ?></h3>
            <p><?= e($o['resumen']) ?></p>
            <div class="pieza__pie"><span>Conocer el tratamiento</span></div>
          </div>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
