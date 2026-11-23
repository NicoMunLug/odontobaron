<?php
/**
 * Crea una copia de la base en SQLite para probar el sitio sin un
 * servidor MySQL. Traduce el esquema de odontobaron.sql.
 *
 *   php sql/preparar-sqlite.php
 *   OB_DRIVER=sqlite php -S localhost:8080
 *
 * En Hostinger no se usa: allí se importa odontobaron.sql en phpMyAdmin.
 */
require_once __DIR__ . '/../includes/config.php';

$origen = __DIR__ . '/odontobaron.sql';
$destino = DB_SQLITE_PATH;

if (!is_dir(dirname($destino))) {
    mkdir(dirname($destino), 0775, true);
}
if (file_exists($destino)) {
    unlink($destino);
}

$sql = file_get_contents($origen);

// --- fuera lo que SQLite no entiende --------------------------------
$sql = preg_replace('/^\s*(SET|START TRANSACTION|COMMIT).*$/mi', '', $sql);
$sql = preg_replace('/\s*ENGINE=\w+\s+DEFAULT CHARSET=\w+\s+COLLATE=\w+/i', '', $sql);
$sql = preg_replace("/\s*COMMENT\s+'(?:[^'\\\\]|\\\\.)*'/i", '', $sql);
$sql = preg_replace('/\s+ON UPDATE CURRENT_TIMESTAMP/i', '', $sql);
$sql = preg_replace('/\s+UNSIGNED/i', '', $sql);

// enum -> texto
$sql = preg_replace("/\bENUM\s*\((?:[^()']|'(?:[^'\\\\]|\\\\.)*')*\)/is", 'TEXT', $sql);

// clave primaria autoincremental
$sql = preg_replace('/`id`\s+INT\s+NOT NULL AUTO_INCREMENT/i',
                    '`id` INTEGER PRIMARY KEY AUTOINCREMENT', $sql);
$sql = preg_replace('/,\s*\n\s*PRIMARY KEY \(`id`\)/i', '', $sql);

// índices sueltos: SQLite no los declara dentro del CREATE TABLE
$sql = preg_replace('/,\s*\n\s*KEY\s+`\w+`\s*\([^)]*\)/i', '', $sql);
$sql = preg_replace('/,?\s*\n\s*UNIQUE KEY\s+`\w+`\s*(\([^)]*\))/i', ",\n  UNIQUE $1", $sql);

// funciones de fecha
$sql = preg_replace_callback(
    '/DATE_(ADD|SUB)\(CURDATE\(\),\s*INTERVAL\s+(\d+)\s+DAY\)/i',
    fn($m) => sprintf("date('now','%s%d day')", strtolower($m[1]) === 'add' ? '+' : '-', $m[2]),
    $sql
);
$sql = preg_replace('/\bCURDATE\(\)/i', "date('now')", $sql);
$sql = preg_replace('/\bCURRENT_TIMESTAMP\b/i', "CURRENT_TIMESTAMP", $sql);

// --- ejecutar --------------------------------------------------------
$pdo = new PDO('sqlite:' . $destino);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$sentencias = array_filter(array_map('trim', explode(";\n", $sql)), function ($s) {
    $s = preg_replace('/^\s*--.*$/m', '', $s);
    return trim($s) !== '';
});

$ok = 0;
foreach ($sentencias as $s) {
    $s = trim(preg_replace('/^\s*--.*$/m', '', $s));
    if ($s === '' || str_starts_with(strtoupper($s), 'DROP TABLE')) {
        continue;
    }
    try {
        $pdo->exec($s);
        $ok++;
    } catch (PDOException $e) {
        fwrite(STDERR, "Sentencia omitida: " . substr(preg_replace('/\s+/', ' ', $s), 0, 90) . "\n");
        fwrite(STDERR, "  -> " . $e->getMessage() . "\n");
    }
}

$tablas = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'")
              ->fetchAll(PDO::FETCH_COLUMN);

echo "Base de pruebas creada en: $destino\n";
echo "Sentencias aplicadas: $ok\n";
foreach ($tablas as $t) {
    $n = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
    printf("  %-20s %3d registros\n", $t, $n);
}
