<?php
/**
 * Marco del panel interno. Se incluye al inicio de cada página de /admin,
 * y admin-pie.php al final.
 */
require_once __DIR__ . '/auth.php';
exigir_personal();

$u = usuario_actual();
$titulo = $titulo ?? 'Panel · ' . SITIO_NOMBRE;

$sinLeer = (int) valor('SELECT COUNT(*) FROM mensajes WHERE leido = 0', [], 0);
$hoyCount = (int) valor(
    "SELECT COUNT(*) FROM citas WHERE fecha = ? AND estado IN ('pendiente','confirmada')",
    [date('Y-m-d')], 0);

$secciones = [
    ['index.php',        'Resumen',   'M3 11.5 12 4l9 7.5M5.5 10v9.5h13V10'],
    ['citas.php',        'Citas',     'M4 6.5h16v13H4zM4 10h16M8.5 3.5v4M15.5 3.5v4'],
    ['pacientes.php',    'Pacientes', 'M12 11.5a3.7 3.7 0 1 0 0-7.4 3.7 3.7 0 0 0 0 7.4ZM4.5 20c.9-3.6 3.9-5.6 7.5-5.6s6.6 2 7.5 5.6'],
    ['mensajes.php',     'Mensajes',  'M4 6h16v12H4zM4 7l8 6 8-6'],
    ['tratamientos.php', 'Tratamientos', 'M7 4h10l-1.2 8.5a4 4 0 0 1-7.6 0Z M12 16.5V20'],
];
$aqui = basename($_SERVER['SCRIPT_NAME']);
?><!DOCTYPE html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e($titulo) ?></title>
<link rel="icon" href="<?= base_url('assets/img/marca/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css') ?>">
</head>
<body>

<div class="panel__barra">
  <img src="<?= base_url('assets/img/marca/logo-blanco.png') ?>" alt="<?= e(SITIO_NOMBRE) ?>">
  <button type="button" data-lateral aria-expanded="false" aria-controls="lateral">
    <span class="oculto-visual">Abrir menú del panel</span>
    <svg width="18" height="12" viewBox="0 0 20 14" fill="none" aria-hidden="true">
      <path d="M0 1h20M0 7h20M0 13h20" stroke="currentColor" stroke-width="1.5"/>
    </svg>
  </button>
</div>

<div class="panel">
  <aside class="lateral" id="lateral">
    <a class="lateral__marca" href="<?= base_url('index.php') ?>">
      <img src="<?= base_url('assets/img/marca/logo-blanco.png') ?>" alt="<?= e(SITIO_NOMBRE) ?>">
    </a>

    <nav aria-label="Secciones del panel">
      <?php foreach ($secciones as [$archivo, $nombre, $trazo]): ?>
        <a href="<?= base_url('admin/' . $archivo) ?>" <?= $aqui === $archivo ? 'aria-current="page"' : '' ?>>
          <svg width="17" height="17" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="<?= $trazo ?>" stroke="currentColor" stroke-width="1.6"
                  stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
          <?= e($nombre) ?>
          <?php if ($archivo === 'mensajes.php' && $sinLeer): ?>
            <span class="insignia"><?= $sinLeer ?></span>
          <?php elseif ($archivo === 'citas.php' && $hoyCount): ?>
            <span class="insignia"><?= $hoyCount ?></span>
          <?php endif; ?>
        </a>
      <?php endforeach; ?>
    </nav>

    <div class="lateral__cuenta">
      <p style="margin:0 0 .35rem;color:#fff"><?= e($u['nombre'] . ' ' . $u['apellido']) ?></p>
      <p style="margin:0 0 .75rem;font-size:.8125rem;opacity:.7"><?= e(ucfirst($u['rol'])) ?></p>
      <a href="<?= base_url('index.php') ?>">Ver el sitio</a> ·
      <a href="<?= base_url('salir.php') ?>">Salir</a>
    </div>
  </aside>

  <main class="obra">
    <?php foreach (flash_pendientes() as $f): ?>
      <div class="aviso aviso--<?= e($f['tipo']) ?>" role="status"><?= e($f['mensaje']) ?></div>
    <?php endforeach; ?>
