<?php
require_once __DIR__ . '/../includes/auth.php';
$titulo = 'Mensajes · Panel';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_personal();
    exigir_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        $campo = ($_POST['accion'] ?? '') === 'respondido' ? 'respondido' : 'leido';
        q("UPDATE mensajes SET $campo = 1 - $campo WHERE id = ?", [$id]);
    }
    redirigir('admin/mensajes.php');
}

require __DIR__ . '/../includes/admin-layout.php';
$mensajes = filas('SELECT * FROM mensajes ORDER BY leido, creado_en DESC LIMIT 200');
?>
<div class="obra__cabeza">
  <div>
    <h1>Mensajes</h1>
    <p><?= count($mensajes) ?> en total · del formulario de contacto</p>
  </div>
</div>

<?php if (!$mensajes): ?>
  <section class="bloque"><div class="vacio" style="padding:2.5rem 1rem">
    <h3>Sin mensajes</h3><p>Aquí llegan los del formulario de contacto.</p>
  </div></section>
<?php else: ?>
  <?php foreach ($mensajes as $m): ?>
    <section class="bloque" id="m<?= (int) $m['id'] ?>"
             style="<?= $m['leido'] ? '' : 'border-color:var(--lila-claro)' ?>">
      <div class="bloque__cabeza">
        <div>
          <h2><?= e($m['asunto']) ?></h2>
          <p><?= e($m['nombre']) ?> · <?= e($m['email']) ?><?= $m['telefono'] ? ' · ' . e($m['telefono']) : '' ?></p>
        </div>
        <div class="fila">
          <span class="tenue menudo"><?= e(fecha_corta(substr($m['creado_en'], 0, 10))) ?></span>
          <?php if ($m['respondido']): ?>
            <span class="marca-estado estado-atendida">Respondido</span>
          <?php elseif (!$m['leido']): ?>
            <span class="marca-estado estado-confirmada">Nuevo</span>
          <?php endif; ?>
        </div>
      </div>

      <p class="plomo" style="max-width:52rem"><?= nl2br(e($m['mensaje'])) ?></p>

      <div class="fila" style="margin-top:1rem">
        <a class="boton boton--menudo" href="mailto:<?= e($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['asunto']) ?>">Responder por correo</a>
        <?php if ($m['telefono']): ?>
          <a class="boton boton--contorno boton--menudo" target="_blank" rel="noopener"
             href="https://wa.me/<?= e(preg_replace('/\D/', '', $m['telefono'])) ?>">WhatsApp</a>
        <?php endif; ?>
        <form method="post" style="display:inline">
          <?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
          <input type="hidden" name="accion" value="leido">
          <button class="boton boton--contorno boton--menudo" type="submit">
            Marcar como <?= $m['leido'] ? 'no leído' : 'leído' ?>
          </button>
        </form>
        <form method="post" style="display:inline">
          <?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
          <input type="hidden" name="accion" value="respondido">
          <button class="boton boton--contorno boton--menudo" type="submit">
            <?= $m['respondido'] ? 'Quitar respondido' : 'Marcar respondido' ?>
          </button>
        </form>
      </div>
    </section>
  <?php endforeach; ?>
<?php endif; ?>
<?php require __DIR__ . '/../includes/admin-pie.php'; ?>
