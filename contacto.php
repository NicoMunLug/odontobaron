<?php
require_once __DIR__ . '/includes/auth.php';

$titulo = 'Contacto · ' . SITIO_NOMBRE;
$descripcion = 'Escríbanos para una cotización, una duda sobre un tratamiento o para agendar su cita.';

$sede = sede_principal();
$u = usuario_actual();
$enviado = false;
$errores = [];
$d = [
    'nombre'   => $u ? $u['nombre'] . ' ' . $u['apellido'] : '',
    'email'    => $u['email'] ?? '',
    'telefono' => $u['telefono'] ?? '',
    'asunto'   => '',
    'mensaje'  => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $d = array_merge($d, array_map('trim', array_intersect_key($_POST, $d)));

    if (mb_strlen($d['nombre']) < 2)  $errores['nombre'] = 'Escriba su nombre.';
    if (!filter_var($d['email'], FILTER_VALIDATE_EMAIL)) $errores['email'] = 'Revise el correo.';
    if (mb_strlen($d['asunto']) < 3)  $errores['asunto'] = 'Escriba el asunto.';
    if (mb_strlen($d['mensaje']) < 10) $errores['mensaje'] = 'Cuéntenos un poco más.';

    if (!$errores) {
        q('INSERT INTO mensajes (nombre, email, telefono, asunto, mensaje) VALUES (?,?,?,?,?)',
          [$d['nombre'], $d['email'], $d['telefono'] ?: null, $d['asunto'], $d['mensaje']]);

        enviar_correo(CONTACTO_EMAIL, 'Mensaje desde la web: ' . $d['asunto'],
            plantilla_correo('Nuevo mensaje',
                '<p><strong>' . e($d['nombre']) . '</strong><br>' . e($d['email']) .
                ($d['telefono'] ? ' · ' . e($d['telefono']) : '') . '</p>' .
                '<p>' . nl2br(e($d['mensaje'])) . '</p>'));

        $enviado = true;
        $d = array_map(fn() => '', $d);
    }
}

$campo = fn(string $k) => isset($errores[$k]) ? ' aria-invalid="true"' : '';
$err = fn(string $k) => isset($errores[$k]) ? '<span class="campo__error">' . e($errores[$k]) . '</span>' : '';

require __DIR__ . '/includes/header.php';
?>
<section class="seccion seccion--ras">
  <div class="envoltura" style="max-width:46rem">
    <h1 style="font-size:clamp(2.25rem,1.8rem+2vw,3.25rem)">Contacto</h1>
    <p class="entrada">
      Si quiere una cotización, tiene una duda sobre un tratamiento o prefiere
      que lo llamemos, déjenos sus datos.
    </p>
  </div>
</section>

<section class="seccion" style="padding-top:0">
  <div class="envoltura">
    <div style="display:grid;grid-template-columns:minmax(0,1fr) minmax(0,20rem);gap:clamp(2rem,1rem+3vw,3.5rem);align-items:start">

      <div>
        <?php if ($enviado): ?>
          <div class="aviso aviso--exito" role="status">
            <span><strong>Recibimos su mensaje.</strong> Le respondemos al correo que nos dejó, normalmente el mismo día.</span>
          </div>
        <?php elseif ($errores): ?>
          <div class="aviso aviso--error" role="alert">Revise los campos marcados.</div>
        <?php endif; ?>

        <form method="post" class="tarjeta">
          <?= csrf_campo() ?>
          <div class="pareja">
            <label class="campo">
              <span>Nombre</span>
              <input type="text" name="nombre" value="<?= e($d['nombre']) ?>" required<?= $campo('nombre') ?> autocomplete="name">
              <?= $err('nombre') ?>
            </label>
            <label class="campo">
              <span>Celular <span class="tenue">(opcional)</span></span>
              <input type="tel" name="telefono" value="<?= e($d['telefono']) ?>" autocomplete="tel">
            </label>
          </div>

          <label class="campo">
            <span>Correo</span>
            <input type="email" name="email" value="<?= e($d['email']) ?>" required<?= $campo('email') ?> autocomplete="email">
            <?= $err('email') ?>
          </label>

          <label class="campo">
            <span>Asunto</span>
            <input type="text" name="asunto" value="<?= e($d['asunto']) ?>" required<?= $campo('asunto') ?>
                   placeholder="Cotización de diseño de sonrisa">
            <?= $err('asunto') ?>
          </label>

          <label class="campo">
            <span>Mensaje</span>
            <textarea name="mensaje" rows="5" required<?= $campo('mensaje') ?>><?= e($d['mensaje']) ?></textarea>
            <?= $err('mensaje') ?>
          </label>

          <button class="boton boton--ancho" type="submit">Enviar mensaje</button>
        </form>
      </div>

      <aside class="tarjeta" style="padding:1.75rem">
        <h4>Más rápido por WhatsApp</h4>
        <p class="plomo menudo">Si su pregunta es corta, escríbanos directamente.</p>
        <a class="boton boton--ancho" href="<?= e(whatsapp_url('Hola, tengo una pregunta.')) ?>"
           target="_blank" rel="noopener">Escribir por WhatsApp</a>

        <hr style="border:0;border-top:1px solid var(--linea);margin:1.5rem 0">

        <h4>El consultorio</h4>
        <p class="plomo menudo" style="margin-bottom:1rem">
          <?= e($sede['direccion'] ?? '') ?><br>
          <?= e(($sede['barrio'] ?? '') . ' · ' . ($sede['ciudad'] ?? '')) ?>
        </p>
        <p class="menudo" style="margin:0">
          <a class="enlace" href="<?= e(instagram_url()) ?>" target="_blank" rel="noopener">@<?= e(CONTACTO_INSTAGRAM) ?></a><br>
          <a class="enlace" href="<?= e(whatsapp_url()) ?>" target="_blank" rel="noopener"><?= e(CONTACTO_TELEFONO) ?></a>
        </p>
      </aside>
    </div>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
