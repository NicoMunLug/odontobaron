<?php
require_once __DIR__ . '/../includes/auth.php';
$titulo = 'Tratamientos · Panel';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_personal();
    exigir_csrf();
    $id = (int) ($_POST['id'] ?? 0);

    if (($_POST['accion'] ?? '') === 'activo' && $id) {
        q('UPDATE tratamientos SET activo = 1 - activo WHERE id = ?', [$id]);
        flash('exito', 'El tratamiento cambió de estado en el sitio.');
    } elseif (($_POST['accion'] ?? '') === 'guardar' && $id) {
        $duracion = max(15, min(240, (int) ($_POST['duracion_min'] ?? 45)));
        $precio   = $_POST['precio_desde'] === '' ? null : (float) $_POST['precio_desde'];
        q('UPDATE tratamientos SET resumen = ?, duracion_min = ?, precio_desde = ? WHERE id = ?',
          [trim($_POST['resumen'] ?? ''), $duracion, $precio, $id]);
        flash('exito', 'Guardamos los cambios.');
    }
    redirigir('admin/tratamientos.php');
}

require __DIR__ . '/../includes/admin-layout.php';

$lista = filas('SELECT * FROM tratamientos ORDER BY orden, nombre');
$desde90 = date('Y-m-d', strtotime('-90 days'));
?>
<div class="obra__cabeza">
  <div>
    <h1>Tratamientos</h1>
    <p>Lo que se muestra en el sitio y cuánto dura cada cita</p>
  </div>
</div>

<?php foreach ($lista as $t):
    $pedidos = (int) valor('SELECT COUNT(*) FROM citas WHERE tratamiento_id = ? AND fecha >= ?',
                           [$t['id'], $desde90], 0); ?>
  <section class="bloque">
    <div class="bloque__cabeza">
      <div>
        <h2><?= e($t['nombre']) ?></h2>
        <p><?= $pedidos ?> cita<?= $pedidos === 1 ? '' : 's' ?> en los últimos 90 días
           · <a class="enlace" href="<?= base_url('tratamiento.php?t=' . urlencode($t['slug'])) ?>" target="_blank">ver en el sitio</a></p>
      </div>
      <form method="post">
        <?= csrf_campo() ?>
        <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
        <input type="hidden" name="accion" value="activo">
        <button class="boton boton--contorno boton--menudo" type="submit">
          <?= $t['activo'] ? 'Ocultar del sitio' : 'Mostrar en el sitio' ?>
        </button>
      </form>
    </div>

    <?php if (!$t['activo']): ?>
      <div class="aviso aviso--aviso" style="margin-bottom:1rem">
        <span>Este tratamiento está oculto: no aparece en el sitio ni se puede agendar.</span>
      </div>
    <?php endif; ?>

    <form method="post" style="display:grid;grid-template-columns:minmax(0,1fr) 8rem 10rem auto;gap:.75rem;align-items:end">
      <?= csrf_campo() ?>
      <input type="hidden" name="id" value="<?= (int) $t['id'] ?>">
      <input type="hidden" name="accion" value="guardar">
      <label class="campo" style="margin:0">
        <span>Resumen que se ve en el listado</span>
        <input type="text" name="resumen" value="<?= e($t['resumen']) ?>" maxlength="220" required>
      </label>
      <label class="campo" style="margin:0">
        <span>Duración</span>
        <input type="number" name="duracion_min" value="<?= (int) $t['duracion_min'] ?>" min="15" max="240" step="15">
      </label>
      <label class="campo" style="margin:0">
        <span>Desde (opcional)</span>
        <input type="number" name="precio_desde" value="<?= $t['precio_desde'] !== null ? (int) $t['precio_desde'] : '' ?>"
               min="0" step="1000" placeholder="Sin precio">
      </label>
      <button class="boton boton--menudo" type="submit">Guardar</button>
    </form>
  </section>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/admin-pie.php'; ?>
