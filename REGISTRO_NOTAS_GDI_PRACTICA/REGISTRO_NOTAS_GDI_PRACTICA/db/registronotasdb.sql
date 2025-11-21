-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-11-2025 a las 20:53:32
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `registronotasdb`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `competencia`
--

CREATE TABLE `competencia` (
  `id_competencia` int(11) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `codigo_sesion` int(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `competencia`
--

INSERT INTO `competencia` (`id_competencia`, `descripcion`, `codigo_sesion`) VALUES
(1, 'Resuelve problemas de cantidad', 2001),
(2, 'Se comunica oralmente y por escrito en su lengua materna', 2002),
(3, 'Explica el mundo físico, biológico y tecnológico', 2003),
(4, 'Gestiona responsablemente su convivencia y ciudadanía', 2004),
(5, 'Construye su identidad como persona digna', 2005),
(6, 'Crea proyectos desde las artes', 2006);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `criterio`
--

CREATE TABLE `criterio` (
  `id_criterio` int(11) NOT NULL,
  `descripcion` varchar(150) DEFAULT NULL,
  `codigo_listacot` int(6) DEFAULT NULL,
  `activo` tinyint(1) DEFAULT 1,
  `orden` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `criterio`
--

INSERT INTO `criterio` (`id_criterio`, `descripcion`, `codigo_listacot`, `activo`, `orden`) VALUES
(1, 'Aplica correctamente los procedimientos de cálculo', 3001, 1, 1),
(2, 'Interpreta el significado de los resultados', 3001, 1, 2),
(3, 'Redacta oraciones coherentes', 3002, 1, 3),
(4, 'Usa conectores apropiadamente', 3002, 1, 4),
(5, 'Reconoce componentes del ecosistema', 3003, 1, 5),
(6, 'Analiza relaciones entre seres vivos', 3003, 1, 6),
(7, 'Participa activamente en debates grupales', 3004, 1, 7),
(8, 'Respeta las opiniones de los demás', 3004, 1, 8),
(9, 'Explica valores religiosos en ejemplos cotidianos', 3005, 1, 9),
(10, 'Demuestra actitudes de respeto y solidaridad', 3005, 1, 10),
(11, 'Utiliza técnicas artísticas adecuadamente', 3006, 1, 11),
(12, 'Expresa emociones mediante el color y forma', 3006, 1, 12);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `curso`
--

CREATE TABLE `curso` (
  `codigo_curso` int(6) NOT NULL,
  `nombre` varchar(100) DEFAULT NULL,
  `DNIdocente` varchar(8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `curso`
--

INSERT INTO `curso` (`codigo_curso`, `nombre`, `DNIdocente`) VALUES
(1001, 'MATEMÁTICA', '45678912'),
(1002, 'COMUNICACIÓN', '45678912'),
(1003, 'CIENCIA Y TECNOLOGÍA', '45678912'),
(1004, 'PERSONAL SOCIAL', '45678912'),
(1005, 'EDUCACIÓN RELIGIOSA', '45678912'),
(1006, 'ARTE Y CULTURA', '45678912');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `docente`
--

CREATE TABLE `docente` (
  `DNIdocente` varchar(8) NOT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `correo` varchar(100) DEFAULT NULL,
  `contrasena` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `docente`
--

INSERT INTO `docente` (`DNIdocente`, `nombres`, `apellidos`, `correo`, `contrasena`) VALUES
('45678912', 'Livia Zelio', 'Ponce', 'livia.ponce@ie606.edu.pe', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiante`
--

CREATE TABLE `estudiante` (
  `DNIestudiante` varchar(8) NOT NULL,
  `nombres` varchar(100) DEFAULT NULL,
  `apellidos` varchar(100) DEFAULT NULL,
  `id_grado_seccion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `estudiante`
--

INSERT INTO `estudiante` (`DNIestudiante`, `nombres`, `apellidos`, `id_grado_seccion`) VALUES
('29032045', 'Leonardo', 'Challco Suni', 1),
('29271304', 'Ariane Brigitte', 'Chany Vargas', 1),
('29297151', 'Michelle Camila', 'Livisi Ccari', 1),
('29341382', 'Mileth Maricrist Keisha', 'Jove Chipana', 1),
('29447469', 'Ruth Kamila', 'Arana Quispe', 1),
('29530668', 'Helen Melissa', 'Canahuiere Gómez', 1),
('29591002', 'Mileydy Aime', 'Luque Idme', 1),
('29606969', 'Roys Giovanny', 'Hancco Ccuno', 1),
('29950812', 'Jhosimar Albert', 'Mamani Mamani', 1),
('30191114', 'Luiz Gustavo', 'Canaza Quispe', 1),
('30342696', 'Ximena Neyruth', 'Arapa Quispe', 1),
('30419565', 'David Miguel', 'Benito Soto', 1),
('30472809', 'Gabriel Raúl', 'Apaza Ramos', 1),
('30499695', 'Dafne Melany', 'Choquemaqui Santamaría', 1),
('30500956', 'Daylin Luana Dalai', 'Hancco Valero', 1),
('30766614', 'Fátima Valeria', 'Condori Montalvo', 1),
('30772679', 'Franco Abel', 'Condori Mamani', 1),
('30832267', 'Aleyda Jennifer', 'Canaza Zúñiga', 1),
('32246360', 'Jahzeel Duván', 'Ccañihua Ccolque', 1),
('32272381', 'Taylor Jadiel Félix', 'Calla Machaca', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluacion`
--

CREATE TABLE `evaluacion` (
  `DNIestudiante` varchar(8) NOT NULL,
  `codigo_listacot` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `evaluacion`
--

INSERT INTO `evaluacion` (`DNIestudiante`, `codigo_listacot`) VALUES
('29032045', 3001),
('29271304', 3001),
('29297151', 3001),
('29341382', 3001),
('29447469', 3001),
('29530668', 3001),
('29591002', 3001),
('29606969', 3001),
('29950812', 3001),
('30191114', 3001),
('30342696', 3001),
('30419565', 3001),
('30472809', 3001),
('30499695', 3001),
('30500956', 3001),
('30766614', 3001),
('30772679', 3001),
('30832267', 3001),
('32246360', 3001),
('32272381', 3001);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evaluacion_detalle`
--

CREATE TABLE `evaluacion_detalle` (
  `id_evaluacion_detalle` int(11) NOT NULL,
  `DNIestudiante` varchar(8) DEFAULT NULL,
  `codigo_listacot` int(6) DEFAULT NULL,
  `id_criterio` int(11) DEFAULT NULL,
  `cumplido` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `fecha_evaluacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gradoseccion`
--

CREATE TABLE `gradoseccion` (
  `id_grado_seccion` int(11) NOT NULL,
  `grado` varchar(20) DEFAULT NULL,
  `seccion` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `gradoseccion`
--

INSERT INTO `gradoseccion` (`id_grado_seccion`, `grado`, `seccion`) VALUES
(1, '6°', 'B');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `lista_de_cotejo`
--

CREATE TABLE `lista_de_cotejo` (
  `codigo_listacot` int(6) NOT NULL,
  `proposito` varchar(150) DEFAULT NULL,
  `codigo_sesion` int(6) DEFAULT NULL,
  `id_grado_seccion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `lista_de_cotejo`
--

INSERT INTO `lista_de_cotejo` (`codigo_listacot`, `proposito`, `codigo_sesion`, `id_grado_seccion`) VALUES
(3001, 'Evaluar la resolución correcta de fracciones', 2001, 1),
(3002, 'Evaluar coherencia en la redacción de textos', 2002, 1),
(3003, 'Evaluar la comprensión de ecosistemas', 2003, 1),
(3004, 'Evaluar la participación y reflexión social', 2004, 1),
(3005, 'Evaluar valores y actitudes religiosas', 2005, 1),
(3006, 'Evaluar la creatividad y expresión artística', 2006, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notas`
--

CREATE TABLE `notas` (
  `DNIestudiante` varchar(8) NOT NULL,
  `codigo_sesion` int(6) NOT NULL,
  `observaciones` varchar(200) DEFAULT NULL,
  `nota_final` varchar(2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `sesion_de_aprendizaje`
--

CREATE TABLE `sesion_de_aprendizaje` (
  `codigo_sesion` int(6) NOT NULL,
  `fecha` date DEFAULT NULL,
  `duracion` int(11) DEFAULT NULL,
  `titulo` varchar(150) DEFAULT NULL,
  `evidencia_aprendizaje` varchar(150) DEFAULT NULL,
  `codigo_curso` int(6) DEFAULT NULL,
  `id_grado_seccion` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `sesion_de_aprendizaje`
--

INSERT INTO `sesion_de_aprendizaje` (`codigo_sesion`, `fecha`, `duracion`, `titulo`, `evidencia_aprendizaje`, `codigo_curso`, `id_grado_seccion`) VALUES
(2001, '2025-03-10', 90, 'Aplicamos estrategias con fracciones', 'Ficha de problemas resueltos', 1001, 1),
(2002, '2025-03-12', 90, 'Redactamos textos narrativos coherentes', 'Texto redactado en cuaderno', 1002, 1),
(2003, '2025-03-14', 90, 'Identificamos ecosistemas y su importancia', 'Mapa conceptual de ecosistemas', 1003, 1),
(2004, '2025-03-16', 90, 'Reflexionamos sobre la historia del Perú', 'Línea de tiempo histórica', 1004, 1),
(2005, '2025-03-18', 90, 'Analizamos valores religiosos y convivencia', 'Ensayo sobre la solidaridad', 1005, 1),
(2006, '2025-03-20', 90, 'Expresamos emociones a través del arte', 'Dibujo en técnica libre', 1006, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `telefono_docente`
--

CREATE TABLE `telefono_docente` (
  `DNIdocente` varchar(8) NOT NULL,
  `telefono` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `telefono_docente`
--

INSERT INTO `telefono_docente` (`DNIdocente`, `telefono`) VALUES
('45678912', '951234567');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `competencia`
--
ALTER TABLE `competencia`
  ADD PRIMARY KEY (`id_competencia`),
  ADD KEY `codigo_sesion` (`codigo_sesion`);

--
-- Indices de la tabla `criterio`
--
ALTER TABLE `criterio`
  ADD PRIMARY KEY (`id_criterio`),
  ADD KEY `criterio_ibfk_1` (`codigo_listacot`);

--
-- Indices de la tabla `curso`
--
ALTER TABLE `curso`
  ADD PRIMARY KEY (`codigo_curso`),
  ADD KEY `DNIdocente` (`DNIdocente`);

--
-- Indices de la tabla `docente`
--
ALTER TABLE `docente`
  ADD PRIMARY KEY (`DNIdocente`),
  ADD UNIQUE KEY `correo` (`correo`);

--
-- Indices de la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD PRIMARY KEY (`DNIestudiante`),
  ADD KEY `id_grado_seccion` (`id_grado_seccion`);

--
-- Indices de la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  ADD PRIMARY KEY (`DNIestudiante`,`codigo_listacot`),
  ADD KEY `codigo_listacot` (`codigo_listacot`);

--
-- Indices de la tabla `evaluacion_detalle`
--
ALTER TABLE `evaluacion_detalle`
  ADD PRIMARY KEY (`id_evaluacion_detalle`),
  ADD UNIQUE KEY `unique_evaluacion` (`DNIestudiante`,`codigo_listacot`,`id_criterio`),
  ADD KEY `codigo_listacot` (`codigo_listacot`),
  ADD KEY `id_criterio` (`id_criterio`);

--
-- Indices de la tabla `gradoseccion`
--
ALTER TABLE `gradoseccion`
  ADD PRIMARY KEY (`id_grado_seccion`);

--
-- Indices de la tabla `lista_de_cotejo`
--
ALTER TABLE `lista_de_cotejo`
  ADD PRIMARY KEY (`codigo_listacot`),
  ADD KEY `id_grado_seccion` (`id_grado_seccion`),
  ADD KEY `lista_de_cotejo_ibfk_1` (`codigo_sesion`);

--
-- Indices de la tabla `notas`
--
ALTER TABLE `notas`
  ADD PRIMARY KEY (`DNIestudiante`,`codigo_sesion`),
  ADD KEY `codigo_sesion` (`codigo_sesion`);

--
-- Indices de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `sesion_de_aprendizaje`
--
ALTER TABLE `sesion_de_aprendizaje`
  ADD PRIMARY KEY (`codigo_sesion`),
  ADD KEY `codigo_curso` (`codigo_curso`),
  ADD KEY `id_grado_seccion` (`id_grado_seccion`);

--
-- Indices de la tabla `telefono_docente`
--
ALTER TABLE `telefono_docente`
  ADD PRIMARY KEY (`DNIdocente`,`telefono`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `competencia`
--
ALTER TABLE `competencia`
  MODIFY `id_competencia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `criterio`
--
ALTER TABLE `criterio`
  MODIFY `id_criterio` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT de la tabla `evaluacion_detalle`
--
ALTER TABLE `evaluacion_detalle`
  MODIFY `id_evaluacion_detalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gradoseccion`
--
ALTER TABLE `gradoseccion`
  MODIFY `id_grado_seccion` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `competencia`
--
ALTER TABLE `competencia`
  ADD CONSTRAINT `competencia_ibfk_1` FOREIGN KEY (`codigo_sesion`) REFERENCES `sesion_de_aprendizaje` (`codigo_sesion`);

--
-- Filtros para la tabla `criterio`
--
ALTER TABLE `criterio`
  ADD CONSTRAINT `criterio_ibfk_1` FOREIGN KEY (`codigo_listacot`) REFERENCES `lista_de_cotejo` (`codigo_listacot`) ON DELETE CASCADE;

--
-- Filtros para la tabla `curso`
--
ALTER TABLE `curso`
  ADD CONSTRAINT `curso_ibfk_1` FOREIGN KEY (`DNIdocente`) REFERENCES `docente` (`DNIdocente`);

--
-- Filtros para la tabla `estudiante`
--
ALTER TABLE `estudiante`
  ADD CONSTRAINT `estudiante_ibfk_1` FOREIGN KEY (`id_grado_seccion`) REFERENCES `gradoseccion` (`id_grado_seccion`);

--
-- Filtros para la tabla `evaluacion`
--
ALTER TABLE `evaluacion`
  ADD CONSTRAINT `evaluacion_ibfk_1` FOREIGN KEY (`DNIestudiante`) REFERENCES `estudiante` (`DNIestudiante`),
  ADD CONSTRAINT `evaluacion_ibfk_2` FOREIGN KEY (`codigo_listacot`) REFERENCES `lista_de_cotejo` (`codigo_listacot`);

--
-- Filtros para la tabla `evaluacion_detalle`
--
ALTER TABLE `evaluacion_detalle`
  ADD CONSTRAINT `evaluacion_detalle_ibfk_1` FOREIGN KEY (`DNIestudiante`) REFERENCES `estudiante` (`DNIestudiante`),
  ADD CONSTRAINT `evaluacion_detalle_ibfk_2` FOREIGN KEY (`codigo_listacot`) REFERENCES `lista_de_cotejo` (`codigo_listacot`),
  ADD CONSTRAINT `evaluacion_detalle_ibfk_3` FOREIGN KEY (`id_criterio`) REFERENCES `criterio` (`id_criterio`);

--
-- Filtros para la tabla `lista_de_cotejo`
--
ALTER TABLE `lista_de_cotejo`
  ADD CONSTRAINT `lista_de_cotejo_ibfk_1` FOREIGN KEY (`codigo_sesion`) REFERENCES `sesion_de_aprendizaje` (`codigo_sesion`) ON DELETE CASCADE,
  ADD CONSTRAINT `lista_de_cotejo_ibfk_2` FOREIGN KEY (`id_grado_seccion`) REFERENCES `gradoseccion` (`id_grado_seccion`);

--
-- Filtros para la tabla `notas`
--
ALTER TABLE `notas`
  ADD CONSTRAINT `notas_ibfk_1` FOREIGN KEY (`DNIestudiante`) REFERENCES `estudiante` (`DNIestudiante`),
  ADD CONSTRAINT `notas_ibfk_2` FOREIGN KEY (`codigo_sesion`) REFERENCES `sesion_de_aprendizaje` (`codigo_sesion`);

--
-- Filtros para la tabla `sesion_de_aprendizaje`
--
ALTER TABLE `sesion_de_aprendizaje`
  ADD CONSTRAINT `sesion_de_aprendizaje_ibfk_1` FOREIGN KEY (`codigo_curso`) REFERENCES `curso` (`codigo_curso`),
  ADD CONSTRAINT `sesion_de_aprendizaje_ibfk_2` FOREIGN KEY (`id_grado_seccion`) REFERENCES `gradoseccion` (`id_grado_seccion`);

--
-- Filtros para la tabla `telefono_docente`
--
ALTER TABLE `telefono_docente`
  ADD CONSTRAINT `telefono_docente_ibfk_1` FOREIGN KEY (`DNIdocente`) REFERENCES `docente` (`DNIdocente`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
