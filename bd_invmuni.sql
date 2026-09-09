CREATE DATABASE IF NOT EXISTS `bd_invmuni` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `bd_invmuni`;

-- Tabla de Usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `rol` ENUM('Administrador', 'Sistemas') NOT NULL DEFAULT 'Sistemas',
  `creado_en` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO `usuarios` (`nombre`, `email`, `password`, `rol`) VALUES
('Administrador TI', 'admin@muni.gob.pe', '$2y$10$e8pK.KxR8x7g7P5k.u3N.e.X4P7C7B7P7C7B7P7C7B7P7C7B7P7C.', 'Administrador'),
('Soporte Tecnico', 'sistemas@muni.gob.pe', '$2y$10$e8pK.KxR8x7g7P5k.u3N.e.X4P7C7B7P7C7B7P7C7B7P7C7B7P7C.', 'Sistemas');

-- Inventario General
CREATE TABLE IF NOT EXISTS `inventario_general` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sede` VARCHAR(100) DEFAULT 'Palacio Municipal',
  `gerencia_subgerencia` VARCHAR(150) NOT NULL,
  `tipo_bien` VARCHAR(50) DEFAULT 'PC',
  `hostname` VARCHAR(50),
  `ip` VARCHAR(45),
  `usuario_responsable` VARCHAR(150),
  `disco_almacenamiento` VARCHAR(100),
  `procesador` VARCHAR(100),
  `condicion_equipo` ENUM('PROPIO', 'ALQUILADA', 'OTROS') DEFAULT 'PROPIO',
  `repotenciado` VARCHAR(10),
  `ram` VARCHAR(20),
  `sistema_operativo` VARCHAR(50),
  `mac_address` VARCHAR(50),
  `conectividad` VARCHAR(10) DEFAULT 'SI',
  `diagnostico` VARCHAR(100),
  `credencial` VARCHAR(50),
  `modelo_cpu` VARCHAR(100),
  `codigo_patrimonial` VARCHAR(100),
  `inv_2021` VARCHAR(50),
  `inv_2022` VARCHAR(50),
  `inv_2023` VARCHAR(50),
  `inv_2024` VARCHAR(50),
  `inv_2025` VARCHAR(50),
  `observaciones` TEXT,
  `area_origen` VARCHAR(150),
  `observaciones_2` TEXT,
  `situacion_actual` ENUM('En uso', 'Recuperado', 'Mantenimiento', 'Desuso') DEFAULT 'En uso',
  `documento_sustento` VARCHAR(255) NULL
) ENGINE=InnoDB;

-- Inventario Periféricos
CREATE TABLE IF NOT EXISTS `inventario_perifericos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `responsable_registro` VARCHAR(100),
  `sede` VARCHAR(100),
  `area` VARCHAR(150),
  `usuario_responsable` VARCHAR(150),
  `tipo_bien` VARCHAR(50),
  `marca` VARCHAR(100),
  `modelo` VARCHAR(100),
  `serie` VARCHAR(100),
  `estado` VARCHAR(50),
  `ip` VARCHAR(45),
  `condicion` VARCHAR(50),
  `codigo_patrimonial` VARCHAR(100),
  `inv_2021` VARCHAR(50),
  `inv_2022` VARCHAR(50),
  `inv_2023` VARCHAR(50),
  `inv_2024` VARCHAR(50),
  `inv_2025` VARCHAR(50),
  `observaciones` TEXT,
  `mac` VARCHAR(50)
) ENGINE=InnoDB;

-- Inventario Impresoras
CREATE TABLE IF NOT EXISTS `inventario_impresoras` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sede` VARCHAR(100),
  `gerencia_subgerencia` VARCHAR(150),
  `usuario_responsable` VARCHAR(150),
  `marca` VARCHAR(100),
  `modelo` VARCHAR(100),
  `conexion_red` VARCHAR(10),
  `ip` VARCHAR(45),
  `codigo_etiqueta` VARCHAR(50),
  `codigo_patrimonial` VARCHAR(100),
  `inv_2025` VARCHAR(50),
  `observaciones` TEXT
) ENGINE=InnoDB;

-- Teléfonos IP
CREATE TABLE IF NOT EXISTS `inventario_telefonos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gerencia_subgerencia` VARCHAR(150),
  `bien` VARCHAR(50) DEFAULT 'Teléfono IP',
  `marca` VARCHAR(100),
  `modelo` VARCHAR(100),
  `cod_interno` VARCHAR(50),
  `cod_patrimonial` VARCHAR(100),
  `inv_2023` VARCHAR(50),
  `inv_2025` VARCHAR(50)
) ENGINE=InnoDB;

-- Equipos en Desuso
CREATE TABLE IF NOT EXISTS `equipos_desuso` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `sede` VARCHAR(100),
  `tipo_bien` VARCHAR(50),
  `marca` VARCHAR(100),
  `modelo` VARCHAR(100),
  `codigo_patrimonial` VARCHAR(100),
  `inv_2025` VARCHAR(50),
  `observaciones` TEXT
) ENGINE=InnoDB;

-- Estabilizadores
CREATE TABLE IF NOT EXISTS `inventario_estabilizadores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `gerencia_subgerencia` VARCHAR(150),
  `tipo_bien` VARCHAR(50) DEFAULT 'Estabilizador',
  `modelo` VARCHAR(100),
  `codigo_patrimonial` VARCHAR(100),
  `inv_2025` VARCHAR(50)
) ENGINE=InnoDB;