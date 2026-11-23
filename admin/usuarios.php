<?php
/**
 * Gestión de cuentas · solo para la administración del consultorio.
 *
 * Permite crear usuarios de cualquier rol, restablecer contraseñas y
 * activar o desactivar cuentas. Las contraseñas nunca se leen: cuando
 * se restablece una, el sistema genera una provisional, la cifra y la
 * muestra una sola vez para que el consultorio se la entregue a la
 * persona.
 */
require_once __DIR__ . '/../includes/claves.php';
exigir_admin();

$titulo = 'Usuarios · Panel';

$ROLES = [
    'paciente'   => 'Paciente',
    'odontologo' => 'Odontóloga u odontólogo',
    'admin'      => 'Administración',
];

$yo = usuario_actual();

// --- Acciones ------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    exigir_csrf();
    $accion = $_POST['accion'] ?? '';

    // ---------------------------------------------------- crear cuenta
    if ($accion === 'crear') {
        $datos = [
            'nombre'    => trim($_POST['nombre'] ?? ''),
            'apellido'  => trim($_POST['apellido'] ?? ''),
            'email'     => mb_strtolower(trim($_POST['email'] ?? '')),
            'telefono'  => trim($_POST['telefono'] ?? ''),
            'tipo_doc'  => $_POST['tipo_documento'] ?? 'CC',
            'documento' => trim($_POST['documento'] ?? ''),
            'rol'       => $_POST['rol'] ?? 'paciente',
        ];

        $error = null;
        if ($datos['nombre'] === '' || $datos['apellido'] === '' || $datos['documento'] === '') {
            $error = 'Nombre, apellido y documento son obligatorios.';
        } elseif (!filter_var($datos['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Ese correo no tiene un formato válido.';
        } elseif (!isset($ROLES[$datos['rol']])) {
            $error = 'Rol no reconocido.';
        } elseif (valor('SELECT id FROM usuarios WHERE email = ?', [$datos['email']])) {
            $error = 'Ya existe una cuenta con ese correo.';
        } elseif (valor('SELECT id FROM usuarios WHERE tipo_documento = ? AND documento = ?',
                        [$datos['tipo_doc'], $datos['documento']])) {
            $error = 'Ya existe una cuenta con ese documento.';
        }

        if ($error) {
            flash('error', $error);
        } else {
            $provisional = clave_provisional();
            q('INSERT INTO usuarios
                 (nombre, apellido, email, telefono, tipo_documento, documento,
                  password_hash, rol, email_verificado, activo)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, 1)', [
                $datos['nombre'], $datos['apellido'], $datos['email'],
                $datos['telefono'] ?: null, $datos['tipo_doc'], $datos['documento'],
                password_hash($provisional, PASSWORD_DEFAULT), $datos['rol'],
            ]);

            flash('exito', 'Cuenta creada para ' . $datos['nombre'] . ' ' . $datos['apellido'] .
                           '. Contraseña provisional: ' . $provisional .
                           ' — entréguesela y pídale que la cambie al entrar.');
        }
        redirigir('admin/usuarios.php');
    }

    // ------------------------------------------ restablecer contraseña
    if ($accion === 'restablecer') {
        $id = (int) ($_POST['id'] ?? 0);
        $u = fila('SELECT * FROM usuarios WHERE id = ?', [$id]);

        if (!$u) {
            flash('error', 'No encontramos esa cuenta.');
        } elseif (($_POST['modo'] ?? '') === 'correo') {
            $enlace = crear_enlace_restablecimiento($u['email']);
            if ($enlace && correo_restablecimiento($u, $enlace)) {
                flash('exito', 'Le enviamos el enlace a ' . $u['email'] . '. Vence en una hora.');
            } elseif ($enlace && APP_DEBUG) {
                flash('aviso', 'El envío de correo está apagado. Enlace de prueba: ' . $enlace);
            } else {
                flash('error', 'No se pudo enviar el correo. Use la contraseña provisional.');
            }
        } else {
            $provisional = clave_provisional();
            cambiar_clave((int) $u['id'], $provisional);
            flash('exito', 'Contraseña nueva de ' . $u['nombre'] . ' ' . $u['apellido'] .
                           ': ' . $provisional . ' — se la entrega y la cambia al entrar.');
        }
        redirigir('admin/usuarios.php');
    }

    // ------------------------------------------------ activar/desactivar
    if ($accion === 'estado') {
        $id = (int) ($_POST['id'] ?? 0);
        if ($id === (int) $yo['id']) {
            flash('error', 'No puede desactivar su propia cuenta.');
        } else {
            $u = fila('SELECT id, nombre, apellido, activo FROM usuarios WHERE id = ?', [$id]);
            if ($u) {
                $nuevo = (int) $u['activo'] === 1 ? 0 : 1;
                q('UPDATE usuarios SET activo = ? WHERE id = ?', [$nuevo, $id]);
                flash('exito', ($nuevo ? 'Reactivamos' : 'Desactivamos') . ' la cuenta de ' .
                               $u['nombre'] . ' ' . $u['apellido'] . '.');
            }
        }
        redirigir('admin/usuarios.php');
    }
}

// --- Listado -------------------------------------------------------
$q = trim($_GET['q'] ?? '');
$filtroRol = $_GET['rol'] ?? '';

$where = '1 = 1';
$par = [];
if ($q !== '') {
    $where .= ' AND (nombre LIKE ? OR apellido LIKE ? OR email LIKE ? OR documento LIKE ?)';
    array_push($par, "%$q%", "%$q%", "%$q%", "%$q%");
}
if (isset($ROLES[$filtroRol])) {
    $where .= ' AND rol = ?';
    $par[] = $filtroRol;
}

$usuarios = filas("SELECT * FROM usuarios WHERE $where
                   ORDER BY CASE rol WHEN 'admin' THEN 1 WHEN 'odontologo' THEN 2 ELSE 3 END,
                            apellido, nombre
                   LIMIT 300", $par);

$conteo = [];
foreach ($ROLES as $clave => $_) {
    $conteo[$clave] = (int) valor('SELECT COUNT(*) FROM usuarios WHERE rol = ?', [$clave], 0);
}

require __DIR__ . '/../includes/admin-layout.php';
?>
<div class="obra__cabeza">
  <div>
    <h1>Usuarios</h1>
    <p>
      <?= $conteo['paciente'] ?> pacientes ·
      <?= $conteo['odontologo'] ?> en el equipo clínico ·
      <?= $conteo['admin'] ?> en administración
    </p>
  </div>
  <button class="boton boton--menudo" type="button" data-abre="nuevo-usuario">Crear usuario</button>
</div>

<section class="bloque" id="nuevo-usuario" hidden>
  <div class="bloque__cabeza"><h2>Crear una cuenta</h2></div>
  <form method="post" class="rejilla-campos">
    <?= csrf_campo() ?>
    <input type="hidden" name="accion" value="crear">

    <label class="campo"><span>Nombre</span>
      <input type="text" name="nombre" required maxlength="60"></label>
    <label class="campo"><span>Apellido</span>
      <input type="text" name="apellido" required maxlength="60"></label>
    <label class="campo"><span>Correo</span>
      <input type="email" name="email" required maxlength="120"></label>
    <label class="campo"><span>Teléfono</span>
      <input type="tel" name="telefono" maxlength="25" placeholder="+57 300 000 0000"></label>

    <label class="campo"><span>Tipo de documento</span>
      <select name="tipo_documento">
        <option value="CC">Cédula de ciudadanía</option>
        <option value="TI">Tarjeta de identidad</option>
        <option value="CE">Cédula de extranjería</option>
        <option value="PA">Pasaporte</option>
      </select></label>
    <label class="campo"><span>Número de documento</span>
      <input type="text" name="documento" required maxlength="25"></label>

    <label class="campo"><span>Rol</span>
      <select name="rol">
        <?php foreach ($ROLES as $clave => $nombre): ?>
          <option value="<?= e($clave) ?>"><?= e($nombre) ?></option>
        <?php endforeach; ?>
      </select></label>

    <div class="campo" style="align-self:end">
      <button class="boton boton--ancho" type="submit">Crear la cuenta</button>
    </div>

    <p class="menudo tenue" style="grid-column:1/-1;margin:0">
      El sistema genera una contraseña provisional y la muestra una sola vez,
      al guardar. Anótela y entréguesela a la persona: después no se puede
      volver a consultar, solo restablecer.
    </p>
  </form>
</section>

<form class="filtros" method="get">
  <input class="crece" type="search" name="q" value="<?= e($q) ?>"
         placeholder="Buscar por nombre, correo o documento">
  <select name="rol">
    <option value="">Todos los roles</option>
    <?php foreach ($ROLES as $clave => $nombre): ?>
      <option value="<?= e($clave) ?>" <?= $filtroRol === $clave ? 'selected' : '' ?>><?= e($nombre) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="boton boton--menudo" type="submit">Filtrar</button>
  <?php if ($q || $filtroRol): ?>
    <a class="enlace menudo" href="<?= base_url('admin/usuarios.php') ?>">Limpiar</a>
  <?php endif; ?>
</form>

<section class="bloque">
  <?php if (!$usuarios): ?>
    <div class="vacio" style="padding:2.5rem 1rem">
      <h3>Sin resultados</h3><p>Pruebe con otro término.</p>
    </div>
  <?php else: ?>
  <div class="tabla-caja">
    <table class="tabla">
      <thead>
        <tr><th>Persona</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acciones</th></tr>
      </thead>
      <tbody>
      <?php foreach ($usuarios as $u): $esYo = (int) $u['id'] === (int) $yo['id']; ?>
        <tr>
          <td>
            <div class="persona">
              <span class="avatar"><?= e(iniciales($u['nombre'], $u['apellido'])) ?></span>
              <span>
                <?= e($u['nombre'] . ' ' . $u['apellido']) ?>
                <?php if ($esYo): ?><span class="tenue menudo">(usted)</span><?php endif; ?>
                <br><span class="tenue menudo"><?= e($u['tipo_documento'] . ' ' . $u['documento']) ?></span>
              </span>
            </div>
          </td>
          <td class="tenue"><?= e($u['email']) ?></td>
          <td><?= e($ROLES[$u['rol']] ?? $u['rol']) ?></td>
          <td>
            <span class="marca-estado <?= (int) $u['activo'] === 1 ? 'estado-confirmada' : 'estado-cancelada' ?>">
              <?= (int) $u['activo'] === 1 ? 'Activa' : 'Desactivada' ?>
            </span>
          </td>
          <td>
            <div class="fila fila--menuda">
              <form method="post" onsubmit="return confirm('¿Generar una contraseña provisional para <?= e(addslashes($u['nombre'])) ?>?')">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="restablecer">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="modo" value="provisional">
                <button class="boton boton--menudo boton--contorno" type="submit">Contraseña provisional</button>
              </form>

              <form method="post">
                <?= csrf_campo() ?>
                <input type="hidden" name="accion" value="restablecer">
                <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                <input type="hidden" name="modo" value="correo">
                <button class="enlace menudo" type="submit">Enviar enlace</button>
              </form>

              <?php if (!$esYo): ?>
                <form method="post">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="accion" value="estado">
                  <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                  <button class="enlace menudo" type="submit">
                    <?= (int) $u['activo'] === 1 ? 'Desactivar' : 'Reactivar' ?>
                  </button>
                </form>
              <?php endif; ?>
            </div>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<p class="menudo tenue">
  Las contraseñas no se pueden consultar: la base guarda una huella cifrada,
  no el texto. Por eso aquí se restablecen, nunca se muestran las actuales.
</p>

<?php require __DIR__ . '/../includes/admin-pie.php'; ?>
