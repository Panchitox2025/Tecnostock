-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 22-11-2026 a las 23:07:51
-- Versión del servidor: 10.11.16-MariaDB
-- Versión de PHP: 8.5.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `tecnostock`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categoria`
--

CREATE TABLE `categoria` (
  `id_categoria` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(250) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla de Categoria de productos';

--
-- Volcado de datos para la tabla `categoria`
--

INSERT INTO `categoria` (`id_categoria`, `nombre`, `descripcion`, `estado`) VALUES
(1, 'Audio', 'Audífonos y parlantes', 1),
(2, 'Cables', 'Cables y adaptadores', 1),
(3, 'Periféricos', 'Mouse y teclados', 1),
(4, 'Energía', 'Cargadores y baterías', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `movimiento`
--

CREATE TABLE `movimiento` (
  `id_movimiento` int(11) NOT NULL,
  `fecha_hora` datetime NOT NULL DEFAULT current_timestamp(),
  `tipo` varchar(10) NOT NULL,
  `cantidad` int(10) UNSIGNED NOT NULL,
  `observacion` varchar(255) DEFAULT NULL,
  `id_producto` int(11) NOT NULL,
  `id_usuario` int(10) UNSIGNED NOT NULL
) ;

--
-- Volcado de datos para la tabla `movimiento`
--

INSERT INTO `movimiento` (`id_movimiento`, `fecha_hora`, `tipo`, `cantidad`, `observacion`, `id_producto`, `id_usuario`) VALUES
(1, '2026-11-22 13:01:36', 'entrada', 10, NULL, 1, 1),
(2, '2026-11-22 13:02:14', 'salida', 10, NULL, 3, 1),
(3, '2026-11-22 14:10:49', 'entrada', 20, NULL, 3, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `producto`
--

CREATE TABLE `producto` (
  `id_producto` int(11) NOT NULL,
  `codigo` varchar(30) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` varchar(255) NOT NULL,
  `precio` decimal(10,2) NOT NULL,
  `stock_actual` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `stock_minimo` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `estado` tinyint(4) NOT NULL DEFAULT 1,
  `id_categoria` int(11) NOT NULL
) ;

--
-- Volcado de datos para la tabla `producto`
--

INSERT INTO `producto` (`id_producto`, `codigo`, `nombre`, `descripcion`, `precio`, `stock_actual`, `stock_minimo`, `estado`, `id_categoria`) VALUES
(1, 'AUD-001', 'Audifonos Bluetooth', 'Inalámbricos y con cancelación de ruido', 25990.00, 25, 5, 1, 1),
(2, 'CAB-001', 'Cable USB-C 1m', 'Carga rapida y datos', 4990.00, 40, 10, 1, 2),
(3, 'PER-001', 'Mouse inalambrico', 'Ergonomico, 1600 DPI', 13000.00, 20, 10, 1, 3),
(4, 'ENE-001', 'Power bank 10000 mAh', 'Dos salidas USB', 19990.00, 8, 4, 1, 4),
(5, 'PER-0023', 'Mouse inalambrico', '1600 dpi', 25000.00, 70, 20, 1, 3);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuario`
--

CREATE TABLE `usuario` (
  `id_usuario` int(10) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `clave` varchar(255) NOT NULL,
  `estado` tinyint(4) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuario`
--

INSERT INTO `usuario` (`id_usuario`, `nombre`, `correo`, `clave`, `estado`) VALUES
(1, 'Jefe', 'admin@tecnostock.cl', '$2y$10$qEttkY4U776h9PNNPF1uzuxBX70ODTnh3w5zxzF5RH6U52J50pXUu', 1);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categoria`
--
ALTER TABLE `categoria`
  ADD PRIMARY KEY (`id_categoria`),
  ADD UNIQUE KEY `unico` (`nombre`);

--
-- Indices de la tabla `movimiento`
--
ALTER TABLE `movimiento`
  ADD PRIMARY KEY (`id_movimiento`),
  ADD KEY `id_producto` (`id_producto`),
  ADD KEY `id_usuario` (`id_usuario`);

--
-- Indices de la tabla `producto`
--
ALTER TABLE `producto`
  ADD PRIMARY KEY (`id_producto`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `fk_producto_categoria` (`id_categoria`);

--
-- Indices de la tabla `usuario`
--
ALTER TABLE `usuario`
  ADD PRIMARY KEY (`id_usuario`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categoria`
--
ALTER TABLE `categoria`
  MODIFY `id_categoria` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `movimiento`
--
ALTER TABLE `movimiento`
  MODIFY `id_movimiento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `producto`
--
ALTER TABLE `producto`
  MODIFY `id_producto` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuario`
--
ALTER TABLE `usuario`
  MODIFY `id_usuario` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `movimiento`
--
ALTER TABLE `movimiento`
  ADD CONSTRAINT `movimiento_ibfk_1` FOREIGN KEY (`id_producto`) REFERENCES `producto` (`id_producto`),
  ADD CONSTRAINT `movimiento_ibfk_2` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id_usuario`);

--
-- Filtros para la tabla `producto`
--
ALTER TABLE `producto`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`id_categoria`) REFERENCES `categoria` (`id_categoria`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
