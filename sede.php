<?php
require_once __DIR__ . '/includes/auth.php';
$titulo = 'El consultorio · ' . SITIO_NOMBRE;
$descripcion = 'Consultorio de la Dra. Alejandra Barón en Palermo, Teusaquillo, Bogotá. Cómo llegar y horarios de atención.';
$sede = sede_principal();
require __DIR__ . '/includes/header.php';
?>
<section class="seccion seccion--ras">
  <div class="envoltura" style="max-width:46rem">
    <h1 style="font-size:clamp(2.25rem,1.8rem+2vw,3.25rem)">El consultorio</h1>
    <p class="entrada">
      Atendemos en <?= e($sede['barrio'] ?? 'Palermo') ?>, en el centro de Bogotá.
      Un solo consultorio, citas sin sobrecupo y tiempo suficiente para cada paciente.
    </p>
  </div>
</section>

<section class="seccion" style="padding-top:0">
  <div class="envoltura">
    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,20rem);gap:clamp(2rem,1rem+3vw,3.5rem);align-items:start">
      <div style="border-radius:var(--radio-l);overflow:hidden;border:1px solid var(--linea);aspect-ratio:16/10;background:var(--lavanda)">
        <?php if (!empty($sede['mapa_embed'])): ?>
          <iframe src="<?= e($sede['mapa_embed']) ?>" width="100%" height="100%" style="border:0;display:block"
                  allowfullscreen loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                  title="Mapa del consultorio"></iframe>
        <?php endif; ?>
      </div>

      <aside class="tarjeta" style="padding:1.75rem">
        <h3 style="font-size:1.25rem"><?= e($sede['nombre']) ?></h3>
        <p class="plomo" style="margin-bottom:1.25rem">
          <?= e($sede['direccion']) ?><br>
          <?= e($sede['barrio']) ?><br>
          <?= e($sede['ciudad']) ?>
        </p>

        <h4>Horarios</h4>
        <ul style="list-style:none;margin:0 0 1.5rem;padding:0;display:grid;gap:.45rem;font-size:.9375rem" class="plomo">
          <li class="fila fila--entre"><span>Lunes a viernes</span><span>8:00 a. m. – 6:00 p. m.</span></li>
          <li class="fila fila--entre"><span>Sábados</span><span>9:00 a. m. – 1:00 p. m.</span></li>
          <li class="fila fila--entre"><span>Domingos y festivos</span><span>Cerrado</span></li>
        </ul>

        <a class="boton boton--ancho" href="<?= base_url('agendar.php') ?>">Agendar cita</a>
        <a class="boton boton--contorno boton--ancho" style="margin-top:.6rem"
           href="<?= e(whatsapp_url('Hola, quiero saber cómo llegar al consultorio.')) ?>"
           target="_blank" rel="noopener">Preguntar por WhatsApp</a>
      </aside>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
