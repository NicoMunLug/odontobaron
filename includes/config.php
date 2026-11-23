<?php
/**
 * OdontoBarón · Configuración
 * -----------------------------------------------------------------
 * En Hostinger: hPanel > Bases de datos > MySQL. Copie allí el nombre
 * de la base, el usuario y la contraseña que creó, y péguelos abajo.
 */

// --- Base de datos ------------------------------------------------
define('DB_DRIVER', getenv('OB_DRIVER') ?: 'mysql');   // mysql | sqlite
define('DB_HOST',   getenv('OB_HOST')   ?: 'localhost');
define('DB_NAME',   getenv('OB_NAME')   ?: 'odontobaron');
define('DB_USER',   getenv('OB_USER')   ?: 'root');
define('DB_PASS',   getenv('OB_PASS')   ?: '');
define('DB_CHARSET', 'utf8mb4');

// Solo para pruebas locales sin servidor MySQL (DB_DRIVER = sqlite)
define('DB_SQLITE_PATH', __DIR__ . '/../storage/odontobaron.sqlite');

// --- Sitio --------------------------------------------------------
define('SITIO_NOMBRE',  'Dra. Alejandra Barón');
define('SITIO_LEMA',    'Odontología Digital y Estética');
define('SITIO_URL',     getenv('OB_URL') ?: '');   // https://midominio.com (sin barra final)

define('CONTACTO_WHATSAPP', '573123716032');
define('CONTACTO_TELEFONO', '+57 312 371 6032');
define('CONTACTO_EMAIL',    'citas@odontobaron.com');
define('CONTACTO_INSTAGRAM', 'draalejandrabaron');

// --- Reglas del consultorio --------------------------------------
define('CITA_DURACION_MIN',      45);   // minutos por bloque de agenda
define('CITA_HORAS_ANTICIPACION', 24);  // plazo mínimo para cancelar o reprogramar
define('CITA_DIAS_MAX',          60);   // hasta cuántos días adelante se puede agendar

// --- Correo -------------------------------------------------------
// Hostinger permite mail() con una cuenta del dominio.
define('MAIL_REMITENTE', 'no-responder@odontobaron.com');
define('MAIL_NOMBRE',    'Dra. Alejandra Barón');
define('MAIL_ACTIVO',    false);   // póngalo en true cuando el dominio esté configurado

// --- Entorno ------------------------------------------------------
define('APP_DEBUG', getenv('OB_DEBUG') === '1');

date_default_timezone_set('America/Bogota');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
