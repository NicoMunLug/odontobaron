<?php
require_once __DIR__ . '/includes/auth.php';

$token = $_GET['token'] ?? '';
$u = $token ? fila('SELECT * FROM usuarios WHERE token_verificacion = ?', [$token]) : null;

if ($u) {
    q('UPDATE usuarios SET email_verificado = 1, token_verificacion = NULL WHERE id = ?', [$u['id']]);
    flash('exito', 'Su cuenta quedó confirmada. Ya puede ingresar.');
    redirigir('ingresar.php');
}

$titulo = 'Confirmar cuenta · ' . SITIO_NOMBRE;
require __DIR__ . '/includes/header.php';
?>
<section class="seccion">
  <div class="envoltura vacio" style="max-width:34rem">
    <h3>Este enlace ya no sirve</h3>
    <p>Puede que la cuenta ya esté confirmada o que el enlace haya caducado.</p>
    <a class="boton" href="<?= base_url('ingresar.php') ?>">Ir a ingresar</a>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
