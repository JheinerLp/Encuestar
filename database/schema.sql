-- =====================================================================
-- SISTEMA INTEGRADO DE RADICACIÓN, RUTEO Y CONTROL DE CALIDAD (SIRCE)
-- PROYECTO: DESARROLLO DE SOFTWARE II - UNIVERSIDAD DEL VALLE
-- MANEJADOR: MySQL / MariaDB (XAMPP)
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `encuesta_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `encuesta_db`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `bitacora_eventos`;
DROP TABLE IF EXISTS `revisiones_calidad`;
DROP TABLE IF EXISTS `encuestas`;
DROP TABLE IF EXISTS `hoja_ruta_items`;
DROP TABLE IF EXISTS `hojas_ruta`;
DROP TABLE IF EXISTS `solicitudes`;
DROP TABLE IF EXISTS `usuarios`;
DROP TABLE IF EXISTS `puntos_atencion`;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------
-- 1. PUNTOS DE ATENCIÓN ESTÁTICOS DE CALI (Requerimiento 5)
-- Ubicados en comunas 1, 6, 7, 13, 14, 20, 21 y 17 (Oficina principal)
-- -----------------------------------------------------
CREATE TABLE `puntos_atencion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(150) NOT NULL,
  `comuna` INT NOT NULL,
  `zona` ENUM('urbana', 'rural') DEFAULT 'urbana',
  `barrio` VARCHAR(100) NOT NULL,
  `direccion` VARCHAR(180) NOT NULL,
  `telefono` VARCHAR(50) NOT NULL,
  `latitud` DECIMAL(10, 8) NOT NULL,
  `longitud` DECIMAL(11, 8) NOT NULL,
  `es_principal` TINYINT(1) DEFAULT 0,
  `descripcion` TEXT NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 2. USUARIOS Y PERFILES (Requerimiento 1)
-- Perfiles: administrador, ventanilla, encuestador, calidad, enrutador
-- -----------------------------------------------------
CREATE TABLE `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `cedula` VARCHAR(30) NOT NULL UNIQUE,
  `nombre_completo` VARCHAR(120) NOT NULL,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `email` VARCHAR(120) NOT NULL,
  `telefono` VARCHAR(30) NULL,
  `rol` ENUM('administrador', 'ventanilla', 'encuestador', 'calidad', 'enrutador') NOT NULL,
  `punto_atencion_id` INT NULL,
  `antiguedad_anios` INT DEFAULT 4,
  `activo` TINYINT(1) DEFAULT 1,
  `ultimo_acceso` DATETIME NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_usuario_punto` FOREIGN KEY (`punto_atencion_id`) REFERENCES `puntos_atencion` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 3. SOLICITUDES DE ENCUESTA (Requerimientos 2, 3 y 4)
-- Incluye georreferenciación precisa y regla de los 15 días calendario
-- -----------------------------------------------------
CREATE TABLE `solicitudes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `numero_radicado` VARCHAR(35) NOT NULL UNIQUE,
  `punto_atencion_id` INT NOT NULL,
  `usuario_ventanilla_id` INT NOT NULL,
  `nombres` VARCHAR(100) NOT NULL,
  `apellidos` VARCHAR(100) NOT NULL,
  `tipo_documento` VARCHAR(10) DEFAULT 'CC',
  `documento_identidad` VARCHAR(25) NOT NULL,
  `zona_tipo` ENUM('urbana', 'rural') DEFAULT 'urbana',
  `comuna` INT NULL,
  `corregimiento` VARCHAR(100) NULL,
  `barrio` VARCHAR(100) NOT NULL,
  `direccion` VARCHAR(200) NOT NULL,
  `celular` VARCHAR(20) NOT NULL,
  `telefono_secundario` VARCHAR(20) NULL,
  `correo_contacto` VARCHAR(100) NULL,
  `latitud` DECIMAL(10, 8) NOT NULL,
  `longitud` DECIMAL(11, 8) NOT NULL,
  `motivo_solicitud` VARCHAR(255) DEFAULT 'Solicitud de encuesta beneficio crediticio gobierno extranjero',
  `fecha_radicacion` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `fecha_limite_15dias` DATE NOT NULL,
  `estado` ENUM('radicada', 'enrutada', 'en_campo', 'encuestada', 'en_revision', 'aprobada_apto', 'rechazada_no_apto', 'vencida') DEFAULT 'radicada',
  `observaciones` TEXT NULL,
  CONSTRAINT `fk_solicitud_punto` FOREIGN KEY (`punto_atencion_id`) REFERENCES `puntos_atencion` (`id`),
  CONSTRAINT `fk_solicitud_ventanilla` FOREIGN KEY (`usuario_ventanilla_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 4. HOJAS DE RUTA (Requerimientos 6 y 7)
-- Carga de hasta 20 encuestas por encuestador, ruteo y entrega en punto
-- -----------------------------------------------------
CREATE TABLE `hojas_ruta` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_hoja` VARCHAR(35) NOT NULL UNIQUE,
  `enrutador_id` INT NOT NULL,
  `encuestador_id` INT NOT NULL,
  `punto_atencion_id` INT NOT NULL,
  `fecha_programada` DATE NOT NULL,
  `camioneta_asignada` VARCHAR(60) NOT NULL,
  `punto_encuentro` VARCHAR(150) NOT NULL,
  `hora_salida` TIME DEFAULT '07:00:00',
  `hora_recogida` TIME DEFAULT '12:30:00',
  `total_encuestas` INT NOT NULL DEFAULT 0,
  `estado` ENUM('programada', 'entregada_a_encuestador', 'en_ejecucion', 'completada') DEFAULT 'programada',
  `fecha_entrega_ventanilla` DATETIME NULL,
  `ventanilla_entrega_id` INT NULL,
  `observaciones_enrutador` TEXT NULL,
  `observaciones_entrega` TEXT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hr_enrutador` FOREIGN KEY (`enrutador_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_hr_encuestador` FOREIGN KEY (`encuestador_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_hr_punto` FOREIGN KEY (`punto_atencion_id`) REFERENCES `puntos_atencion` (`id`),
  CONSTRAINT `fk_hr_ventanilla` FOREIGN KEY (`ventanilla_entrega_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 5. ITEMS DE LA HOJA DE RUTA (Relación con Solicitudes)
-- -----------------------------------------------------
CREATE TABLE `hoja_ruta_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hoja_ruta_id` INT NOT NULL,
  `solicitud_id` INT NOT NULL,
  `orden_visita` INT NOT NULL,
  `llamada_previa_realizada` TINYINT(1) DEFAULT 0,
  `fecha_llamada_previa` DATETIME NULL,
  `observacion_llamada` VARCHAR(255) NULL,
  `estado_visita` ENUM('pendiente', 'efectiva', 'no_contacto', 'rechazada', 'reprogramada') DEFAULT 'pendiente',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hritem_hoja` FOREIGN KEY (`hoja_ruta_id`) REFERENCES `hojas_ruta` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hritem_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 6. ENCUESTAS DIGITALES DILIGENCIADAS EN CAMPO (Requerimiento 7)
-- Erradica errores de papel: validaciones obligatorias, edad menor, etc.
-- -----------------------------------------------------
CREATE TABLE `encuestas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `solicitud_id` INT NOT NULL UNIQUE,
  `hoja_ruta_id` INT NULL,
  `encuestador_id` INT NOT NULL,
  `fecha_levantamiento` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `jefe_hogar_nombre` VARCHAR(150) NOT NULL,
  `numero_integrantes` INT NOT NULL,
  `tiene_menores` TINYINT(1) NOT NULL DEFAULT 0,
  `menor_nombre` VARCHAR(150) NULL,
  `menor_fecha_nacimiento` DATE NULL,
  `menor_edad_calculada` INT NULL,
  `ingreso_mensual` DECIMAL(12, 2) NOT NULL,
  `tipo_vivienda` ENUM('propia', 'arrendada', 'familiar', 'posesion') NOT NULL,
  `estrato` INT NOT NULL,
  `servicios_basicos` VARCHAR(200) NOT NULL,
  `motivo_prestamo_detalle` TEXT NOT NULL,
  `latitud_visita` DECIMAL(10, 8) NULL,
  `longitud_visita` DECIMAL(11, 8) NULL,
  `firma_digital` VARCHAR(100) NULL,
  `observaciones_encuestador` TEXT NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_encuesta_solicitud` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`id`),
  CONSTRAINT `fk_encuesta_hoja` FOREIGN KEY (`hoja_ruta_id`) REFERENCES `hojas_ruta` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_encuesta_encuestador` FOREIGN KEY (`encuestador_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 7. REVISIONES DE CALIDAD (Revisión diaria del equipo de 5)
-- Dictamen oficial: Apto / No apto para beneficio de gobierno extranjero
-- -----------------------------------------------------
CREATE TABLE `revisiones_calidad` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `encuesta_id` INT NOT NULL UNIQUE,
  `usuario_calidad_id` INT NOT NULL,
  `fecha_revision` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `consistencia_barrio_comuna` TINYINT(1) NOT NULL DEFAULT 1,
  `consistencia_edad_menor` TINYINT(1) NOT NULL DEFAULT 1,
  `formulario_completo` TINYINT(1) NOT NULL DEFAULT 1,
  `identificacion_encuestador_valida` TINYINT(1) NOT NULL DEFAULT 1,
  `resultado_oficial` ENUM('apto', 'no_apto') NOT NULL,
  `dictamen_justificacion` TEXT NOT NULL,
  `observaciones_auditoria` TEXT NULL,
  CONSTRAINT `fk_rev_encuesta` FOREIGN KEY (`encuesta_id`) REFERENCES `encuestas` (`id`),
  CONSTRAINT `fk_rev_calidad` FOREIGN KEY (`usuario_calidad_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------
-- 8. BITÁCORA DE AUDITORÍA Y TRAZABILIDAD
-- -----------------------------------------------------
CREATE TABLE `bitacora_eventos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NULL,
  `rol_usuario` VARCHAR(40) NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `accion` VARCHAR(100) NOT NULL,
  `detalle` TEXT NOT NULL,
  `ip_origen` VARCHAR(45) NULL,
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_bitacora_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
