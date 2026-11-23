<?php
require_once __DIR__ . '/includes/auth.php';

if (autenticado()) {
    redirigir(es_personal() ? 'admin/index.php' : 'panel/index.php');
}

$titulo = 'Ingresar · ' . SITIO_NOMBRE;
$error = null;
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $email = trim($_POST['email'] ?? '');
    $error = iniciar_sesion($email, $_POST['password'] ?? '');
    if ($error === null) {
        sesion_iniciar();
        $destino = $_SESSION['destino'] ?? null;
        unset($_SESSION['destino']);
        redirigir($destino ?: (es_personal() ? 'admin/index.php' : 'panel/index.php'));
    }
}

require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="envoltura" style="max-width:26rem">
    <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Ingresar</h1>
    <p class="plomo">Entre a su cuenta para ver, cambiar o cancelar sus citas.</p>

    <?php if ($error): ?>
      <div class="aviso aviso--error" role="alert"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" class="tarjeta">
      <?= csrf_campo() ?>
      <label class="campo">
        <span>Correo</span>
        <input type="email" name="email" value="<?= e($email) ?>" required autocomplete="email" autofocus>
      </label>
      <label class="campo">
        <span>Contraseña</span>
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button class="boton boton--ancho" type="submit">Ingresar</button>
    </form>

    <p class="centrado" style="margin-top:1.5rem">
      ¿Primera vez? <a class="enlace" href="<?= base_url('registro.php') ?>">Cree su cuenta</a>
    </p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
