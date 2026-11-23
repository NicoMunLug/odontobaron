-- =====================================================================
--  OdontoBarón · Migración 02
--  Restablecimiento de contraseña
--  ---------------------------------------------------------------
--  Ejecútela solo si ya importó odontobaron.sql antes de esta versión.
--  En una instalación nueva no hace falta: la tabla ya viene en el
--  volcado principal.
--
--  phpMyAdmin > seleccione la base > pestaña "SQL" > pegue y ejecute.
-- =====================================================================

SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- Enlaces de restablecimiento
--
-- No se guarda el token que viaja en el correo, sino su huella SHA-256.
-- Si alguien lee la tabla no puede reconstruir el enlace, igual que pasa
-- con las contraseñas.
-- ---------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `restablecimientos` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `token_hash`  CHAR(64)     NOT NULL COMMENT 'SHA-256 del token enviado',
  `expira_en`   DATETIME     NOT NULL,
  `usado_en`    DATETIME     DEFAULT NULL,
  `ip`          VARCHAR(45)  DEFAULT NULL,
  `creado_en`   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_restablecimientos_token` (`token_hash`),
  KEY `idx_restablecimientos_usuario` (`usuario_id`, `usado_en`),
  CONSTRAINT `fk_restablecimientos_usuario`
    FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
