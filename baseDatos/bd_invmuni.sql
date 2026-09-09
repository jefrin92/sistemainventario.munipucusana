-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 29-08-2026 a las 01:21:26
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `bd_invmuni`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_computo`
--

CREATE TABLE `inventario_computo` (
  `id` int(11) NOT NULL,
  `gerencia_subgerencia` varchar(150) DEFAULT NULL,
  `usuario` varchar(150) DEFAULT NULL,
  `hostname` varchar(100) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `almacenamiento` varchar(100) DEFAULT NULL,
  `procesador_ram` varchar(150) DEFAULT NULL,
  `condicion` varchar(50) DEFAULT NULL,
  `diagnostico_sustento` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_desuso`
--

CREATE TABLE `inventario_desuso` (
  `id` int(11) NOT NULL,
  `sede` varchar(150) DEFAULT NULL,
  `tipo_bien` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `codigo_patrimonial` varchar(100) DEFAULT NULL,
  `inventario_2020` varchar(50) DEFAULT NULL,
  `inventario_2021` varchar(50) DEFAULT NULL,
  `inventario_2022` varchar(50) DEFAULT NULL,
  `inventario_2023` varchar(50) DEFAULT NULL,
  `inventario_2025` varchar(50) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_especifico`
--

CREATE TABLE `inventario_especifico` (
  `id` int(11) NOT NULL,
  `gerencia_subgerencia` varchar(150) DEFAULT NULL,
  `bien` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `cod_int` varchar(100) DEFAULT NULL,
  `cod_pat` varchar(100) DEFAULT NULL,
  `inv_2023` varchar(50) DEFAULT NULL,
  `inv_2025` varchar(50) DEFAULT NULL,
  `inv_2026` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_estabilizadores`
--

CREATE TABLE `inventario_estabilizadores` (
  `id` int(11) NOT NULL,
  `gerencia_subgerencia` varchar(150) DEFAULT NULL,
  `tipo_bien` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `codigo_patrimonial` varchar(100) DEFAULT NULL,
  `inventario_2020` varchar(50) DEFAULT NULL,
  `inventario_2021` varchar(50) DEFAULT NULL,
  `inventario_2022` varchar(50) DEFAULT NULL,
  `inventario_2023` varchar(50) DEFAULT NULL,
  `inventario_2025` varchar(50) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_general`
--

CREATE TABLE `inventario_general` (
  `id` int(11) NOT NULL,
  `sede_municipal` varchar(150) DEFAULT NULL,
  `gerencia_subgerencia` varchar(150) DEFAULT NULL,
  `tipo_bien` varchar(100) DEFAULT NULL,
  `hostname` varchar(100) DEFAULT NULL,
  `direccion_ip` varchar(45) DEFAULT NULL,
  `usuario_responsable` varchar(150) DEFAULT NULL,
  `disco_duro_ssd` varchar(100) DEFAULT NULL,
  `tipo_procesador` varchar(100) DEFAULT NULL,
  `condicion_equipo` varchar(50) DEFAULT NULL,
  `repotenciado` varchar(10) DEFAULT NULL,
  `ram` varchar(50) DEFAULT NULL,
  `sistema_operativo` varchar(100) DEFAULT NULL,
  `mac_address` varchar(50) DEFAULT NULL,
  `conectividad_red` varchar(50) DEFAULT NULL,
  `diagnostico` text DEFAULT NULL,
  `credencial` varchar(100) DEFAULT NULL,
  `modelo_cpu` varchar(100) DEFAULT NULL,
  `columna1` varchar(100) DEFAULT NULL,
  `codigo_patrimonial` varchar(100) DEFAULT NULL,
  `inventario_2021` varchar(50) DEFAULT NULL,
  `inventario_2022` varchar(50) DEFAULT NULL,
  `inventario_2023` varchar(50) DEFAULT NULL,
  `inventario_2024` varchar(50) DEFAULT NULL,
  `inventario_2025` varchar(50) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `sustento_documentario_diagnostico` varchar(255) DEFAULT NULL,
  `sustento_documento_pcs_alquiladas` varchar(255) DEFAULT NULL,
  `situacion_actual` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `inventario_general`
--

INSERT INTO `inventario_general` (`id`, `sede_municipal`, `gerencia_subgerencia`, `tipo_bien`, `hostname`, `direccion_ip`, `usuario_responsable`, `disco_duro_ssd`, `tipo_procesador`, `condicion_equipo`, `repotenciado`, `ram`, `sistema_operativo`, `mac_address`, `conectividad_red`, `diagnostico`, `credencial`, `modelo_cpu`, `columna1`, `codigo_patrimonial`, `inventario_2021`, `inventario_2022`, `inventario_2023`, `inventario_2024`, `inventario_2025`, `observacion`, `sustento_documentario_diagnostico`, `sustento_documento_pcs_alquiladas`, `situacion_actual`, `fecha_registro`) VALUES
(1, 'Palacio Municipal', 'Oficina de Tecnologías de la Información y Gobierno Digital', 'PC', 'GAMBIENTAL12', '192.168.1.199', 'Jefrin Joseph Cochola Fabian', 'SSD 256GB + HDD 250GB', 'CORE I5 10TH', 'Bueno', 'No', '8GB', 'WINDOWS 10 PRO', 'D8-5R-1B-46-8C', 'SI', 'EL CPU ESTA OPERATIVO ', 'Jcochola', 'DELL', 'S/N', '740899560068', '2239', '2042', '1073', 'S/COD', '236425', 'S/N', 'S/N', 'S/N', 'S/N', '2026-08-28 21:57:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_impresoras`
--

CREATE TABLE `inventario_impresoras` (
  `id` int(11) NOT NULL,
  `sede` varchar(100) DEFAULT NULL,
  `gerencia_subgerencia` varchar(150) DEFAULT NULL,
  `usuario_responsable` varchar(150) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `conexion_red` varchar(10) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `codigo_etiqueta` varchar(50) DEFAULT NULL,
  `codigo_patrimonial` varchar(100) DEFAULT NULL,
  `inv_2025` varchar(50) DEFAULT NULL,
  `observaciones` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_perifericos`
--

CREATE TABLE `inventario_perifericos` (
  `id` int(11) NOT NULL,
  `responsable` varchar(150) DEFAULT NULL,
  `sede` varchar(150) DEFAULT NULL,
  `area` varchar(150) DEFAULT NULL,
  `usuario_responsable` varchar(150) DEFAULT NULL,
  `tipo_bien` varchar(100) DEFAULT NULL,
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `serie` varchar(100) DEFAULT NULL,
  `estado` varchar(50) DEFAULT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `condicion` varchar(50) DEFAULT NULL,
  `codigo_patrimonial` varchar(100) DEFAULT NULL,
  `inventario_2021` varchar(50) DEFAULT NULL,
  `inventario_2022` varchar(50) DEFAULT NULL,
  `inventario_2023` varchar(50) DEFAULT NULL,
  `inventario_2024` varchar(50) DEFAULT NULL,
  `inventario_2025` varchar(50) DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `mac` varchar(50) DEFAULT NULL,
  `columna1` varchar(100) DEFAULT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `inventario_telefonos`
--

CREATE TABLE `inventario_telefonos` (
  `id` int(11) NOT NULL,
  `gerencia_subgerencia` varchar(150) DEFAULT NULL,
  `bien` varchar(50) DEFAULT 'Teléfono IP',
  `marca` varchar(100) DEFAULT NULL,
  `modelo` varchar(100) DEFAULT NULL,
  `cod_interno` varchar(50) DEFAULT NULL,
  `cod_patrimonial` varchar(100) DEFAULT NULL,
  `inv_2023` varchar(50) DEFAULT NULL,
  `inv_2025` varchar(50) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('Administrador','Sistemas') NOT NULL DEFAULT 'Sistemas',
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `creado_en`) VALUES
(1, 'Administrador TI', 'admin@muni.gob.pe', '$2y$10$e8pK.KxR8x7g7P5k.u3N.e.X4P7C7B7P7C7B7P7C7B7P7C7B7P7C.', 'Administrador', '2026-08-28 13:47:25'),
(2, 'Soporte Tecnico', 'sistemas@muni.gob.pe', '$2y$10$e8pK.KxR8x7g7P5k.u3N.e.X4P7C7B7P7C7B7P7C7B7P7C7B7P7C.', 'Sistemas', '2026-08-28 13:47:25');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `inventario_computo`
--
ALTER TABLE `inventario_computo`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_desuso`
--
ALTER TABLE `inventario_desuso`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_especifico`
--
ALTER TABLE `inventario_especifico`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_estabilizadores`
--
ALTER TABLE `inventario_estabilizadores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_general`
--
ALTER TABLE `inventario_general`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_impresoras`
--
ALTER TABLE `inventario_impresoras`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_perifericos`
--
ALTER TABLE `inventario_perifericos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `inventario_telefonos`
--
ALTER TABLE `inventario_telefonos`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `inventario_computo`
--
ALTER TABLE `inventario_computo`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_desuso`
--
ALTER TABLE `inventario_desuso`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_especifico`
--
ALTER TABLE `inventario_especifico`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_estabilizadores`
--
ALTER TABLE `inventario_estabilizadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_general`
--
ALTER TABLE `inventario_general`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `inventario_impresoras`
--
ALTER TABLE `inventario_impresoras`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_perifericos`
--
ALTER TABLE `inventario_perifericos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `inventario_telefonos`
--
ALTER TABLE `inventario_telefonos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
