<?php
require_once __DIR__ . '/../includes/auth.php';
$titulo = 'Pacientes · Panel';
require __DIR__ . '/../includes/admin-layout.php';

$q = trim($_GET['q'] ?? '');
$par = [];
$where = "rol = 'paciente'";
if ($q !== '') {
    $where .= ' AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR documento LIKE ?)';
    array_push($par, "%$q%", "%$q%", "%$q%", "%$q%");
}
$pacientes = filas("SELECT * FROM usuarios WHERE $where ORDER BY apellido, nombre LIMIT 200", $par);

$resumen = [];
foreach ($pacientes as $p) {
    $resumen[$p['id']] = [
        'citas'  => (int) valor('SELECT COUNT(*) FROM citas WHERE paciente_id = ?', [$p['id']], 0),
        'ultima' => valor("SELECT MAX(fecha) FROM citas WHERE paciente_id = ? AND estado = 'atendida'", [$p['id']]),
        'saldo'  => (float) valor(
            "SELECT COALESCE((SELECT SUM(valor) FROM citas WHERE paciente_id = ? AND estado = 'atendida'),0)
                  - COALESCE((SELECT SUM(valor) FROM pagos WHERE paciente_id = ? AND estado = 'aprobado'),0)",
            [$p['id'], $p['id']], 0),
    ];
}
?>
<div class="obra__cabeza">
  <div>
    <h1>Pacientes</h1>
    <p><?= count($pacientes) ?> paciente<?= count($pacientes) === 1 ? '' : 's' ?> con cuenta</p>
  </div>
</div>

<form class="filtros" method="get">
  <input class="crece" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre, correo o documento">
  <button class="boton boton--menudo" type="submit">Buscar</button>
  <?php if ($q): ?><a class="enlace menudo" href="<?= base_url('admin/pacientes.php') ?>">Limpiar</a><?php endif; ?>
</form>

<section class="bloque">
  <?php if (!$pacientes): ?>
    <div class="vacio" style="padding:2.5rem 1rem"><h3>Sin resultados</h3><p>Pruebe con otro término.</p></div>
  <?php else: ?>
  <div class="tabla-caja">
    <table class="tabla">
      <thead>
        <tr><th>Paciente</th><th>Documento</th><th>Contacto</th>
            <th class="num">Citas</th><th>Última atención</th><th class="num">Saldo</th></tr>
      </thead>
      <tbody>
      <?php foreach ($pacientes as $p): $r = $resumen[$p['id']]; ?>
        <tr>
          <td>
            <div class="persona">
              <span class="avatar"><?= e(iniciales($p['nombre'], $p['apellido'])) ?></span>
              <span><?= e($p['nombre'] . ' ' . $p['apellido']) ?></span>
            </div>
          </td>
          <td class="tenue"><?= e($p['tipo_documento'] . ' ' . $p['documento']) ?></td>
          <td>
            <?= e($p['email']) ?><br>
            <span class="tenue menudo"><?= e($p['telefono'] ?: 'Sin teléfono') ?></span>
          </td>
          <td class="num"><?= $r['citas'] ?></td>
          <td class="tenue"><?= $r['ultima'] ? e(fecha_corta($r['ultima'])) : '—' ?></td>
          <td class="num"><?= $r['saldo'] > 0 ? e(dinero($r['saldo'])) : '<span class="tenue">Al día</span>' ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/../includes/admin-pie.php'; ?>
