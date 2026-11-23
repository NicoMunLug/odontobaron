<?php
require_once __DIR__ . '/auth.php';

$titulo      = $titulo      ?? SITIO_NOMBRE . ' · ' . SITIO_LEMA;
$descripcion = $descripcion ?? 'Consultorio de odontología digital y estética de la Dra. Alejandra Barón en Bogotá. Agende su cita en línea.';
$u = usuario_actual();
?><!DOCTYPE html>
<html lang="es-CO">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titulo) ?></title>
<meta name="description" content="<?= e($descripcion) ?>">
<meta name="theme-color" content="#8574A8">
<meta property="og:title" content="<?= e($titulo) ?>">
<meta property="og:description" content="<?= e($descripcion) ?>">
<meta property="og:type" content="website">
<link rel="icon" href="<?= base_url('assets/img/marca/favicon.svg') ?>" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="<?= base_url('assets/css/app.css') ?>">
</head>
<body>
<a class="oculto-visual" href="#contenido">Saltar al contenido</a>

<header class="cabecera" data-cabecera>
  <div class="envoltura cabecera__caja">
    <a class="marca" href="<?= base_url('index.php') ?>" aria-label="<?= e(SITIO_NOMBRE) ?> · inicio">
      <img src="<?= base_url('assets/img/marca/logo-negro.png') ?>" alt="Alejandra Barón, odontología integral, digital y estética">
    </a>

    <button class="menu-boton" type="button" aria-expanded="false" aria-controls="menu-principal" data-menu>
      <span class="oculto-visual">Abrir menú</span>
      <svg width="20" height="14" viewBox="0 0 20 14" fill="none" aria-hidden="true">
        <path d="M0 1h20M0 7h20M0 13h20" stroke="currentColor" stroke-width="1.5"/>
      </svg>
    </button>

    <nav class="menu" id="menu-principal" aria-label="Principal">
      <a href="<?= base_url('index.php') ?>" <?= es_pagina('index.php') ? 'aria-current="page"' : '' ?>>Inicio</a>
      <a href="<?= base_url('tratamientos.php') ?>" <?= es_pagina('tratamientos.php') || es_pagina('tratamiento.php') ? 'aria-current="page"' : '' ?>>Tratamientos</a>
      <a href="<?= base_url('sede.php') ?>" <?= es_pagina('sede.php') ? 'aria-current="page"' : '' ?>>Consultorio</a>
      <a href="<?= base_url('pagos.php') ?>" <?= es_pagina('pagos.php') ? 'aria-current="page"' : '' ?>>Pagos</a>
      <a href="<?= base_url('contacto.php') ?>" <?= es_pagina('contacto.php') ? 'aria-current="page"' : '' ?>>Contacto</a>
      <?php if ($u): ?>
        <a href="<?= base_url(es_personal() ? 'admin/index.php' : 'panel/index.php') ?>">
          <?= es_personal() ? 'Panel' : 'Mis citas' ?>
        </a>
      <?php endif; ?>
    </nav>

    <div class="cabecera__acciones">
      <a class="icono-enlace" href="<?= e(instagram_url()) ?>" target="_blank" rel="noopener" aria-label="Instagram de la doctora">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <rect x="2.5" y="2.5" width="19" height="19" rx="5.5" stroke="currentColor" stroke-width="1.6"/>
          <circle cx="12" cy="12" r="4.2" stroke="currentColor" stroke-width="1.6"/>
          <circle cx="17.4" cy="6.6" r="1.2" fill="currentColor"/>
        </svg>
      </a>
      <?php if ($u): ?>
        <a class="icono-enlace" href="<?= base_url('salir.php') ?>" aria-label="Cerrar sesión" title="Cerrar sesión">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <path d="M15 4h3.5A1.5 1.5 0 0 1 20 5.5v13a1.5 1.5 0 0 1-1.5 1.5H15M10 16l-4-4 4-4M6 12h10"
                  stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          </svg>
        </a>
      <?php else: ?>
        <a class="icono-enlace" href="<?= base_url('ingresar.php') ?>" aria-label="Ingresar" title="Ingresar">
          <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true">
            <circle cx="12" cy="8.5" r="3.8" stroke="currentColor" stroke-width="1.6"/>
            <path d="M4.5 20c.9-3.6 3.9-5.6 7.5-5.6s6.6 2 7.5 5.6" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>
          </svg>
        </a>
      <?php endif; ?>
      <a class="boton" href="<?= base_url('agendar.php') ?>">Agendar cita</a>
    </div>
  </div>
</header>

<main id="contenido">
<?php foreach (flash_pendientes() as $f): ?>
  <div class="envoltura" style="padding-top:1.5rem">
    <div class="aviso aviso--<?= e($f['tipo']) ?>" role="status"><?= e($f['mensaje']) ?></div>
  </div>
<?php endforeach; ?>
