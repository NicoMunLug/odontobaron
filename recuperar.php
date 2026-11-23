<?php
/**
 * Solicitud de restablecimiento de contraseña.
 *
 * La respuesta es siempre la misma, exista o no el correo: si cambiara,
 * esta página serviría para averiguar quién es paciente del consultorio.
 */
require_once __DIR__ . '/includes/claves.php';

if (autenticado()) {
    redirigir(es_personal() ? 'admin/index.php' : 'panel/index.php');
}

$titulo  = 'Recuperar contraseña · ' . SITIO_NOMBRE;
$email   = '';
$enviado = false;
$enlaceDemo = null;   // solo se usa con APP_DEBUG activo

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $email = trim($_POST['email'] ?? '');

    $enlace = crear_enlace_restablecimiento($email);
    if ($enlace !== null) {
        $u = fila('SELECT nombre, email FROM usuarios WHERE email = ?', [mb_strtolower($email)]);
        $salio = correo_restablecimiento($u, $enlace);

        // Sin correo configurado el enlace no llegaría a ninguna parte.
        // En modo depuración se muestra en pantalla para poder probar el flujo.
        if (!$salio && APP_DEBUG) {
            $enlaceDemo = $enlace;
        }
    }
    $enviado = true;
}

require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="envoltura" style="max-width:26rem">
    <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Recuperar contraseña</h1>

    <?php if ($enviado): ?>
      <p class="plomo">
        Si <strong><?= e($email) ?></strong> corresponde a una cuenta nuestra,
        allí llegará un enlace para crear una contraseña nueva. Vence en una hora.
      </p>

      <?php if ($enlaceDemo): ?>
        <div class="aviso" style="word-break:break-all">
          <strong>Modo de prueba.</strong> El envío de correo está apagado,
          así que este es el enlace:<br>
          <a class="enlace" href="<?= e($enlaceDemo) ?>"><?= e($enlaceDemo) ?></a>
        </div>
      <?php endif; ?>

      <p class="plomo menudo">
        ¿No le llegó? Revise la carpeta de correo no deseado, o escríbanos al
        <?= e(CONTACTO_TELEFONO) ?> y le ayudamos desde el consultorio.
      </p>
      <p class="centrado" style="margin-top:1.5rem">
        <a class="enlace" href="<?= base_url('ingresar.php') ?>">Volver al ingreso</a>
      </p>

    <?php else: ?>
      <p class="plomo">
        Escriba el correo con el que creó su cuenta y le enviamos un enlace
        para ponerle una contraseña nueva.
      </p>

      <form method="post" class="tarjeta">
        <?= csrf_campo() ?>
        <label class="campo">
          <span>Correo</span>
          <input type="email" name="email" value="<?= e($email) ?>" required
                 autocomplete="email" autofocus>
        </label>
        <button class="boton boton--ancho" type="submit">Enviarme el enlace</button>
      </form>

      <p class="centrado" style="margin-top:1.5rem">
        <a class="enlace" href="<?= base_url('ingresar.php') ?>">Volver al ingreso</a>
      </p>
    <?php endif; ?>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
