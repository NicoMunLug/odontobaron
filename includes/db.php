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
        http_response_code(503);
        exit('No pudimos conectar con la base de datos. Intente de nuevo en unos minutos.');
    }

    return $pdo;
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
