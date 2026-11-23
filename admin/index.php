<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/citas.php';

$titulo = 'Resumen · Panel';
require __DIR__ . '/../includes/admin-layout.php';

$hoy        = date('Y-m-d');
$mesInicio  = date('Y-m-01');
$mesFin     = date('Y-m-t');
$mesAntIni  = date('Y-m-01', strtotime('-1 month'));
$mesAntFin  = date('Y-m-t', strtotime('-1 month'));

$cuenta = fn(string $extra, array $par) =>
    (int) valor("SELECT COUNT(*) FROM citas WHERE $extra", $par, 0);

$citasMes    = $cuenta('fecha BETWEEN ? AND ?', [$mesInicio, $mesFin]);
$citasMesAnt = $cuenta('fecha BETWEEN ? AND ?', [$mesAntIni, $mesAntFin]);
$citasHoy    = $cuenta("fecha = ? AND estado IN ('pendiente','confirmada')", [$hoy]);
$porConfirmar= $cuenta("estado = 'pendiente' AND fecha >= ?", [$hoy]);

// tasa de ausencias del mes: no asistió / (atendidas + no asistió)
$noAsistio = $cuenta("fecha BETWEEN ? AND ? AND estado = 'no_asistio'", [$mesInicio, $mesFin]);
$atendidas = $cuenta("fecha BETWEEN ? AND ? AND estado = 'atendida'", [$mesInicio, $mesFin]);
$baseAus   = $noAsistio + $atendidas;
$tasaAus   = $baseAus ? round($noAsistio / $baseAus * 100) : 0;

$ingresosMes = (float) valor(
    "SELECT COALESCE(SUM(valor),0) FROM pagos WHERE estado = 'aprobado' AND creado_en BETWEEN ? AND ?",
    [$mesInicio . ' 00:00:00', $mesFin . ' 23:59:59'], 0);

$pacientes = (int) valor("SELECT COUNT(*) FROM usuarios WHERE rol = 'paciente' AND activo = 1", [], 0);

// --- citas de los últimos 6 meses ---------------------------------
$serie = [];
for ($i = 5; $i >= 0; $i--) {
    $ini = date('Y-m-01', strtotime("-$i month"));
    $fin = date('Y-m-t', strtotime("-$i month"));
    $serie[] = [
        'etiqueta' => mes_abrev($ini),
        'valor'    => $cuenta('fecha BETWEEN ? AND ?', [$ini, $fin]),
    ];
}
$maxSerie = max(1, max(array_column($serie, 'valor')));

// --- citas por tratamiento (90 días) -------------------------------
$desde90 = date('Y-m-d', strtotime('-90 days'));
$porTratamiento = filas(
    'SELECT t.nombre, COUNT(c.id) AS total
     FROM tratamientos t LEFT JOIN citas c
       ON c.tratamiento_id = t.id AND c.fecha >= ?
     GROUP BY t.id, t.nombre
     ORDER BY total DESC, t.nombre', [$desde90]);
$maxTrat = max(1, (int) ($porTratamiento[0]['total'] ?? 1));

// --- agenda de hoy --------------------------------------------------
$agendaHoy = filas(
    "SELECT c.*, t.nombre AS tratamiento, u.nombre AS pn, u.apellido AS pa, u.telefono
     FROM citas c
     JOIN tratamientos t ON t.id = c.tratamiento_id
     JOIN usuarios u     ON u.id = c.paciente_id
     WHERE c.fecha = ? AND c.estado IN ('pendiente','confirmada')
     ORDER BY c.hora", [$hoy]);

$ultimosMensajes = filas('SELECT * FROM mensajes ORDER BY creado_en DESC LIMIT 4');

$variacion = $citasMesAnt > 0 ? round(($citasMes - $citasMesAnt) / $citasMesAnt * 100) : null;
?>

<div class="obra__cabeza">
  <div>
    <h1>Resumen</h1>
    <p><?= e(ucfirst(MESES[(int) date('n')])) ?> de <?= date('Y') ?> · actualizado ahora</p>
  </div>
  <a class="boton boton--menudo" href="<?= base_url('admin/citas.php') ?>">Ver todas las citas</a>
</div>

<div class="indicadores">
  <div class="indicador">
    <div class="indicador__rotulo">Citas este mes</div>
    <div class="indicador__cifra"><?= $citasMes ?></div>
    <?php if ($variacion !== null): ?>
      <div class="indicador__nota <?= $variacion >= 0 ? 'indicador__nota--sube' : 'indicador__nota--baja' ?>">
        <?= $variacion >= 0 ? '+' : '' ?><?= $variacion ?> % frente al mes pasado
      </div>
    <?php else: ?>
      <div class="indicador__nota">Sin dato del mes anterior</div>
    <?php endif; ?>
  </div>

  <div class="indicador">
    <div class="indicador__rotulo">Citas de hoy</div>
    <div class="indicador__cifra"><?= $citasHoy ?></div>
    <div class="indicador__nota"><?= $porConfirmar ?> por confirmar en total</div>
  </div>

  <div class="indicador">
    <div class="indicador__rotulo">Tasa de ausencias</div>
    <div class="indicador__cifra"><?= $tasaAus ?> %</div>
    <div class="indicador__nota">
      <?= $noAsistio ?> de <?= $baseAus ?: 0 ?> citas del mes
    </div>
  </div>

  <div class="indicador">
    <div class="indicador__rotulo">Pagos del mes</div>
    <div class="indicador__cifra" style="font-size:1.6rem"><?= e(dinero($ingresosMes)) ?></div>
    <div class="indicador__nota">Registrados y aprobados</div>
  </div>

  <div class="indicador">
    <div class="indicador__rotulo">Pacientes activos</div>
    <div class="indicador__cifra"><?= $pacientes ?></div>
    <div class="indicador__nota">Con cuenta en el sistema</div>
  </div>
</div>

<div class="rejilla-2">
  <section class="bloque">
    <div class="bloque__cabeza">
      <h2>Citas por mes</h2>
      <p>Últimos seis meses</p>
    </div>

    <?php
    $ancho = 640; $alto = 220; $margenI = 28; $margenD = 8; $margenS = 18; $margenB = 28;
    $util = $ancho - $margenI - $margenD;
    $paso = $util / count($serie);
    $anchoBarra = min(54, $paso * .56);
    ?>
    <svg class="grafico" viewBox="0 0 <?= $ancho ?> <?= $alto ?>" role="img"
         aria-label="Citas por mes en los últimos seis meses">
      <?php for ($g = 0; $g <= 3; $g++):
          $y = $margenS + ($alto - $margenS - $margenB) * $g / 3;
          $v = round($maxSerie * (1 - $g / 3)); ?>
        <line class="guia" x1="<?= $margenI ?>" y1="<?= $y ?>" x2="<?= $ancho - $margenD ?>" y2="<?= $y ?>"/>
        <text x="0" y="<?= $y + 3.5 ?>"><?= $v ?></text>
      <?php endfor; ?>

      <?php foreach ($serie as $i => $s):
          $h = ($alto - $margenS - $margenB) * ($s['valor'] / $maxSerie);
          $x = $margenI + $paso * $i + ($paso - $anchoBarra) / 2;
          $y = $alto - $margenB - $h; ?>
        <g>
          <rect class="barra" x="<?= round($x, 1) ?>" y="<?= round($h > 0 ? $y : $alto - $margenB - 2, 1) ?>"
                width="<?= round($anchoBarra, 1) ?>" height="<?= round(max($h, 2), 1) ?>" rx="4">
            <title><?= e($s['etiqueta']) ?>: <?= $s['valor'] ?> citas</title>
          </rect>
          <?php if ($s['valor'] > 0): ?>
            <text class="valor" x="<?= round($x + $anchoBarra / 2, 1) ?>" y="<?= round($y - 6, 1) ?>"
                  text-anchor="middle"><?= $s['valor'] ?></text>
          <?php endif; ?>
          <text x="<?= round($x + $anchoBarra / 2, 1) ?>" y="<?= $alto - 8 ?>" text-anchor="middle">
            <?= e($s['etiqueta']) ?>
          </text>
        </g>
      <?php endforeach; ?>
      <line class="eje" x1="<?= $margenI ?>" y1="<?= $alto - $margenB ?>"
            x2="<?= $ancho - $margenD ?>" y2="<?= $alto - $margenB ?>"/>
    </svg>
  </section>

  <section class="bloque">
    <div class="bloque__cabeza">
      <h2>Tratamientos más pedidos</h2>
      <p>Últimos 90 días</p>
    </div>
    <div class="barras-rotulo">
      <?php foreach ($porTratamiento as $t): ?>
        <div class="barra-fila">
          <span><?= e($t['nombre']) ?></span>
          <span class="barra-pista">
            <span class="barra-trazo" style="width:<?= max(2, round($t['total'] / $maxTrat * 100)) ?>%"></span>
          </span>
          <span><?= (int) $t['total'] ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </section>
</div>

<div class="rejilla-2" style="margin-top:1.25rem">
  <section class="bloque">
    <div class="bloque__cabeza">
      <h2>Agenda de hoy</h2>
      <p><?= e(fecha_larga($hoy)) ?></p>
    </div>
    <?php if (!$agendaHoy): ?>
      <div class="vacio" style="padding:2rem 1rem">
        <p style="margin:0">No hay citas para hoy.</p>
      </div>
    <?php else: ?>
      <div class="agenda">
        <?php foreach ($agendaHoy as $c): ?>
          <div class="agenda__hora">
            <strong><?= e(hora_legible($c['hora'])) ?></strong>
            <div>
              <p><?= e($c['pn'] . ' ' . $c['pa']) ?></p>
              <p class="tenue"><?= e($c['tratamiento']) ?><?= $c['telefono'] ? ' · ' . e($c['telefono']) : '' ?></p>
            </div>
            <span class="marca-estado estado-<?= e($c['estado']) ?>"><?= e(estado_etiqueta($c['estado'])) ?></span>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>

  <section class="bloque">
    <div class="bloque__cabeza">
      <h2>Mensajes recientes</h2>
      <a class="enlace menudo" href="<?= base_url('admin/mensajes.php') ?>">Ver todos</a>
    </div>
    <?php if (!$ultimosMensajes): ?>
      <div class="vacio" style="padding:2rem 1rem"><p style="margin:0">Sin mensajes.</p></div>
    <?php else: ?>
      <div style="display:grid;gap:.75rem">
        <?php foreach ($ultimosMensajes as $m): ?>
          <a href="<?= base_url('admin/mensajes.php#m' . (int) $m['id']) ?>"
             style="text-decoration:none;color:inherit;padding:.75rem .9rem;border:1px solid var(--linea);border-radius:var(--radio-s);display:block">
            <div class="fila fila--entre" style="gap:.5rem">
              <strong style="font-size:.9375rem;font-weight:500"><?= e($m['asunto']) ?></strong>
              <?php if (!$m['leido']): ?><span class="insignia" style="margin:0">nuevo</span><?php endif; ?>
            </div>
            <p class="tenue menudo" style="margin:.2rem 0 0">
              <?= e($m['nombre']) ?> · <?= e(fecha_corta(substr($m['creado_en'], 0, 10))) ?>
            </p>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </section>
</div>

<?php require __DIR__ . '/../includes/admin-pie.php'; ?>
