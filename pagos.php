<?php
require_once __DIR__ . '/includes/auth.php';
$titulo = 'Medios de pago · ' . SITIO_NOMBRE;
$descripcion = 'Tarjetas débito y crédito, Nequi, Daviplata, transferencia y efectivo. Pago total o por abonos.';
$medios = [
  ['Tarjetas débito y crédito', 'Franquicias principales. El datáfono está en el consultorio.'],
  ['Nequi y Daviplata', 'Le compartimos el número al confirmar la cita.'],
  ['Transferencia bancaria', 'Envíenos el comprobante y queda registrado en su cuenta.'],
  ['Efectivo', 'En el consultorio, al terminar la sesión.'],
];
require __DIR__ . '/includes/header.php';
?>
<section class="seccion seccion--ras">
  <div class="envoltura" style="max-width:46rem">
    <h1 style="font-size:clamp(2.25rem,1.8rem+2vw,3.25rem)">Medios de pago</h1>
    <p class="entrada">
      Puede pagar el tratamiento completo o por abonos. Cada pago queda registrado
      en su cuenta, con la fecha y el saldo que falta.
    </p>
  </div>
</section>

<section class="seccion" style="padding-top:0">
  <div class="envoltura">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(15rem,1fr));gap:1.25rem">
      <?php foreach ($medios as $m): ?>
        <div class="tarjeta">
          <h3 style="font-size:1.15rem"><?= e($m[0]) ?></h3>
          <p class="plomo menudo" style="margin:0"><?= e($m[1]) ?></p>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="tarjeta" style="margin-top:2rem;background:var(--lavanda);border-color:var(--lavanda-hondo)">
      <h3 style="font-size:1.25rem">Pago por abonos</h3>
      <p class="plomo" style="max-width:44rem">
        En tratamientos largos como ortodoncia, implantes o diseño de sonrisa, puede abonar
        por partes. Acordamos el plan en la valoración y usted ve el saldo actualizado
        cada vez que entra a su cuenta.
      </p>
      <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar valoración</a>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
