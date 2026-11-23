<?php
require_once __DIR__ . '/includes/auth.php';
cerrar_sesion();
flash('exito', 'Cerró su sesión.');
redirigir('index.php');
