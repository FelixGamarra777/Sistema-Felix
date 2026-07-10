-- cyber_compunet_db.sql
-- Dump de referencia con el esquema final (incluye la relación FK).
-- NOTA: Ya NO es necesario importar este archivo manualmente.
-- El sistema (conexion.php + instalador.php) crea y repara la base de
-- datos automáticamente en cada conexión. Este archivo se deja solo
-- como respaldo/documentación de la estructura final.

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

CREATE DATABASE IF NOT EXISTS `cyber_compunet_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE `cyber_compunet_db`;

-- --------------------------------------------------------
-- Tabla `conceptos`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `conceptos` (
  `id_concepto` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_concepto`),
  UNIQUE KEY `nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tabla `movimientos`
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `movimientos` (
  `id_movimiento` INT NOT NULL AUTO_INCREMENT,
  `fecha_movimiento` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  `id_concepto` INT NOT NULL,
  `tipo` ENUM('ingreso','egreso') NOT NULL,
  `cantidad` INT NOT NULL,
  `precio_unitario` DECIMAL(12,2) NOT NULL,
  `monto_total` DECIMAL(12,2) NOT NULL,
  `fuente` VARCHAR(150) NOT NULL,
  PRIMARY KEY (`id_movimiento`),
  CONSTRAINT `fk_movimientos_conceptos`
    FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id_concepto`)
    ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Concepto de respaldo usado por el instalador para reparar registros huérfanos
INSERT INTO `conceptos` (`nombre`) VALUES ('Sin especificar');

COMMIT;
