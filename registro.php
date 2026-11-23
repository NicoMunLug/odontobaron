<?php
require_once __DIR__ . '/includes/auth.php';

if (autenticado()) {
    redirigir('panel/index.php');
}

$titulo = 'Crear cuenta · ' . SITIO_NOMBRE;
$errores = [];
$d = ['nombre' => '', 'apellido' => '', 'email' => '', 'telefono' => '',
      'tipo_documento' => 'CC', 'documento' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $d = array_merge($d, array_intersect_key($_POST, $d));
    $r = registrar_paciente($_POST);

    if ($r['ok']) {
        if (MAIL_ACTIVO) {
            flash('exito', 'Le enviamos un correo para confirmar su cuenta. Revise su bandeja de entrada.');
            redirigir('ingresar.php');
        }
        // sin correo configurado, la cuenta queda lista de una vez
        q('UPDATE usuarios SET email_verificado = 1, token_verificacion = NULL WHERE id = ?', [$r['id']]);
        iniciar_sesion($_POST['email'], $_POST['password']);
        flash('exito', 'Su cuenta quedó lista.');
        sesion_iniciar();
        $destino = $_SESSION['destino'] ?? null;
        unset($_SESSION['destino']);
        redirigir($destino ?: 'panel/index.php');
    }
    $errores = $r['errores'];
}

$campo = fn(string $k) => isset($errores[$k])
    ? ' aria-invalid="true"' : '';
$err = fn(string $k) => isset($errores[$k])
    ? '<span class="campo__error">' . e($errores[$k]) . '</span>' : '';

require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="envoltura" style="max-width:34rem">
    <h1 style="font-size:clamp(1.9rem,1.6rem+1.4vw,2.5rem)">Crear cuenta</h1>
    <p class="plomo">Con su cuenta puede agendar, cambiar o cancelar citas, y ver su historia clínica.</p>

    <?php if ($errores): ?>
      <div class="aviso aviso--error" role="alert">Revise los campos marcados.</div>
    <?php endif; ?>

    <form method="post" class="tarjeta">
      <?= csrf_campo() ?>

      <div class="pareja">
        <label class="campo">
          <span>Nombres</span>
          <input type="text" name="nombre" value="<?= e($d['nombre']) ?>" required<?= $campo('nombre') ?> autocomplete="given-name">
          <?= $err('nombre') ?>
        </label>
        <label class="campo">
          <span>Apellidos</span>
          <input type="text" name="apellido" value="<?= e($d['apellido']) ?>" required<?= $campo('apellido') ?> autocomplete="family-name">
          <?= $err('apellido') ?>
        </label>
      </div>

      <div class="pareja">
        <label class="campo">
          <span>Tipo de documento</span>
          <select name="tipo_documento"<?= $campo('tipo_documento') ?>>
            <?php foreach (['CC' => 'Cédula de ciudadanía', 'TI' => 'Tarjeta de identidad',
                            'CE' => 'Cédula de extranjería', 'PA' => 'Pasaporte'] as $k => $v): ?>
              <option value="<?= $k ?>" <?= $d['tipo_documento'] === $k ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="campo">
          <span>Número</span>
          <input type="text" name="documento" value="<?= e($d['documento']) ?>" required<?= $campo('documento') ?> inputmode="numeric">
          <?= $err('documento') ?>
        </label>
      </div>

      <label class="campo">
        <span>Correo</span>
        <input type="email" name="email" value="<?= e($d['email']) ?>" required<?= $campo('email') ?> autocomplete="email">
        <?= $err('email') ?>
      </label>

      <label class="campo">
        <span>Celular</span>
        <input type="tel" name="telefono" value="<?= e($d['telefono']) ?>" required<?= $campo('telefono') ?>
               autocomplete="tel" placeholder="+57 300 000 0000">
        <?= $err('telefono') ?>
      </label>

      <div class="pareja">
        <label class="campo">
          <span>Contraseña</span>
          <input type="password" name="password" required<?= $campo('password') ?> autocomplete="new-password">
          <?= $err('password') ?: '<span class="campo__ayuda">Mínimo 8 caracteres.</span>' ?>
        </label>
        <label class="campo">
          <span>Repetir contraseña</span>
          <input type="password" name="password2" required<?= $campo('password2') ?> autocomplete="new-password">
          <?= $err('password2') ?>
        </label>
      </div>

      <button class="boton boton--ancho" type="submit">Crear mi cuenta</button>
      <p class="campo__ayuda centrado" style="margin-top:.9rem">
        Sus datos se tratan conforme a la Ley 1581 de 2012 y solo se usan para su atención.
      </p>
    </form>

    <p class="centrado" style="margin-top:1.5rem">
      ¿Ya tiene cuenta? <a class="enlace" href="<?= base_url('ingresar.php') ?>">Ingrese aquí</a>
    </p>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
