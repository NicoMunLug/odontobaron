<?php
/**
 * Conexión PDO. Devuelve siempre la misma instancia.
 */
require_once __DIR__ . '/config.php';

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $opciones = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        if (DB_DRIVER === 'sqlite') {
            $dir = dirname(DB_SQLITE_PATH);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $pdo = new PDO('sqlite:' . DB_SQLITE_PATH, null, null, $opciones);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $opciones);
        }
    } catch (PDOException $e) {
        if (APP_DEBUG) {
            throw $e;
        }
        pantalla_sin_conexion();
    }

    return $pdo;
}

/**
 * Pantalla de fallo de conexión.
 *
 * Cuando el sitio está recién subido y todavía no tiene credenciales, el
 * mensaje genérico no ayuda: no dice qué falta ni dónde se arregla. En ese
 * caso se muestra la guía, que no revela nada privado porque los valores
 * que nombra son los de ejemplo del repositorio. Si las credenciales ya
 * están puestas y aun así falla, el problema es otro y el mensaje vuelve a
 * ser genérico, para no exponer el nombre de la base ni el del usuario.
 */
function pantalla_sin_conexion(): void
{
    http_response_code(503);
    header('Content-Type: text/html; charset=utf-8');

    $estilo = 'font:400 16px/1.6 system-ui,-apple-system,"Segoe UI",sans-serif;'
            . 'color:#2A2235;background:#FBFAFC;margin:0;padding:2rem 1.25rem';

    if (!SIN_CONFIGURAR) {
        echo '<!doctype html><html lang="es"><meta charset="utf-8">'
           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<title>Servicio no disponible</title><body style="' . $estilo . '">'
           . '<div style="max-width:32rem;margin:3rem auto">'
           . '<h1 style="font-weight:300">No pudimos conectar con la base de datos</h1>'
           . '<p>Intente de nuevo en unos minutos. Si el problema sigue, '
           . 'escríbanos al ' . htmlspecialchars(CONTACTO_TELEFONO) . '.</p>'
           . '</div></body></html>';
        exit;
    }

    $instalador = dirname($_SERVER['SCRIPT_NAME'] ?? '/') === '/'
        ? '/instalar.php'
        : '/instalar.php';

    echo '<!doctype html><html lang="es"><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Falta configurar la base de datos</title><body style="' . $estilo . '">'
       . '<div style="max-width:34rem;margin:2rem auto">'
       . '<p style="color:#8574A8;font-size:.875rem;margin:0 0 .5rem">OdontoBarón</p>'
       . '<h1 style="font-weight:300;font-size:1.9rem;line-height:1.2;margin:0 0 1rem">'
       . 'Falta conectar la base de datos</h1>'
       . '<p>Los archivos del sitio están bien subidos y PHP funciona: este mensaje '
       . 'lo genera la propia aplicación. Lo que falta es decirle con qué base de '
       . 'datos hablar.</p>'
       . '<p style="margin:1.5rem 0">'
       . '<a href="' . htmlspecialchars($instalador) . '" '
       . 'style="background:#8574A8;color:#fff;padding:.85rem 1.5rem;border-radius:999px;'
       . 'text-decoration:none;display:inline-block">Configurar ahora</a></p>'
       . '<p style="font-size:.9375rem;color:#5A5268">También puede hacerlo a mano: '
       . 'copie <code>includes/config.local.example.php</code> como '
       . '<code>includes/config.local.php</code> y escriba allí el nombre de la base, '
       . 'el usuario y la contraseña que aparecen en hPanel, en Bases de datos.</p>'
       . '</div></body></html>';
    exit;
}

/** Ejecuta una consulta preparada y devuelve el statement. */
function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/** Primera fila, o null. */
function fila(string $sql, array $params = []): ?array
{
    $r = q($sql, $params)->fetch();
    return $r === false ? null : $r;
}

/** Todas las filas. */
function filas(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

/** Primer valor de la primera fila. */
function valor(string $sql, array $params = [], $defecto = null)
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? $defecto : $v;
}
