<?php
/**
 * Asistente de instalación.
 *
 * Escribe includes/config.local.php con las credenciales de la base. Existe
 * para no tener que crear ese archivo a mano por FTP, que desde un teléfono
 * es incómodo.
 *
 * Se apaga solo: en cuanto config.local.php existe, esta página deja de
 * funcionar y responde 404. Aun así, conviene borrarla del servidor cuando
 * el sitio ya esté andando.
 *
 * La contraseña no se guarda en ningún registro ni se vuelve a mostrar:
 * viaja del formulario al archivo de configuración y nada más.
 */
require_once __DIR__ . '/includes/config.php';

// --- Cerrado una vez configurado -----------------------------------
if (CONFIG_LOCAL_EXISTE) {
    http_response_code(404);
    exit('<!doctype html><meta charset="utf-8"><p style="font:400 16px system-ui;padding:2rem">'
       . 'Este sitio ya está configurado. Para cambiar las credenciales, edite '
       . '<code>includes/config.local.php</code> desde el administrador de archivos.</p>');
}

session_start();
if (empty($_SESSION['instalar_csrf'])) {
    $_SESSION['instalar_csrf'] = bin2hex(random_bytes(32));
}

$error   = null;
$aviso   = null;
$listo   = false;
$datos   = [
    'host'    => $_POST['host']    ?? 'localhost',
    'nombre'  => trim($_POST['nombre']  ?? ''),
    'usuario' => trim($_POST['usuario'] ?? ''),
    'url'     => rtrim(trim($_POST['url'] ?? ''), '/'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clave = $_POST['clave'] ?? '';

    if (!hash_equals($_SESSION['instalar_csrf'], $_POST['csrf'] ?? '')) {
        $error = 'La sesión caducó. Vuelva a enviar el formulario.';
    } elseif ($datos['nombre'] === '' || $datos['usuario'] === '') {
        $error = 'El nombre de la base y el usuario son obligatorios.';
    } else {
        // Primero se prueba la conexión; solo si funciona se guarda.
        try {
            $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4',
                           $datos['host'], $datos['nombre']);
            $pdo = new PDO($dsn, $datos['usuario'], $clave, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);

            // ¿Está importado el esquema?
            $tablas = $pdo->query("SHOW TABLES LIKE 'usuarios'")->fetchAll();
            if (!$tablas) {
                $aviso = 'La conexión funciona, pero la base está vacía: todavía '
                       . 'falta importar sql/odontobaron.sql desde phpMyAdmin.';
            }

            $contenido = "<?php\n"
                . "/**\n"
                . " * Credenciales de este servidor. Generado por instalar.php.\n"
                . " * Este archivo NO va al repositorio: está en .gitignore.\n"
                . " */\n\n"
                . "return " . var_export([
                    'driver'        => 'mysql',
                    'host'          => $datos['host'],
                    'nombre'        => $datos['nombre'],
                    'usuario'       => $datos['usuario'],
                    'clave'         => $clave,
                    'url'           => $datos['url'],
                    'correo_activo' => false,
                    'depurar'       => false,
                ], true) . ";\n";

            if (@file_put_contents(CONFIG_LOCAL_RUTA, $contenido, LOCK_EX) === false) {
                $error = 'No se pudo escribir includes/config.local.php. '
                       . 'Revise los permisos de la carpeta includes (debe ser 755) '
                       . 'o cree el archivo a mano.';
            } else {
                @chmod(CONFIG_LOCAL_RUTA, 0640);
                $listo = true;
            }
        } catch (PDOException $e) {
            // El mensaje de MySQL dice si el error es de clave, de base o de host.
            $error = 'No se pudo conectar: ' . $e->getMessage();
        }
    }
}

$csrf = $_SESSION['instalar_csrf'];
?><!doctype html>
<html lang="es">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Configurar OdontoBarón</title>
<style>
  :root { --lila:#8574A8; --tinta:#2A2235; --suave:#6B6478; --linea:#E7E1F0; }
  * { box-sizing: border-box; }
  body {
    font: 400 16px/1.6 system-ui, -apple-system, "Segoe UI", sans-serif;
    color: var(--tinta); background: #FBFAFC; margin: 0; padding: 1.5rem 1.25rem 3rem;
  }
  .caja { max-width: 32rem; margin: 1rem auto; }
  h1 { font-weight: 300; font-size: 1.75rem; line-height: 1.2; margin: .25rem 0 1rem; }
  .marca { color: var(--lila); font-size: .875rem; margin: 0; }
  label { display: block; margin-bottom: 1rem; }
  label span { display: block; font-size: .875rem; margin-bottom: .35rem; }
  input {
    width: 100%; padding: .75rem .9rem; font: inherit;
    border: 1px solid var(--linea); border-radius: .5rem; background: #fff;
  }
  input:focus { outline: 2px solid var(--lila); outline-offset: 1px; }
  small { color: var(--suave); font-size: .8125rem; display: block; margin-top: .3rem; }
  button {
    width: 100%; padding: .9rem; font: inherit; font-weight: 500;
    background: var(--lila); color: #fff; border: 0; border-radius: 999px; cursor: pointer;
  }
  .ficha { background: #fff; border: 1px solid var(--linea); border-radius: 1rem; padding: 1.5rem; }
  .error, .ok, .nota {
    padding: .9rem 1rem; border-radius: .6rem; margin-bottom: 1.25rem; font-size: .9375rem;
  }
  .error { background: #FBF2F3; color: #A84050; }
  .ok    { background: #EFF6F1; color: #3F7D5B; }
  .nota  { background: #FDF3E8; color: #96601F; }
  code { background: #F2EEF8; padding: .1rem .35rem; border-radius: .25rem; font-size: .9em; }
</style>
<div class="caja">
  <p class="marca">OdontoBarón</p>

<?php if ($listo): ?>
  <h1>Listo</h1>
  <div class="ok">Credenciales guardadas en <code>includes/config.local.php</code>.</div>
  <?php if ($aviso): ?><div class="nota"><?= htmlspecialchars($aviso) ?></div><?php endif; ?>
  <p>El sitio ya puede conectarse a la base de datos.</p>
  <p><a href="index.php">Abrir el sitio</a></p>
  <p><small>Por seguridad, borre <code>instalar.php</code> del servidor ahora que
  terminó. Mientras exista no hace daño, porque se apaga sola al encontrar el
  archivo de configuración, pero sobra.</small></p>

<?php else: ?>
  <h1>Configurar la base de datos</h1>
  <p>Estos datos están en hPanel, en <strong>Bases de datos → MySQL</strong>.
     El nombre de la base y el del usuario empiezan por el número de su cuenta.</p>

  <?php if ($error): ?><div class="error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

  <form method="post" class="ficha">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrf) ?>">

    <label><span>Servidor</span>
      <input name="host" value="<?= htmlspecialchars($datos['host']) ?>" required>
      <small>En Hostinger es <code>localhost</code>.</small>
    </label>

    <label><span>Nombre de la base</span>
      <input name="nombre" value="<?= htmlspecialchars($datos['nombre']) ?>"
             placeholder="u000000000_odontobaron" required autocapitalize="off" spellcheck="false">
    </label>

    <label><span>Usuario</span>
      <input name="usuario" value="<?= htmlspecialchars($datos['usuario']) ?>"
             placeholder="u000000000_admin" required autocapitalize="off" spellcheck="false">
    </label>

    <label><span>Contraseña del usuario de la base</span>
      <input type="password" name="clave" autocomplete="off">
      <small>No se guarda en ningún registro ni se vuelve a mostrar: se escribe
      en el archivo de configuración y nada más.</small>
    </label>

    <label><span>Dirección del sitio</span>
      <input name="url" value="<?= htmlspecialchars($datos['url']) ?>"
             placeholder="https://sudominio.com" autocapitalize="off" spellcheck="false">
      <small>Sin barra al final. Se usa en los enlaces de los correos.</small>
    </label>

    <button type="submit">Probar y guardar</button>
  </form>

  <p><small>La conexión se prueba antes de guardar. Si los datos están mal, no se
  escribe nada y aquí aparece el motivo que devuelve MySQL.</small></p>
<?php endif; ?>
</div>
</html>
