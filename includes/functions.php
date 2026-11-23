<?php
/**
 * Utilidades compartidas: escape, URLs, fechas, dinero, correo, CSRF.
 */
require_once __DIR__ . '/db.php';

// --- Salida segura ------------------------------------------------
function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Convierte saltos de línea en párrafos. */
/**
 * Normaliza los saltos de línea de un campo de texto.
 *
 * MySQL interpreta la secuencia \n dentro de una cadena, pero SQLite la
 * guarda tal cual. Sin esto, el mismo registro se ve con párrafos en el
 * servidor y con "\n" a la vista en las pruebas locales, y las listas
 * de "el tratamiento incluye" quedan en un solo renglón.
 */
function saltos(?string $texto): string
{
    $t = str_replace(["\r\n", "\r"], "\n", (string) $texto);
    return str_replace(['\\r\\n', '\\n', '\\r'], "\n", $t);
}

function parrafos(?string $texto): string
{
    $bloques = preg_split('/\n\s*\n/', trim(saltos($texto)));
    return implode('', array_map(fn($b) => '<p>' . nl2br(e(trim($b))) . '</p>', $bloques));
}

/** Convierte un texto de una línea por ítem en una lista. */
function lineas(?string $texto): array
{
    $t = saltos($texto);
    if (trim($t) === '') {
        return [];
    }
    return array_values(array_filter(array_map('trim', explode("\n", $t)), 'strlen'));
}

// --- Rutas --------------------------------------------------------
function base_url(string $ruta = ''): string
{
    $base = SITIO_URL !== '' ? SITIO_URL : '';
    if ($base === '') {
        $dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        // dentro de /admin o /panel hay que subir un nivel
        if (preg_match('#/(admin|panel)$#', $dir)) {
            $dir = dirname($dir);
        }
        $base = rtrim($dir, '/');
    }
    return $base . '/' . ltrim($ruta, '/');
}

function url_actual(): string
{
    return strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
}

function es_pagina(string $archivo): bool
{
    return basename($_SERVER['SCRIPT_NAME'] ?? '') === $archivo;
}

function redirigir(string $ruta): never
{
    header('Location: ' . (str_starts_with($ruta, 'http') ? $ruta : base_url($ruta)));
    exit;
}

// --- Mensajes entre páginas --------------------------------------
function flash(string $tipo, string $mensaje): void
{
    sesion_iniciar();
    $_SESSION['flash'][] = ['tipo' => $tipo, 'mensaje' => $mensaje];
}

function flash_pendientes(): array
{
    sesion_iniciar();
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

// --- Sesión y CSRF ------------------------------------------------
function sesion_iniciar(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure'   => !empty($_SERVER['HTTPS']),
        ]);
        session_start();
    }
}

function csrf_token(): string
{
    sesion_iniciar();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="_token" value="' . e(csrf_token()) . '">';
}

function csrf_valido(): bool
{
    sesion_iniciar();
    $enviado = $_POST['_token'] ?? '';
    return is_string($enviado) && !empty($_SESSION['csrf'])
        && hash_equals($_SESSION['csrf'], $enviado);
}

function exigir_csrf(): void
{
    if (!csrf_valido()) {
        http_response_code(419);
        exit('La sesión expiró. Vuelva a la página anterior y envíe el formulario de nuevo.');
    }
}

// --- Formato ------------------------------------------------------
function dinero(?float $valor): string
{
    if ($valor === null) {
        return '—';
    }
    return '$' . number_format($valor, 0, ',', '.');
}

const MESES = [1 => 'enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio',
    'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
const DIAS = [1 => 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado', 'domingo'];

function fecha_larga(string $fecha): string
{
    $t = strtotime($fecha);
    return DIAS[(int) date('N', $t)] . ' ' . date('j', $t) . ' de ' . MESES[(int) date('n', $t)];
}

function fecha_corta(string $fecha): string
{
    $t = strtotime($fecha);
    return date('j', $t) . ' ' . mes_abrev($fecha) . ' ' . date('Y', $t);
}

/** Abreviatura del mes, segura con acentos. */
function mes_abrev(string $fecha): string
{
    return mb_substr(MESES[(int) date('n', strtotime($fecha))], 0, 3, 'UTF-8');
}

/** Abreviatura del día de la semana, segura con acentos. */
function dia_abrev(string $fecha): string
{
    return mb_substr(DIAS[(int) date('N', strtotime($fecha))], 0, 3, 'UTF-8');
}

function hora_legible(string $hora): string
{
    return date('g:i a', strtotime($hora));
}

function iniciales(string $nombre, string $apellido = ''): string
{
    return mb_strtoupper(mb_substr($nombre, 0, 1) . mb_substr($apellido, 0, 1));
}

// --- Dominio ------------------------------------------------------
function whatsapp_url(string $mensaje = ''): string
{
    $m = $mensaje !== '' ? '?text=' . rawurlencode($mensaje) : '';
    return 'https://wa.me/' . CONTACTO_WHATSAPP . $m;
}

function instagram_url(): string
{
    return 'https://www.instagram.com/' . CONTACTO_INSTAGRAM . '/';
}

function estado_etiqueta(string $estado): string
{
    return [
        'pendiente'   => 'Por confirmar',
        'confirmada'  => 'Confirmada',
        'atendida'    => 'Atendida',
        'cancelada'   => 'Cancelada',
        'no_asistio'  => 'No asistió',
    ][$estado] ?? $estado;
}

function tratamientos_activos(): array
{
    static $cache = null;
    return $cache ??= filas('SELECT * FROM tratamientos WHERE activo = 1 ORDER BY orden, nombre');
}

function tratamiento_por_slug(string $slug): ?array
{
    return fila('SELECT * FROM tratamientos WHERE slug = ? AND activo = 1', [$slug]);
}

function sede_principal(): ?array
{
    return fila('SELECT * FROM sedes WHERE activo = 1 ORDER BY id LIMIT 1');
}

/** Código legible para una cita nueva. */
function nuevo_codigo_cita(): string
{
    $n = (int) valor('SELECT COUNT(*) FROM citas') + 1;
    return sprintf('OB-%s-%04d', date('Y'), $n);
}

// --- Correo -------------------------------------------------------
function enviar_correo(string $para, string $asunto, string $cuerpoHtml): bool
{
    if (!MAIL_ACTIVO) {
        return false;   // en desarrollo no se envía nada
    }
    $cabeceras = implode("\r\n", [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: ' . MAIL_NOMBRE . ' <' . MAIL_REMITENTE . '>',
        'Reply-To: ' . CONTACTO_EMAIL,
    ]);
    return @mail($para, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpoHtml, $cabeceras);
}

/** Plantilla mínima para los correos del consultorio. */
function plantilla_correo(string $titulo, string $contenidoHtml): string
{
    return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;
        padding:32px;color:#2A2235;background:#FBFAFC">
        <h1 style="font-size:20px;font-weight:500;color:#8574A8;margin:0 0 16px">' . e($titulo) . '</h1>'
        . $contenidoHtml .
        '<p style="margin-top:28px;font-size:13px;color:#6B6478">
            ' . e(SITIO_NOMBRE) . ' · ' . e(CONTACTO_TELEFONO) . '
         </p></div>';
}
