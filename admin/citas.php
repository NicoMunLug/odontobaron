<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/citas.php';

$titulo = 'Citas · Panel';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_personal();
    exigir_csrf();
    $id = (int) ($_POST['cita_id'] ?? 0);
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'estado' && $id) {
        cambiar_estado_cita($id, $_POST['estado'] ?? '');
        flash('exito', 'La cita quedó como ' . mb_strtolower(estado_etiqueta($_POST['estado'] ?? '')) . '.');
    }
    redirigir('admin/citas.php?' . http_build_query(array_filter([
        'estado' => $_POST['f_estado'] ?? '',
        'desde'  => $_POST['f_desde'] ?? '',
        'q'      => $_POST['f_q'] ?? '',
    ])));
}

require __DIR__ . '/../includes/admin-layout.php';

$fEstado = $_GET['estado'] ?? '';
$fDesde  = $_GET['desde'] ?? date('Y-m-d', strtotime('-7 days'));
$fQ      = trim($_GET['q'] ?? '');

$where = ['c.fecha >= ?'];
$par   = [$fDesde];
if ($fEstado !== '' && in_array($fEstado, ['pendiente','confirmada','atendida','cancelada','no_asistio'], true)) {
    $where[] = 'c.estado = ?';
    $par[] = $fEstado;
}
if ($fQ !== '') {
    $where[] = '(u.nombre LIKE ? OR u.apellido LIKE ? OR c.codigo LIKE ? OR u.documento LIKE ?)';
    array_push($par, "%$fQ%", "%$fQ%", "%$fQ%", "%$fQ%");
}

$citas = filas(
    'SELECT c.*, t.nombre AS tratamiento, u.nombre AS pn, u.apellido AS pa,
            u.telefono, u.email, u.documento
     FROM citas c
     JOIN tratamientos t ON t.id = c.tratamiento_id
     JOIN usuarios u     ON u.id = c.paciente_id
     WHERE ' . implode(' AND ', $where) . '
     ORDER BY c.fecha, c.hora
     LIMIT 300', $par);
?>

<div class="obra__cabeza">
  <div>
    <h1>Citas</h1>
    <p><?= count($citas) ?> cita<?= count($citas) === 1 ? '' : 's' ?> desde el <?= e(fecha_corta($fDesde)) ?></p>
  </div>
</div>

<form class="filtros" method="get">
  <input class="crece" type="search" name="q" value="<?= e($fQ) ?>"
         placeholder="Buscar por paciente, documento o código">
  <select name="estado" data-filtro>
    <option value="">Todos los estados</option>
    <?php foreach (['pendiente','confirmada','atendida','cancelada','no_asistio'] as $es): ?>
      <option value="<?= $es ?>" <?= $fEstado === $es ? 'selected' : '' ?>><?= e(estado_etiqueta($es)) ?></option>
    <?php endforeach; ?>
  </select>
  <input type="date" name="desde" value="<?= e($fDesde) ?>" data-filtro>
  <button class="boton boton--menudo" type="submit">Filtrar</button>
  <?php if ($fQ || $fEstado): ?>
    <a class="enlace menudo" href="<?= base_url('admin/citas.php') ?>">Limpiar</a>
  <?php endif; ?>
</form>

<section class="bloque">
  <?php if (!$citas): ?>
    <div class="vacio" style="padding:2.5rem 1rem">
      <h3>Sin citas con esos filtros</h3>
      <p>Pruebe con otra fecha o quite el filtro de estado.</p>
    </div>
  <?php else: ?>
    <div class="tabla-caja">
      <table class="tabla">
        <thead>
          <tr>
            <th>Cuándo</th>
            <th>Paciente</th>
            <th>Tratamiento</th>
            <th>Estado</th>
            <th>Cambiar a</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($citas as $c): ?>
          <tr>
            <td style="white-space:nowrap">
              <?= e(fecha_corta($c['fecha'])) ?><br>
              <span class="tenue menudo"><?= e(hora_legible($c['hora'])) ?></span>
            </td>
            <td>
              <div class="persona">
                <span class="avatar"><?= e(iniciales($c['pn'], $c['pa'])) ?></span>
                <span>
                  <?= e($c['pn'] . ' ' . $c['pa']) ?><br>
                  <span class="tenue menudo"><?= e($c['telefono'] ?: $c['email']) ?></span>
                </span>
              </div>
            </td>
            <td>
              <?= e($c['tratamiento']) ?><br>
              <span class="tenue menudo"><?= e($c['codigo']) ?></span>
            </td>
            <td><span class="marca-estado estado-<?= e($c['estado']) ?>"><?= e(estado_etiqueta($c['estado'])) ?></span></td>
            <td>
              <form method="post" style="display:flex;gap:.4rem;align-items:center">
                <?= csrf_campo() ?>
                <input type="hidden" name="cita_id" value="<?= (int) $c['id'] ?>">
                <input type="hidden" name="accion" value="estado">
                <input type="hidden" name="f_estado" value="<?= e($fEstado) ?>">
                <input type="hidden" name="f_desde" value="<?= e($fDesde) ?>">
                <input type="hidden" name="f_q" value="<?= e($fQ) ?>">
                <select name="estado" style="padding:.35rem .6rem;font-size:.8125rem;width:auto">
                  <?php foreach (['pendiente','confirmada','atendida','no_asistio','cancelada'] as $es): ?>
                    <option value="<?= $es ?>" <?= $c['estado'] === $es ? 'selected' : '' ?>>
                      <?= e(estado_etiqueta($es)) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
                <button class="boton boton--contorno boton--menudo" type="submit">Guardar</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</section>

<?php require __DIR__ . '/../includes/admin-pie.php'; ?>
