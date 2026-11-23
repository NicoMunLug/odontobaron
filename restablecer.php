<?php
/**
 * Creación de la contraseña nueva a partir del enlace recibido por correo.
 */
require_once __DIR__ . '/includes/claves.php';

$token = $_GET['t'] ?? $_POST['t'] ?? '';
$usuario = usuario_de_token($token);

$titulo = 'Nueva contraseña · ' . SITIO_NOMBRE;
$error  = null;
$listo  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $usuario !== null) {
    exigir_csrf();
    $error = restablecer_con_token(
        $token,
        $_POST['password'] ?? '',
        $_POST['password2'] ?? ''
    );
    if ($error === null) {
        $listo = true;
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="envoltura" style="max-width:26rem">

    <?php if ($listo): ?>
      <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Contraseña cambiada</h1>
      <p class="plomo">
        Ya puede entrar con la contraseña nueva. El enlace que usó quedó
        anulado, así que nadie más podrá volver a abrirlo.
      </p>
      <a class="boton boton--ancho" href="<?= base_url('ingresar.php') ?>">Ingresar</a>

    <?php elseif ($usuario === null): ?>
      <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Este enlace ya no sirve</h1>
      <p class="plomo">
        Los enlaces duran una hora y se pueden usar una sola vez. Pida uno
        nuevo y le llegará al correo en seguida.
      </p>
      <a class="boton boton--ancho" href="<?= base_url('recuperar.php') ?>">Pedir un enlace nuevo</a>

    <?php else: ?>
      <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Nueva contraseña</h1>
      <p class="plomo">
        Hola, <?= e($usuario['nombre']) ?>. Escriba la contraseña con la que
        quiere entrar de ahora en adelante.
      </p>

      <?php if ($error): ?>
        <div class="aviso aviso--error" role="alert"><?= e($error) ?></div>
      <?php endif; ?>

      <form method="post" class="tarjeta">
        <?= csrf_campo() ?>
        <input type="hidden" name="t" value="<?= e($token) ?>">
        <label class="campo">
          <span>Contraseña nueva</span>
          <input type="password" name="password" required minlength="<?= CLAVE_MINIMA ?>"
                 autocomplete="new-password" autofocus>
        </label>
        <label class="campo">
          <span>Repítala</span>
          <input type="password" name="password2" required minlength="<?= CLAVE_MINIMA ?>"
                 autocomplete="new-password">
        </label>
        <p class="menudo tenue" style="margin:-.4rem 0 1.1rem">
          Mínimo <?= CLAVE_MINIMA ?> caracteres, con letras y números.
        </p>
        <button class="boton boton--ancho" type="submit">Guardar contraseña</button>
      </form>
    <?php endif; ?>

  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
