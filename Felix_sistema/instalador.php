<?php
// instalador.php
// -----------------------------------------------------------------------
// SISTEMA AUTOMATIZADO DE INSTALACIÓN / AUTO-REPARACIÓN DE BASE DE DATOS
// -----------------------------------------------------------------------
// Se ejecuta en cada conexión (desde conexion.php) y garantiza que:
//   1. Las tablas necesarias existan (las crea si faltan).
//   2. Las columnas necesarias existan (las agrega si faltan).
//   3. Los datos "huérfanos" (id_concepto inválido) se reparen.
//   4. Exista la relación (FK) entre movimientos y conceptos.
//
// Esto evita para siempre errores como:
//   SQLSTATE[42S22]: Column not found: 1054 Unknown column 'id_concepto'
// aunque la base de datos real esté desactualizada respecto al código.
// -----------------------------------------------------------------------

function verificarYRepararBaseDeDatos(PDO $pdo) {

    // ---- 1. Crear tabla `conceptos` si no existe ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `conceptos` (
            `id_concepto`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nombre`         VARCHAR(100) NOT NULL,
            `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_concepto`),
            UNIQUE KEY `nombre` (`nombre`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ---- 2. Crear tabla `movimientos` si no existe ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `movimientos` (
            `id_movimiento`    INT NOT NULL AUTO_INCREMENT,
            `fecha_movimiento` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `id_concepto`      INT NOT NULL,
            `tipo`             ENUM('ingreso','egreso') NOT NULL,
            `cantidad`         INT NOT NULL,
            `precio_unitario`  DECIMAL(12,2) NOT NULL,
            `monto_total`      DECIMAL(12,2) NOT NULL,
            `fuente`           VARCHAR(150) NOT NULL,
            PRIMARY KEY (`id_movimiento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ---- 2b. Crear tablas `bancos`, `clientes` y `proveedores` si no existen ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `bancos` (
            `id_banco`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nombre_banco` VARCHAR(100) NOT NULL,
            PRIMARY KEY (`id_banco`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `clientes` (
            `id_cliente`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nombre_empresa` VARCHAR(150) NOT NULL,
            `cedula_rif`     VARCHAR(20) NOT NULL,
            `tipo_persona`   ENUM('Natural','Juridica') NOT NULL,
            `fecha_registro` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_cliente`),
            UNIQUE KEY `idx_rif_cliente` (`cedula_rif`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `proveedores` (
            `id_proveedor`   BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `nombre_empresa` VARCHAR(150) NOT NULL,
            `cedula_rif`     VARCHAR(20) NOT NULL,
            `tipo_persona`   ENUM('Natural','Juridica') NOT NULL,
            `fecha_registro` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id_proveedor`),
            UNIQUE KEY `idx_rif_proveedor` (`cedula_rif`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ---- 2c. Tablas del módulo de tasa BCV y facturación (POS) ----
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `tasas_bcv` (
            `fecha`          DATE NOT NULL,
            `tasa`           DECIMAL(14,4) NOT NULL,
            `origen`         ENUM('api','manual') NOT NULL DEFAULT 'api',
            `actualizado_en` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`fecha`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `facturas` (
            `id_factura`     BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `numero_factura` VARCHAR(50) NOT NULL,
            `fecha_factura`  TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `id_cliente`     BIGINT UNSIGNED NULL,
            `total_usd`      DECIMAL(14,2) NOT NULL DEFAULT 0,
            `total_bs`       DECIMAL(18,2) NOT NULL DEFAULT 0,
            `tasa_bcv`       DECIMAL(14,4) NOT NULL DEFAULT 0,
            `referencia`     VARCHAR(150) NULL,
            `usuario`        VARCHAR(50) NULL,
            PRIMARY KEY (`id_factura`),
            UNIQUE KEY `idx_numero_factura` (`numero_factura`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `factura_items` (
            `id_item`         BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_factura`      BIGINT UNSIGNED NOT NULL,
            `id_concepto`     BIGINT UNSIGNED NOT NULL,
            `descripcion`     VARCHAR(150) NOT NULL,
            `cantidad`        INT NOT NULL,
            `precio_unitario` DECIMAL(12,2) NOT NULL,
            `monto_total`     DECIMAL(14,2) NOT NULL,
            PRIMARY KEY (`id_item`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `factura_pagos` (
            `id_pago`    BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `id_factura` BIGINT UNSIGNED NOT NULL,
            `forma_pago` VARCHAR(30) NOT NULL,
            `moneda`     ENUM('USD','BS') NOT NULL DEFAULT 'USD',
            `monto`      DECIMAL(18,2) NOT NULL,
            `monto_usd`  DECIMAL(14,2) NOT NULL,
            `id_banco`   BIGINT UNSIGNED NULL,
            `referencia` VARCHAR(150) NULL,
            PRIMARY KEY (`id_pago`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");

    // ---- 3. Verificar columnas obligatorias y agregarlas si faltan ----
    // Esto es lo que soluciona tu error actual: si `movimientos` ya existe
    // pero le falta `id_concepto` (o cualquier otra columna), se agrega sola.
    $columnasRequeridas = [
        'movimientos' => [
            'id_concepto'        => "INT NOT NULL DEFAULT 0 AFTER fecha_movimiento",
            'tipo'               => "ENUM('ingreso','egreso') NOT NULL AFTER id_concepto",
            'cantidad'           => "INT NOT NULL DEFAULT 1 AFTER tipo",
            'precio_unitario'    => "DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER cantidad",
            'monto_total'        => "DECIMAL(12,2) NOT NULL DEFAULT 0 AFTER precio_unitario",
            'fuente'             => "VARCHAR(150) NOT NULL DEFAULT '' AFTER monto_total",
            'forma_pago'         => "ENUM('Efectivo','Dolares','Pago Movil','Transferencia') NOT NULL AFTER fuente",
            'id_banco'           => "BIGINT UNSIGNED NULL AFTER forma_pago",
            'id_cliente'         => "BIGINT UNSIGNED NULL AFTER id_banco",
            'id_proveedor'       => "BIGINT UNSIGNED NULL AFTER id_cliente",
            'numero_factura'     => "VARCHAR(50) NULL AFTER id_proveedor",
            'fuente_referencia'  => "VARCHAR(150) NULL AFTER numero_factura",
            'tasa_bcv'           => "DECIMAL(14,4) NULL AFTER fuente_referencia",
            'monto_bs'           => "DECIMAL(18,2) NULL AFTER tasa_bcv",
            'id_factura'         => "BIGINT UNSIGNED NULL AFTER monto_bs",
        ],
        'conceptos' => [
            'nombre'          => "VARCHAR(100) NOT NULL",
            'tipo_concepto'   => "ENUM('ingreso','egreso') NULL AFTER nombre",
            'categoria'       => "ENUM('producto','servicio') NOT NULL DEFAULT 'servicio' AFTER tipo_concepto",
            'grupo'           => "VARCHAR(50) NULL AFTER categoria",
            'precio_unitario' => "DECIMAL(12,2) NULL AFTER categoria",
            'stock'           => "INT NULL AFTER precio_unitario",
            'fecha_creacion'  => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP",
        ],
        'facturas' => [
            // Orden importante: cada columna se agrega solo si falta, y las
            // cláusulas AFTER apuntan a columnas que ya existen en este punto.
            'numero_factura'  => "VARCHAR(50) NOT NULL DEFAULT '' AFTER id_factura",
            'fecha_factura'   => "TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP AFTER numero_factura",
            'id_cliente'      => "BIGINT UNSIGNED NULL AFTER fecha_factura",
            'total_usd'       => "DECIMAL(14,2) NOT NULL DEFAULT 0 AFTER id_cliente",
            'total_bs'        => "DECIMAL(18,2) NOT NULL DEFAULT 0 AFTER total_usd",
            'tasa_bcv'        => "DECIMAL(14,4) NOT NULL DEFAULT 0 AFTER total_bs",
            'referencia'      => "VARCHAR(150) NULL AFTER tasa_bcv",
            'usuario'         => "VARCHAR(50) NULL AFTER referencia",
        ],
    ];

    foreach ($columnasRequeridas as $tabla => $columnas) {
        foreach ($columnas as $columna => $definicion) {
            if (!existeColumna($pdo, $tabla, $columna)) {
                $pdo->exec("ALTER TABLE `$tabla` ADD COLUMN `$columna` $definicion");
            }
        }
    }

    // ---- 3b. Eliminar objetos heredados de esquemas antiguos ----
    // Versiones viejas de `facturas` tenían la columna `id_movimiento_asociado`
    // y la FK `fk_fac_movimiento` (hacia `movimientos`) que el código actual ya
    // no usa. Con un DEFAULT inválido (0) esa FK rompe TODO INSERT de factura con
    // error 1452. Se elimina primero la FK y luego la columna huérfana.
    $llavesObsoletas = [
        ['facturas', 'fk_fac_movimiento'],
    ];
    foreach ($llavesObsoletas as [$tabla, $nombreFk]) {
        if (existeLlaveForanea($pdo, $tabla, $nombreFk)) {
            try { $pdo->exec("ALTER TABLE `$tabla` DROP FOREIGN KEY `$nombreFk`"); }
            catch (\Exception $e) { /* si no se puede, no se detiene el sistema */ }
        }
    }
    $columnasObsoletas = [
        ['facturas', 'id_movimiento_asociado'],
    ];
    foreach ($columnasObsoletas as [$tabla, $columna]) {
        if (existeColumna($pdo, $tabla, $columna)) {
            try { $pdo->exec("ALTER TABLE `$tabla` DROP COLUMN `$columna`"); }
            catch (\Exception $e) { /* idem */ }
        }
    }

    // ---- 4. Reparar movimientos "huérfanos" (id_concepto que no existe) ----
    // Ej: los registros viejos con id_concepto = 0 que ya tienes en tu tabla.
    $stmt = $pdo->prepare("SELECT id_concepto FROM conceptos WHERE nombre = ?");
    $stmt->execute(['Sin especificar']);
    $idRespaldo = $stmt->fetchColumn();

    if (!$idRespaldo) {
        $pdo->exec("INSERT INTO conceptos (nombre) VALUES ('Sin especificar')");
        $idRespaldo = $pdo->lastInsertId();
    }

    $pdo->exec("
        UPDATE movimientos m
        LEFT JOIN conceptos c ON m.id_concepto = c.id_concepto
        SET m.id_concepto = $idRespaldo
        WHERE c.id_concepto IS NULL
    ");

    // ---- 5. Crear las relaciones (llaves foráneas) si aún no existen ----
    // Ahora que no hay huérfanos, esto normalmente ya puede crearse sin error.
    $relaciones = [
        ['movimientos', 'fk_movimientos_conceptos', "ADD CONSTRAINT `fk_movimientos_conceptos`
            FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id_concepto`)
            ON DELETE RESTRICT ON UPDATE CASCADE"],
        ['movimientos', 'fk_mov_banco', "ADD CONSTRAINT `fk_mov_banco`
            FOREIGN KEY (`id_banco`) REFERENCES `bancos`(`id_banco`)
            ON DELETE SET NULL ON UPDATE CASCADE"],
        ['movimientos', 'fk_mov_cliente', "ADD CONSTRAINT `fk_mov_cliente`
            FOREIGN KEY (`id_cliente`) REFERENCES `clientes`(`id_cliente`)
            ON DELETE SET NULL ON UPDATE CASCADE"],
        ['movimientos', 'fk_mov_proveedor', "ADD CONSTRAINT `fk_mov_proveedor`
            FOREIGN KEY (`id_proveedor`) REFERENCES `proveedores`(`id_proveedor`)
            ON DELETE SET NULL ON UPDATE CASCADE"],
        ['movimientos', 'fk_mov_factura', "ADD CONSTRAINT `fk_mov_factura`
            FOREIGN KEY (`id_factura`) REFERENCES `facturas`(`id_factura`)
            ON DELETE SET NULL ON UPDATE CASCADE"],
        ['facturas', 'fk_fact_cliente', "ADD CONSTRAINT `fk_fact_cliente`
            FOREIGN KEY (`id_cliente`) REFERENCES `clientes`(`id_cliente`)
            ON DELETE SET NULL ON UPDATE CASCADE"],
        ['factura_items', 'fk_item_factura', "ADD CONSTRAINT `fk_item_factura`
            FOREIGN KEY (`id_factura`) REFERENCES `facturas`(`id_factura`)
            ON DELETE CASCADE ON UPDATE CASCADE"],
        ['factura_items', 'fk_item_concepto', "ADD CONSTRAINT `fk_item_concepto`
            FOREIGN KEY (`id_concepto`) REFERENCES `conceptos`(`id_concepto`)
            ON DELETE RESTRICT ON UPDATE CASCADE"],
        ['factura_pagos', 'fk_pago_factura', "ADD CONSTRAINT `fk_pago_factura`
            FOREIGN KEY (`id_factura`) REFERENCES `facturas`(`id_factura`)
            ON DELETE CASCADE ON UPDATE CASCADE"],
        ['factura_pagos', 'fk_pago_banco', "ADD CONSTRAINT `fk_pago_banco`
            FOREIGN KEY (`id_banco`) REFERENCES `bancos`(`id_banco`)
            ON DELETE SET NULL ON UPDATE CASCADE"],
    ];

    foreach ($relaciones as [$tabla, $nombreFk, $ddl]) {
        if (!existeLlaveForanea($pdo, $tabla, $nombreFk)) {
            try {
                $pdo->exec("ALTER TABLE `$tabla` $ddl");
            } catch (\Exception $e) {
                // Si por algún motivo no se puede crear todavía, no se detiene
                // el sistema: seguirá funcionando y se reintentará luego.
            }
        }
    }
}

function existeColumna(PDO $pdo, $tabla, $columna) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$tabla, $columna]);
    return $stmt->fetchColumn() > 0;
}

function existeLlaveForanea(PDO $pdo, $tabla, $nombreFk) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ?
    ");
    $stmt->execute([$tabla, $nombreFk]);
    return $stmt->fetchColumn() > 0;
}
