<?php
/**
 * Registro, inicio de sesión y control de acceso por rol.
 */
require_once __DIR__ . '/functions.php';

function usuario_actual(): ?array
{
    sesion_iniciar();
    if (empty($_SESSION['usuario_id'])) {
        return null;
    }
    static $cache = null;
    if ($cache === null) {
        $cache = fila('SELECT * FROM usuarios WHERE id = ? AND activo = 1', [$_SESSION['usuario_id']]);
        if ($cache === null) {
            cerrar_sesion();
        }
    }
    return $cache;
}

function autenticado(): bool
{
    return usuario_actual() !== null;
}

function rol(): ?string
{
    return usuario_actual()['rol'] ?? null;
}

function es_personal(): bool
{
    return in_array(rol(), ['admin', 'odontologo'], true);
}

function exigir_sesion(): void
{
    if (!autenticado()) {
        sesion_iniciar();
        $_SESSION['destino'] = $_SERVER['REQUEST_URI'] ?? '';
        flash('aviso', 'Inicie sesión para continuar.');
        redirigir('ingresar.php');
    }
}

function exigir_personal(): void
{
    exigir_sesion();
    if (!es_personal()) {
        http_response_code(403);
        exit('No tiene permisos para ver esta página.');
    }
}

function exigir_admin(): void
{
    exigir_sesion();
    if (rol() !== 'admin') {
        http_response_code(403);
        exit('Esta sección es solo para la administración del consultorio.');
    }
}

/**
 * Intenta iniciar sesión. Devuelve null si todo va bien, o el mensaje de error.
 */
function iniciar_sesion(string $email, string $password): ?string
{
    $u = fila('SELECT * FROM usuarios WHERE email = ?', [mb_strtolower(trim($email))]);

    if ($u === null || !password_verify($password, $u['password_hash'])) {
        return 'El correo o la contraseña no coinciden.';
    }
    if ((int) $u['activo'] !== 1) {
        return 'Esta cuenta está desactivada. Escríbanos para reactivarla.';
    }
    if ((int) $u['email_verificado'] !== 1) {
        return 'Confirme su cuenta desde el correo que le enviamos antes de ingresar.';
    }

    sesion_iniciar();
    session_regenerate_id(true);
    $_SESSION['usuario_id'] = (int) $u['id'];
    return null;
}

function cerrar_sesion(): void
{
    sesion_iniciar();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Registra un paciente. Devuelve ['ok'=>bool,'errores'=>[], 'id'=>int].
 */
function registrar_paciente(array $d): array
{
    $errores = [];

    $nombre    = trim($d['nombre'] ?? '');
    $apellido  = trim($d['apellido'] ?? '');
    $email     = mb_strtolower(trim($d['email'] ?? ''));
    $telefono  = trim($d['telefono'] ?? '');
    $tipoDoc   = $d['tipo_documento'] ?? 'CC';
    $documento = trim($d['documento'] ?? '');
    $pass      = $d['password'] ?? '';
    $pass2     = $d['password2'] ?? '';

    if (mb_strlen($nombre) < 2)   $errores['nombre'] = 'Escriba su nombre.';
    if (mb_strlen($apellido) < 2) $errores['apellido'] = 'Escriba sus apellidos.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errores['email'] = 'Revise el correo.';
    if (!preg_match('/^[0-9+\s()-]{7,25}$/', $telefono)) $errores['telefono'] = 'Revise el teléfono.';
    if (!preg_match('/^[0-9A-Za-z-]{5,25}$/', $documento)) $errores['documento'] = 'Revise el número de documento.';
    if (!in_array($tipoDoc, ['CC', 'TI', 'CE', 'PA'], true)) $errores['tipo_documento'] = 'Seleccione el tipo de documento.';
    if (strlen($pass) < 8) $errores['password'] = 'Use al menos 8 caracteres.';
    if ($pass !== $pass2)  $errores['password2'] = 'Las dos contraseñas no coinciden.';

    if (!$errores) {
        if (valor('SELECT COUNT(*) FROM usuarios WHERE email = ?', [$email]) > 0) {
            $errores['email'] = 'Ya existe una cuenta con este correo.';
        } elseif (valor('SELECT COUNT(*) FROM usuarios WHERE tipo_documento = ? AND documento = ?',
                        [$tipoDoc, $documento]) > 0) {
            $errores['documento'] = 'Ya existe una cuenta con este documento.';
        }
    }

    if ($errores) {
        return ['ok' => false, 'errores' => $errores, 'id' => 0];
    }

    $token = bin2hex(random_bytes(24));
    q('INSERT INTO usuarios (nombre, apellido, email, telefono, tipo_documento, documento,
            password_hash, rol, email_verificado, token_verificacion)
       VALUES (?,?,?,?,?,?,?,?,?,?)',
      [$nombre, $apellido, $email, $telefono, $tipoDoc, $documento,
       password_hash($pass, PASSWORD_BCRYPT), 'paciente', 0, $token]);

    $id = (int) db()->lastInsertId();

    $enlace = base_url('confirmar.php?token=' . $token);
    if (SITIO_URL !== '') {
        $enlace = SITIO_URL . '/confirmar.php?token=' . $token;
    }
    enviar_correo($email, 'Confirme su cuenta · ' . SITIO_NOMBRE,
        plantilla_correo('Ya casi está',
            '<p>Hola ' . e($nombre) . ', confirme su cuenta para agendar citas en línea.</p>
             <p><a href="' . e($enlace) . '" style="background:#8574A8;color:#fff;padding:12px 20px;
                border-radius:999px;text-decoration:none;display:inline-block">Confirmar mi cuenta</a></p>
             <p style="font-size:13px;color:#6B6478">Si el botón no funciona, copie este enlace:<br>'
             . e($enlace) . '</p>'));

    return ['ok' => true, 'errores' => [], 'id' => $id, 'token' => $token];
}
