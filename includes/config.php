<?php
/**
 * OdontoBarón · Configuración
 * -----------------------------------------------------------------
 * ESTE ARCHIVO NO LLEVA CONTRASEÑAS. Va en el repositorio, así que
 * cualquiera que vea el código vería lo que esté escrito aquí.
 *
 * Las credenciales del servidor se escriben en includes/config.local.php,
 * que git ignora. Copie includes/config.local.example.php con ese nombre
 * y ponga allí los datos de hPanel > Bases de datos > MySQL.
 */

/** Valores del servidor, si existe el archivo local. */
$local = [];
define('CONFIG_LOCAL_RUTA', __DIR__ . '/config.local.php');
define('CONFIG_LOCAL_EXISTE', is_file(CONFIG_LOCAL_RUTA));
define('CONFIG_EJEMPLO_RUTA', __DIR__ . '/config.local.example.php');

/**
 * Error fácil de cometer: escribir las credenciales en la plantilla en vez
 * de en la copia. La plantilla se llama config.local.example.php y el sitio
 * no la lee nunca; si todavía trae el nombre de base de ejemplo está sin
 * tocar, y si no, alguien escribió ahí pensando que era el archivo bueno.
 */
define('CONFIG_EJEMPLO_EDITADO', !CONFIG_LOCAL_EXISTE
    && is_file(CONFIG_EJEMPLO_RUTA)
    && strpos((string) @file_get_contents(CONFIG_EJEMPLO_RUTA), 'u000000000_odontobaron') === false);

if (CONFIG_LOCAL_EXISTE) {
    $local = require CONFIG_LOCAL_RUTA;
    if (!is_array($local)) {
        $local = [];
    }
}

/**
 * Orden de prioridad: archivo local > variable de entorno > valor por defecto.
 * Así el mismo código corre en Hostinger, en local y en las pruebas.
 */
function cfg(array $local, string $clave, string $entorno, $defecto)
{
    if (array_key_exists($clave, $local) && $local[$clave] !== '') {
        return $local[$clave];
    }
    $v = getenv($entorno);
    return ($v === false || $v === '') ? $defecto : $v;
}

// --- Base de datos ------------------------------------------------
define('DB_DRIVER', cfg($local, 'driver', 'OB_DRIVER', 'mysql'));   // mysql | sqlite
define('DB_HOST',   cfg($local, 'host',   'OB_HOST',   'localhost'));
define('DB_NAME',   cfg($local, 'nombre', 'OB_NAME',   'odontobaron'));
define('DB_USER',   cfg($local, 'usuario', 'OB_USER',  'root'));
define('DB_PASS',   cfg($local, 'clave',  'OB_PASS',   ''));
define('DB_CHARSET', 'utf8mb4');

/**
 * El sitio está subido pero todavía sin credenciales: config.local.php no
 * existe y la configuración sigue en los valores de ejemplo. Se usa para
 * mostrar la guía de instalación en vez de un error sin explicación.
 */
define('SIN_CONFIGURAR', !CONFIG_LOCAL_EXISTE
    && DB_DRIVER === 'mysql'
    && DB_NAME === 'odontobaron'
    && DB_USER === 'root'
    && DB_PASS === '');

// Solo para pruebas locales sin servidor MySQL (DB_DRIVER = sqlite)
define('DB_SQLITE_PATH', __DIR__ . '/../storage/odontobaron.sqlite');

// --- Sitio --------------------------------------------------------
define('SITIO_NOMBRE',  'Dra. Alejandra Barón');
define('SITIO_LEMA',    'Odontología Digital y Estética');
define('SITIO_URL',     cfg($local, 'url', 'OB_URL', ''));   // https://midominio.com (sin barra final)

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
define('MAIL_ACTIVO',    (bool) cfg($local, 'correo_activo', 'OB_MAIL', false));

// --- Entorno ------------------------------------------------------
// Póngalo en true solo mientras busca un error: muestra rutas y consultas.
define('APP_DEBUG', (bool) cfg($local, 'depurar', 'OB_DEBUG', false) === true
                    || cfg($local, 'depurar', 'OB_DEBUG', '0') === '1');

date_default_timezone_set('America/Bogota');

if (APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
    ini_set('display_errors', '0');
}
