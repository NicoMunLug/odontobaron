-- =====================================================================
--  OdontoBarón · Base de datos
--  Consultorio Odontológico Dra. Laura Alejandra Barón Malagón
--  ---------------------------------------------------------------
--  Importar desde phpMyAdmin (Hostinger):
--    1. Crear la base de datos desde el panel de Hostinger.
--    2. Entrar a phpMyAdmin > seleccionar la base > pestaña "Importar".
--    3. Subir este archivo y ejecutar.
--    4. Copiar las credenciales en includes/config.php
--
--  Motor: MySQL 5.7+ / MariaDB 10.4+   ·   Cotejamiento: utf8mb4
-- =====================================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "-05:00";
SET NAMES utf8mb4;
START TRANSACTION;

-- ---------------------------------------------------------------------
-- Usuarios: pacientes, odontólogos y administradores
-- Hereda el modelo de la clase Persona del diagrama de clases.
-- ---------------------------------------------------------------------
DROP TABLE IF EXISTS `pagos`;
DROP TABLE IF EXISTS `historia_clinica`;
DROP TABLE IF EXISTS `citas`;
DROP TABLE IF EXISTS `bloqueos`;
DROP TABLE IF EXISTS `horarios`;
DROP TABLE IF EXISTS `mensajes`;
DROP TABLE IF EXISTS `tratamientos`;
DROP TABLE IF EXISTS `sedes`;
DROP TABLE IF EXISTS `usuarios`;

CREATE TABLE `usuarios` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`          VARCHAR(60)  NOT NULL,
  `apellido`        VARCHAR(60)  NOT NULL,
  `email`           VARCHAR(120) NOT NULL,
  `telefono`        VARCHAR(25)  DEFAULT NULL,
  `tipo_documento`  ENUM('CC','TI','CE','PA','NIT') NOT NULL DEFAULT 'CC',
  `documento`       VARCHAR(25)  NOT NULL,
  `fecha_nacimiento` DATE        DEFAULT NULL,
  `password_hash`   VARCHAR(255) NOT NULL,
  `rol`             ENUM('paciente','odontologo','admin') NOT NULL DEFAULT 'paciente',
  `especialidad`    VARCHAR(80)  DEFAULT NULL COMMENT 'Solo odontólogos',
  `consultorio`     VARCHAR(20)  DEFAULT NULL COMMENT 'Solo odontólogos',
  `foto`            VARCHAR(160) DEFAULT NULL,
  `alergias`        TEXT         DEFAULT NULL,
  `medicamentos`    TEXT         DEFAULT NULL,
  `antecedentes`    TEXT         DEFAULT NULL,
  `email_verificado` TINYINT(1)  NOT NULL DEFAULT 0,
  `token_verificacion` VARCHAR(64) DEFAULT NULL,
  `activo`          TINYINT(1)   NOT NULL DEFAULT 1,
  `creado_en`       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuarios_email` (`email`),
  UNIQUE KEY `uq_usuarios_documento` (`tipo_documento`, `documento`),
  KEY `idx_usuarios_rol` (`rol`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Sedes del consultorio
-- ---------------------------------------------------------------------
CREATE TABLE `sedes` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`    VARCHAR(80)  NOT NULL,
  `direccion` VARCHAR(160) NOT NULL,
  `barrio`    VARCHAR(80)  DEFAULT NULL,
  `ciudad`    VARCHAR(80)  NOT NULL DEFAULT 'Bogotá, D. C.',
  `telefono`  VARCHAR(25)  DEFAULT NULL,
  `mapa_embed` TEXT        DEFAULT NULL,
  `activo`    TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catálogo de tratamientos
-- ---------------------------------------------------------------------
CREATE TABLE `tratamientos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `slug`          VARCHAR(60)  NOT NULL,
  `nombre`        VARCHAR(90)  NOT NULL,
  `resumen`       VARCHAR(220) NOT NULL,
  `descripcion`   TEXT         NOT NULL,
  `incluye`       TEXT         DEFAULT NULL COMMENT 'Un ítem por línea',
  `imagen`        VARCHAR(160) DEFAULT NULL,
  `duracion_min`  SMALLINT UNSIGNED NOT NULL DEFAULT 45,
  `precio_desde`  DECIMAL(12,2) DEFAULT NULL,
  `destacado`     TINYINT(1)   NOT NULL DEFAULT 0,
  `activo`        TINYINT(1)   NOT NULL DEFAULT 1,
  `orden`         TINYINT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tratamientos_slug` (`slug`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Horarios de atención por odontólogo (1 = lunes ... 7 = domingo)
-- ---------------------------------------------------------------------
CREATE TABLE `horarios` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `odontologo_id` INT UNSIGNED NOT NULL,
  `sede_id`       INT UNSIGNED NOT NULL,
  `dia_semana`    TINYINT UNSIGNED NOT NULL,
  `hora_inicio`   TIME NOT NULL,
  `hora_fin`      TIME NOT NULL,
  `activo`        TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_horarios_odontologo` (`odontologo_id`, `dia_semana`),
  CONSTRAINT `fk_horarios_odontologo` FOREIGN KEY (`odontologo_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_horarios_sede` FOREIGN KEY (`sede_id`)
    REFERENCES `sedes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Bloqueos de agenda (vacaciones, festivos, cierres)
-- ---------------------------------------------------------------------
CREATE TABLE `bloqueos` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `odontologo_id` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = aplica a todo el consultorio',
  `fecha_inicio`  DATE NOT NULL,
  `fecha_fin`     DATE NOT NULL,
  `motivo`        VARCHAR(140) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_bloqueos_fecha` (`fecha_inicio`, `fecha_fin`),
  CONSTRAINT `fk_bloqueos_odontologo` FOREIGN KEY (`odontologo_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Citas
-- La clave única (odontologo_id, fecha, hora) impide dos reservas
-- simultáneas sobre el mismo horario: es el control del caso CP-05.
-- ---------------------------------------------------------------------
CREATE TABLE `citas` (
  `id`             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `codigo`         VARCHAR(12)  NOT NULL COMMENT 'Código visible para el paciente',
  `paciente_id`    INT UNSIGNED NOT NULL,
  `odontologo_id`  INT UNSIGNED NOT NULL,
  `tratamiento_id` INT UNSIGNED NOT NULL,
  `sede_id`        INT UNSIGNED NOT NULL,
  `fecha`          DATE NOT NULL,
  `hora`           TIME NOT NULL,
  `estado`         ENUM('pendiente','confirmada','atendida','cancelada','no_asistio')
                   NOT NULL DEFAULT 'pendiente',
  `valor`          DECIMAL(12,2) DEFAULT NULL,
  `notas_paciente` VARCHAR(400) DEFAULT NULL,
  `notas_internas` TEXT DEFAULT NULL,
  `motivo_cancelacion` VARCHAR(200) DEFAULT NULL,
  `cancelada_en`   DATETIME DEFAULT NULL,
  `recordatorio_enviado` TINYINT(1) NOT NULL DEFAULT 0,
  `creado_en`      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `actualizado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_citas_codigo` (`codigo`),
  UNIQUE KEY `uq_citas_agenda` (`odontologo_id`, `fecha`, `hora`),
  KEY `idx_citas_paciente` (`paciente_id`, `fecha`),
  KEY `idx_citas_fecha` (`fecha`, `estado`),
  CONSTRAINT `fk_citas_paciente` FOREIGN KEY (`paciente_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_citas_odontologo` FOREIGN KEY (`odontologo_id`)
    REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_citas_tratamiento` FOREIGN KEY (`tratamiento_id`)
    REFERENCES `tratamientos` (`id`),
  CONSTRAINT `fk_citas_sede` FOREIGN KEY (`sede_id`)
    REFERENCES `sedes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Historia clínica: una entrada por cita atendida
-- ---------------------------------------------------------------------
CREATE TABLE `historia_clinica` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `paciente_id`   INT UNSIGNED NOT NULL,
  `odontologo_id` INT UNSIGNED NOT NULL,
  `cita_id`       INT UNSIGNED DEFAULT NULL,
  `fecha`         DATE NOT NULL,
  `motivo`        VARCHAR(200) DEFAULT NULL,
  `diagnostico`   TEXT DEFAULT NULL,
  `tratamiento_realizado` TEXT DEFAULT NULL,
  `formula_medica` TEXT DEFAULT NULL,
  `observaciones` TEXT DEFAULT NULL,
  `creado_en`     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_hc_paciente` (`paciente_id`, `fecha`),
  CONSTRAINT `fk_hc_paciente` FOREIGN KEY (`paciente_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hc_odontologo` FOREIGN KEY (`odontologo_id`)
    REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_hc_cita` FOREIGN KEY (`cita_id`)
    REFERENCES `citas` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Pagos: totales o por abonos sobre una cita
-- ---------------------------------------------------------------------
CREATE TABLE `pagos` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `cita_id`    INT UNSIGNED NOT NULL,
  `paciente_id` INT UNSIGNED NOT NULL,
  `valor`      DECIMAL(12,2) NOT NULL,
  `metodo`     ENUM('efectivo','tarjeta_debito','tarjeta_credito','nequi','daviplata','transferencia')
               NOT NULL DEFAULT 'efectivo',
  `tipo`       ENUM('total','abono') NOT NULL DEFAULT 'total',
  `referencia` VARCHAR(60) DEFAULT NULL,
  `estado`     ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'aprobado',
  `registrado_por` INT UNSIGNED DEFAULT NULL,
  `creado_en`  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_pagos_cita` (`cita_id`),
  KEY `idx_pagos_fecha` (`creado_en`),
  CONSTRAINT `fk_pagos_cita` FOREIGN KEY (`cita_id`)
    REFERENCES `citas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pagos_paciente` FOREIGN KEY (`paciente_id`)
    REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Mensajes del formulario de contacto
-- ---------------------------------------------------------------------
CREATE TABLE `mensajes` (
  `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre`    VARCHAR(90)  NOT NULL,
  `email`     VARCHAR(120) NOT NULL,
  `telefono`  VARCHAR(25)  DEFAULT NULL,
  `asunto`    VARCHAR(120) NOT NULL,
  `mensaje`   TEXT NOT NULL,
  `leido`     TINYINT(1) NOT NULL DEFAULT 0,
  `respondido` TINYINT(1) NOT NULL DEFAULT 0,
  `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mensajes_leido` (`leido`, `creado_en`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =====================================================================
--  DATOS INICIALES
-- =====================================================================

-- Sede ------------------------------------------------------------------
INSERT INTO `sedes` (`id`, `nombre`, `direccion`, `barrio`, `ciudad`, `telefono`, `mapa_embed`) VALUES
(1, 'Palermo', 'Calle 45A #20-96, consultorio 702', 'Palermo, Teusaquillo', 'Bogotá, D. C.', '+57 312 371 6032',
 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d254532.71233249127!2d-74.3841873347694!3d4.5808005649172765!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x8e3f9b41e96cbb73%3A0xd6be6267552e93d0!2sDr%20Mauricio%20Baron%20sede%20palermo!5e0!3m2!1ses!2sco!4v1693210441843!5m2!1ses!2sco');

-- Usuarios --------------------------------------------------------------
-- Contraseña de todas las cuentas de ejemplo: OdontoBaron2026*
-- Cámbiela desde el panel apenas inicie sesión.
INSERT INTO `usuarios`
(`id`,`nombre`,`apellido`,`email`,`telefono`,`tipo_documento`,`documento`,`password_hash`,`rol`,`especialidad`,`consultorio`,`email_verificado`) VALUES
(1,'Laura Alejandra','Barón Malagón','draalejandrabaron@odontobaron.com','+57 312 371 6032','CC','52987456',
 '$2y$10$gR.fdSWhyLMZWhk/.L/s2Oi/723l0TXEgLL6L16rcwMKDd5YSTcHy','odontologo','Odontología Digital y Estética','702',1),
(2,'Administración','Consultorio','admin@odontobaron.com','+57 312 371 6032','CC','1000000000',
 '$2y$10$gR.fdSWhyLMZWhk/.L/s2Oi/723l0TXEgLL6L16rcwMKDd5YSTcHy','admin',NULL,NULL,1),
(3,'Nicolás Fernando','Muñoz Lugo','nfmunozl@itc.edu.co','+57 300 000 0000','CC','1000898787',
 '$2y$10$gR.fdSWhyLMZWhk/.L/s2Oi/723l0TXEgLL6L16rcwMKDd5YSTcHy','paciente',NULL,NULL,1),
(4,'Juan Sebastián','Donato Carrillo','jsdonatoc@itc.edu.co','+57 300 000 0001','CC','1000898788',
 '$2y$10$gR.fdSWhyLMZWhk/.L/s2Oi/723l0TXEgLL6L16rcwMKDd5YSTcHy','paciente',NULL,NULL,1),
(5,'Daniel Eduardo','Sánchez Aldana','desanchez@itc.edu.co','+57 300 000 0002','CC','1000898789',
 '$2y$10$gR.fdSWhyLMZWhk/.L/s2Oi/723l0TXEgLL6L16rcwMKDd5YSTcHy','paciente',NULL,NULL,1);

-- Horarios de la doctora (lunes a viernes 8-12 y 14-18; sábado 9-13) -----
INSERT INTO `horarios` (`odontologo_id`,`sede_id`,`dia_semana`,`hora_inicio`,`hora_fin`) VALUES
(1,1,1,'08:00:00','12:00:00'),(1,1,1,'14:00:00','18:00:00'),
(1,1,2,'08:00:00','12:00:00'),(1,1,2,'14:00:00','18:00:00'),
(1,1,3,'08:00:00','12:00:00'),(1,1,3,'14:00:00','18:00:00'),
(1,1,4,'08:00:00','12:00:00'),(1,1,4,'14:00:00','18:00:00'),
(1,1,5,'08:00:00','12:00:00'),(1,1,5,'14:00:00','18:00:00'),
(1,1,6,'09:00:00','13:00:00');

-- Tratamientos ----------------------------------------------------------
INSERT INTO `tratamientos`
(`slug`,`nombre`,`resumen`,`descripcion`,`incluye`,`imagen`,`duracion_min`,`precio_desde`,`destacado`,`orden`) VALUES
('odontologia-estetica','Odontología estética',
 'Diseño de sonrisa, carillas y blanqueamiento para devolverle armonía a tu sonrisa.',
 'La odontología estética reúne los procedimientos que mejoran la apariencia de los dientes, las encías y la sonrisa en conjunto, sin perder de vista la función. Todo parte de un diseño de sonrisa digital: se fotografía y se modela el resultado antes de tocar un solo diente, de modo que puedas ver a dónde vamos antes de empezar.\n\nEl blanqueamiento dental aclara el color del esmalte con agentes controlados en consultorio. Las carillas de resina o porcelana corrigen forma, color y pequeños desalineamientos. Y las coronas devuelven estructura a los dientes muy dañados, cuidando que el resultado se vea natural entre los demás.',
 'Diseño de sonrisa digital\nBlanqueamiento en consultorio\nCarillas en resina o porcelana\nCoronas estéticas\nArmonización de encías',
 'assets/img/tratamientos/odontologia-estetica.jpg',90,NULL,1,1),
('ortodoncia','Ortodoncia',
 'Brackets y alineadores para corregir la posición de los dientes y la mordida.',
 'La ortodoncia corrige los problemas de maloclusión y de alineación dental. El tratamiento comienza con una evaluación minuciosa de la salud bucal y de la posición de los dientes: radiografías, fotografías y modelos de estudio que permiten analizar la mordida, la alineación y la relación entre los maxilares.\n\nLos brackets son dispositivos pequeños que se adhieren a la superficie del diente y se conectan con alambres y elásticos; juntos aplican una presión gradual que lleva cada diente a su posición. Como alternativa estética existen los alineadores transparentes, removibles y prácticamente invisibles.',
 'Estudio de ortodoncia completo\nBrackets metálicos o estéticos\nAlineadores transparentes\nControles mensuales\nRetenedores al finalizar',
 'assets/img/tratamientos/ortodoncia.jpg',45,NULL,1,2),
('implantologia','Implantología',
 'Implantes de titanio que reemplazan la raíz del diente perdido y sostienen la prótesis.',
 'La implantología se dedica a la colocación de implantes dentales y a la restauración de los dientes perdidos. Los implantes son pequeñas estructuras de titanio que se insertan en el hueso maxilar o mandibular y funcionan como raíces artificiales sobre las cuales se colocan coronas, puentes u otras prótesis.\n\nEl proceso comienza con una evaluación de la salud oral y de la estructura ósea para determinar la calidad y la cantidad de hueso disponible. Una vez el implante cicatriza y se integra con el hueso, se coloca la prótesis definitiva, ajustada para que se vea natural junto a los demás dientes.',
 'Valoración y tomografía\nColocación del implante\nPeriodo de oseointegración\nCorona o prótesis definitiva\nControles de seguimiento',
 'assets/img/tratamientos/implantologia.jpg',120,NULL,1,3),
('endodoncia','Endodoncia',
 'Tratamiento de conducto para salvar el diente cuando el nervio está comprometido.',
 'La endodoncia se enfoca en el diagnóstico y el tratamiento de los problemas que afectan la pulpa dental, conocida comúnmente como el nervio del diente. Se realizan radiografías y pruebas clínicas para determinar la extensión del daño y la necesidad del tratamiento.\n\nEl tratamiento de conducto retira la pulpa afectada, limpia y conforma el conducto y lo sella para evitar una nueva infección. Cuando un tratamiento previo no resultó o presentó complicaciones, se realiza un retratamiento para volver a limpiar y sellar el conducto.',
 'Diagnóstico con radiografía\nAnestesia local\nLimpieza y conformación del conducto\nSellado definitivo\nRestauración posterior',
 'assets/img/tratamientos/endodoncia.jpg',90,NULL,0,4),
('periodoncia','Periodoncia',
 'Cuidado de encías y hueso: del sangrado leve a la enfermedad periodontal avanzada.',
 'La periodoncia se ocupa de los tejidos que rodean y sostienen el diente: las encías, el hueso alveolar y el ligamento periodontal. Las enfermedades periodontales van desde la inflamación leve de la encía, la gingivitis, hasta afecciones más graves como la periodontitis, que compromete el soporte del diente.\n\nEl tratamiento parte de un examen clínico y radiográfico que evalúa la salud de la encía y del hueso. A partir de allí se realizan la detartraje, el alisado radicular y, cuando hace falta, procedimientos quirúrgicos para recuperar el tejido perdido.',
 'Examen periodontal completo\nDetartraje y profilaxis\nAlisado radicular\nCirugía periodontal si se requiere\nControles de mantenimiento',
 'assets/img/tratamientos/periodoncia.jpg',60,NULL,0,5),
('cirugia-oral','Cirugía oral',
 'Extracciones, cordales e intervenciones quirúrgicas en boca y estructuras faciales.',
 'La cirugía oral reúne los procedimientos quirúrgicos que se realizan en la boca y las estructuras faciales. La extracción dental es el más frecuente: desde extracciones simples hasta dientes impactados o con raíces curvadas, como suele ocurrir con los cordales.\n\nTambién incluye la colocación de implantes, las cirugías preprotésicas que preparan el terreno para una prótesis y los procedimientos reconstructivos más complejos. Cada intervención se planifica con imágenes previas y se acompaña de indicaciones claras para el posoperatorio.',
 'Valoración e imágenes previas\nExtracción simple o quirúrgica\nCirugía de cordales\nIndicaciones posoperatorias\nControl de cicatrización',
 'assets/img/tratamientos/cirugia-oral.jpg',75,NULL,0,6),
('odontologia-general','Odontología general',
 'Chequeos, limpieza y resinas: el cuidado que evita los tratamientos grandes.',
 'La odontología general se centra en el diagnóstico, la prevención y el tratamiento de los problemas más comunes de la salud bucal. Incluye los exámenes visuales y radiográficos que permiten identificar caries, enfermedades de la encía, infecciones y anomalías dentales antes de que avancen.\n\nLa prevención es su componente principal: las limpiezas regulares eliminan la placa y el sarro que se acumulan en dientes y encías, y evitan que una gingivitis se convierta en periodontitis. Las resinas devuelven forma y función al diente afectado por caries.',
 'Valoración y diagnóstico\nProfilaxis y aplicación de flúor\nResinas y obturaciones\nRadiografías de control\nPlan de tratamiento personalizado',
 'assets/img/tratamientos/odontologia-general.jpg',45,NULL,0,7);

-- Citas de ejemplo ------------------------------------------------------
INSERT INTO `citas`
(`codigo`,`paciente_id`,`odontologo_id`,`tratamiento_id`,`sede_id`,`fecha`,`hora`,`estado`,`valor`,`notas_paciente`) VALUES
('OB-2026-0001',3,1,2,1, DATE_ADD(CURDATE(), INTERVAL 3 DAY),'09:00:00','confirmada',180000,'Control de ortodoncia'),
('OB-2026-0002',4,1,1,1, DATE_ADD(CURDATE(), INTERVAL 5 DAY),'10:30:00','pendiente',250000,'Valoración para diseño de sonrisa'),
('OB-2026-0003',5,1,7,1, DATE_SUB(CURDATE(), INTERVAL 8 DAY),'15:00:00','atendida',120000,NULL),
('OB-2026-0004',3,1,7,1, DATE_SUB(CURDATE(), INTERVAL 20 DAY),'11:00:00','atendida',120000,NULL),
('OB-2026-0005',4,1,5,1, DATE_SUB(CURDATE(), INTERVAL 15 DAY),'16:00:00','no_asistio',150000,NULL),
('OB-2026-0006',5,1,4,1, DATE_SUB(CURDATE(), INTERVAL 30 DAY),'08:30:00','atendida',380000,NULL),
('OB-2026-0007',3,1,3,1, DATE_SUB(CURDATE(), INTERVAL 45 DAY),'14:00:00','cancelada',NULL,NULL);

INSERT INTO `pagos` (`cita_id`,`paciente_id`,`valor`,`metodo`,`tipo`,`referencia`) VALUES
(3,5,120000,'nequi','total','NQ-884512'),
(4,3,120000,'tarjeta_debito','total','TD-119045'),
(6,5,200000,'efectivo','abono','EF-001'),
(6,5,180000,'daviplata','abono','DV-772310');

INSERT INTO `historia_clinica`
(`paciente_id`,`odontologo_id`,`cita_id`,`fecha`,`motivo`,`diagnostico`,`tratamiento_realizado`,`formula_medica`) VALUES
(5,1,3, DATE_SUB(CURDATE(), INTERVAL 8 DAY),'Control y limpieza',
 'Gingivitis leve en sector anteroinferior.','Profilaxis y aplicación de flúor.','Enjuague con clorhexidina al 0,12 %, dos veces al día durante siete días.'),
(3,1,4, DATE_SUB(CURDATE(), INTERVAL 20 DAY),'Molestia al masticar',
 'Caries oclusal en 46.','Resina de fotocurado en 46.',NULL);

INSERT INTO `mensajes` (`nombre`,`email`,`telefono`,`asunto`,`mensaje`,`leido`) VALUES
('María Camila Rojas','mcrojas@example.com','+57 310 555 1212','Cotización diseño de sonrisa',
 'Buenas tardes, quisiera saber el valor aproximado de un diseño de sonrisa con carillas y si manejan plan de pagos.',0),
('Andrés Felipe Gómez','afgomez@example.com','+57 311 444 9090','Horario sábados',
 '¿Atienden los sábados en la tarde? Trabajo entre semana y me queda difícil.',1);

COMMIT;
