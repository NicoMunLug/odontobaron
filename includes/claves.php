<?php
/**
 * Restablecimiento de contraseña.
 *
 * Reglas que sigue este módulo:
 *
 *  - El enlace lleva un token aleatorio de 32 bytes. En la base solo se
 *    guarda su huella SHA-256, así que leer la tabla no sirve para entrar.
 *  - Cada enlace dura una hora y funciona una sola vez.
 *  - Pedir un enlace nuevo anula los anteriores del mismo usuario.
 *  - La página de solicitud responde siempre lo mismo, exista o no el
 *    correo: de lo contrario serviría para averiguar quién es paciente.
 */
require_once __DIR__ . '/auth.php';

const CLAVE_MINIMA     = 8;
const CLAVE_VIGENCIA_H = 1;    // horas que dura el enlace

/** Verifica que la contraseña tenga una forma razonable. */
function clave_valida(string $clave): ?string
{
    if (mb_strlen($clave) < CLAVE_MINIMA) {
        return 'La contraseña debe tener al menos ' . CLAVE_MINIMA . ' caracteres.';
    }
    if (!preg_match('/[A-Za-zÁÉÍÓÚÑáéíóúñ]/u', $clave) || !preg_match('/\d/', $clave)) {
        return 'Combine letras y números para que sea más difícil de adivinar.';
    }
    return null;
}

/**
 * Crea un enlace de restablecimiento para un correo.
 *
 * Devuelve la URL cuando el correo corresponde a una cuenta activa, y
 * null cuando no. Quien llama NO debe revelar esa diferencia al visitante.
 */
function crear_enlace_restablecimiento(string $email): ?string
{
    $email = mb_strtolower(trim($email));
    $u = fila('SELECT id, nombre, email, activo FROM usuarios WHERE email = ?', [$email]);

    if ($u === null || (int) $u['activo'] !== 1) {
        return null;
    }

    // Un enlace nuevo invalida los anteriores.
    q('UPDATE restablecimientos SET usado_en = ? WHERE usuario_id = ? AND usado_en IS NULL',
      [date('Y-m-d H:i:s'), $u['id']]);

    $token = bin2hex(random_bytes(32));

    q('INSERT INTO restablecimientos (usuario_id, token_hash, expira_en, ip) VALUES (?, ?, ?, ?)', [
        $u['id'],
        hash('sha256', $token),
        date('Y-m-d H:i:s', strtotime('+' . CLAVE_VIGENCIA_H . ' hour')),
        substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45),
    ]);

    return base_url('restablecer.php?t=' . $token);
}

/** Busca el usuario de un token vigente. Devuelve null si no sirve. */
function usuario_de_token(string $token): ?array
{
    if ($token === '' || !preg_match('/^[a-f0-9]{64}$/', $token)) {
        return null;
    }

    $r = fila(
        'SELECT r.id AS rid, u.*
           FROM restablecimientos r
           JOIN usuarios u ON u.id = r.usuario_id
          WHERE r.token_hash = ? AND r.usado_en IS NULL AND r.expira_en > ? AND u.activo = 1',
        [hash('sha256', $token), date('Y-m-d H:i:s')]
    );

    return $r ?: null;
}

/**
 * Cambia la contraseña con un token válido y lo marca como usado.
 * Devuelve null si todo salió bien, o el mensaje de error.
 */
function restablecer_con_token(string $token, string $clave, string $confirmacion): ?string
{
    if ($clave !== $confirmacion) {
        return 'Las dos contraseñas no coinciden.';
    }
    if ($error = clave_valida($clave)) {
        return $error;
    }

    $u = usuario_de_token($token);
    if ($u === null) {
        return 'Este enlace ya no sirve. Pida uno nuevo.';
    }

    cambiar_clave((int) $u['id'], $clave);
    q('UPDATE restablecimientos SET usado_en = ? WHERE id = ?', [date('Y-m-d H:i:s'), $u['rid']]);

    return null;
}

/** Guarda una contraseña nueva ya cifrada y cierra los enlaces pendientes. */
function cambiar_clave(int $usuarioId, string $clave): void
{
    q('UPDATE usuarios SET password_hash = ?, email_verificado = 1 WHERE id = ?',
      [password_hash($clave, PASSWORD_DEFAULT), $usuarioId]);

    q('UPDATE restablecimientos SET usado_en = ? WHERE usuario_id = ? AND usado_en IS NULL',
      [date('Y-m-d H:i:s'), $usuarioId]);
}

/** Contraseña provisional legible, para cuando el consultorio la entrega en persona. */
function clave_provisional(): string
{
    $silabas = ['ba', 'ce', 'di', 'fo', 'gu', 'la', 'me', 'ni', 'ro', 'sa', 'te', 'vi'];
    $palabra = '';
    for ($i = 0; $i < 3; $i++) {
        $palabra .= $silabas[random_int(0, count($silabas) - 1)];
    }
    return ucfirst($palabra) . random_int(100, 999);
}

/** Correo con el enlace de restablecimiento. */
function correo_restablecimiento(array $usuario, string $enlace): bool
{
    $html = plantilla_correo('Restablecer su contraseña',
        '<p>Hola, ' . e($usuario['nombre']) . '.</p>
         <p>Recibimos una solicitud para cambiar la contraseña de su cuenta.
            Si fue usted, entre al siguiente enlace. Vence en una hora y
            funciona una sola vez.</p>
         <p style="margin:24px 0">
           <a href="' . e($enlace) . '"
              style="background:#8574A8;color:#fff;padding:12px 22px;border-radius:999px;
                     text-decoration:none;display:inline-block">Cambiar mi contraseña</a>
         </p>
         <p style="font-size:13px;color:#6B6478">Si no lo pidió, puede ignorar este
            mensaje: su contraseña actual sigue funcionando.</p>');

    return enviar_correo($usuario['email'], 'Restablecer su contraseña · ' . SITIO_NOMBRE, $html);
}
