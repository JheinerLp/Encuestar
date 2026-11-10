-- =====================================================================
-- DATOS INICIALES Y CASOS DE PRUEBA (SEED)
-- =====================================================================
USE `encuesta_db`;

-- 1. PUNTOS DE ATENCIÓN ESTÁTICOS (Comunas 1, 6, 7, 13, 14, 20, 21 y 17 Principal)
INSERT INTO `puntos_atencion` (`id`, `codigo`, `nombre`, `comuna`, `zona`, `barrio`, `direccion`, `telefono`, `latitud`, `longitud`, `es_principal`, `descripcion`) VALUES
(1, 'PA-C01', 'Punto de Atención Terrón Colorado', 1, 'urbana', 'Terrón Colorado', 'Av. 4 Oeste # 19-35', '(602) 893-4101', 3.45420000, -76.55830000, 0, 'Atención zona ladera noroccidental'),
(2, 'PA-C06', 'Punto de Atención Los Mangos - Petecuy', 6, 'urbana', 'Petecuy II', 'Cra. 1D # 73-20', '(602) 441-2306', 3.48620000, -76.49510000, 0, 'Atención sector norte'),
(3, 'PA-C07', 'Punto de Atención Alfonso López', 7, 'urbana', 'Alfonso López I', 'Cl. 70 # 7T Bis-12', '(602) 438-9907', 3.46870000, -76.48890000, 0, 'Atención sector nororiente'),
(4, 'PA-C13', 'Punto de Atención El Vergel - Oriente', 13, 'urbana', 'El Vergel', 'Cra. 32A # 48-15', '(602) 328-5513', 3.42150000, -76.49900000, 0, 'Atención sector oriente distrito de Aguablanca'),
(5, 'PA-C14', 'Punto de Atención Manuela Beltrán', 14, 'urbana', 'Manuela Beltrán', 'Cra. 26K # 108-04', '(602) 420-1414', 3.41900000, -76.48500000, 0, 'Atención sector oriente suroriental'),
(6, 'PA-C20', 'Punto de Atención Siloé', 20, 'urbana', 'Siloé', 'Cl. 1 Oeste # 50-10', '(602) 552-8020', 3.41800000, -76.55400000, 0, 'Atención zona de ladera suroccidental'),
(7, 'PA-C21', 'Punto de Atención Desepaz', 21, 'urbana', 'Desepaz', 'Cra. 25 # 120-00', '(602) 429-7721', 3.43500000, -76.47200000, 0, 'Atención sector oriente extremo'),
(8, 'PA-C17', 'Oficina Principal y Punto de Atención Caney / Pasoancho', 17, 'urbana', 'El Caney', 'Cl. 42 # 85-30', '(602) 330-1700', 3.38500000, -76.53300000, 1, 'Sede Central de Coordinación y Punto de Atención');

-- 2. USUARIOS CON LOS 5 PERFILES REQUERIDOS
-- Contraseñas:
-- admin / admin123
-- ventanilla1 / ventanilla123
-- enrutador1 / enrutador123
-- encuestador1 / encuestador123
-- calidad1 / calidad123
INSERT INTO `usuarios` (`id`, `cedula`, `nombre_completo`, `username`, `password_hash`, `email`, `telefono`, `rol`, `punto_atencion_id`, `antiguedad_anios`, `activo`) VALUES
(1, '1144123001', 'Ing. Carlos Mendoza (Admin)', 'admin', '$2y$10$xZ2wn27a7AsxMEHC0dSvYuBfjsd8ynMPm79ka6ps6mcyyEHJNUm2G', 'administrador@encuestascali.gov.co', '3157890001', 'administrador', 8, 5, 1),
(2, '1144123002', 'Lina Marcela Rentería (Ventanilla Sede 17)', 'ventanilla1', '$2y$10$XFiT26rhjPsSJOip7pjU8Om8h2pOngdzCUHYqqUSWjZz6mOH2qBi.', 'ventanilla.principal@encuestascali.gov.co', '3167890002', 'ventanilla', 8, 3, 1),
(3, '1144123003', 'John Alexander Riascos (Enrutador Maestro)', 'enrutador1', '$2y$10$NjEZWgNfZWlgS2HocilTeuphl1DeQz9WJNpuP.EOSyxtaEpCCgGw.', 'enrutamiento@encuestascali.gov.co', '3177890003', 'enrutador', 8, 7, 1),
(4, '1144123004', 'Andrés Felipe Caicedo (Encuestador Campo)', 'encuestador1', '$2y$10$mUN9Das4xypE.tdFkt3nt.e0n6rpzTmS4wSSQhbSOQAYzwUBXd59q', 'andres.caicedo@encuestascali.gov.co', '3187890004', 'encuestador', 8, 4, 1),
(5, '1144123005', 'Dra. Sandra Milena Ortiz (Líder Calidad)', 'calidad1', '$2y$10$4BDEYeD/1KtvnDQglcdOW.mdqwoZ.QBfA9NByaXaAZ0MfvthBFbo2', 'calidad.auditoria@encuestascali.gov.co', '3197890005', 'calidad', 8, 4, 1),
-- Encuestadores adicionales de la planta de 20 para asignaciones
(6, '1144123006', 'Julián David Morales (Encuestador)', 'encuestador2', '$2y$10$mUN9Das4xypE.tdFkt3nt.e0n6rpzTmS4wSSQhbSOQAYzwUBXd59q', 'julian.morales@encuestascali.gov.co', '3104560006', 'encuestador', 4, 4, 1),
(7, '1144123007', 'Diana Patricia Cuero (Encuestadora)', 'encuestador3', '$2y$10$mUN9Das4xypE.tdFkt3nt.e0n6rpzTmS4wSSQhbSOQAYzwUBXd59q', 'diana.cuero@encuestascali.gov.co', '3124560007', 'encuestador', 6, 4, 1),
(8, '1144123008', 'Hernán Darío Osorio (Ventanilla Comuna 13)', 'ventanilla2', '$2y$10$XFiT26rhjPsSJOip7pjU8Om8h2pOngdzCUHYqqUSWjZz6mOH2qBi.', 'ventanilla.c13@encuestascali.gov.co', '3114560008', 'ventanilla', 4, 2, 1);

-- 3. SOLICITUDES DE EJEMPLO (Incluye recientes, en proceso, críticas y vencidas para probar SLA 15 días)
INSERT INTO `solicitudes` (`id`, `numero_radicado`, `punto_atencion_id`, `usuario_ventanilla_id`, `nombres`, `apellidos`, `tipo_documento`, `documento_identidad`, `zona_tipo`, `comuna`, `corregimiento`, `barrio`, `direccion`, `celular`, `latitud`, `longitud`, `fecha_radicacion`, `fecha_limite_15dias`, `estado`, `observaciones`) VALUES
(1, 'SOL-2026-0001', 8, 2, 'María Fernanda', 'Zapata Ospina', 'CC', '31876543', 'urbana', 17, NULL, 'El Caney', 'Cl. 44 # 83-12 Casa 4', '3165551020', 3.38620000, -76.53180000, DATE_SUB(NOW(), INTERVAL 2 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 2 DAY), INTERVAL 15 DAY), 'enrutada', 'Usuario solicita visita matutina.'),
(2, 'SOL-2026-0002', 8, 2, 'Jairo Hernando', 'Gómez Vidal', 'CC', '16789456', 'urbana', 17, NULL, 'Primero de Mayo', 'Cra. 56 # 13E-45', '3174442030', 3.40200000, -76.53400000, DATE_SUB(NOW(), INTERVAL 1 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 1 DAY), INTERVAL 15 DAY), 'enrutada', 'Madre cabeza de hogar con 2 hijos menores.'),
(3, 'SOL-2026-0003', 4, 8, 'Esperanza', 'Mosquera Balanta', 'CC', '29456123', 'urbana', 13, NULL, 'El Vergel', 'Cra. 33 # 46-20', '3183334050', 3.42250000, -76.49800000, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 'radicada', 'Presenta copia de recibo de servicios públicos.'),
(4, 'SOL-2026-0004', 4, 8, 'Wilson Antonio', 'Carabalí Mina', 'CC', '94321678', 'urbana', 14, NULL, 'Manuela Beltrán', 'Cl. 107 # 26J-15', '3102223344', 3.41850000, -76.48400000, NOW(), DATE_ADD(NOW(), INTERVAL 15 DAY), 'radicada', 'Interesado en subsidio productivo.'),
(5, 'SOL-2026-0005', 1, 2, 'Rosa Elvira', 'Gutiérrez Peña', 'CC', '66987123', 'urbana', 1, NULL, 'Terrón Colorado', 'Av. 4 Oeste # 21-10', '3119998877', 3.45500000, -76.55900000, DATE_SUB(NOW(), INTERVAL 11 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 11 DAY), INTERVAL 15 DAY), 'radicada', 'ALERTA: Faltan 4 días para vencer plazo institucional.'),
(6, 'SOL-2026-0006', 6, 2, 'Rigoberto', 'Muñoz Narváez', 'CC', '14234567', 'urbana', 20, NULL, 'Siloé', 'Cl. 2 Oeste # 52-04', '3157776655', 3.41700000, -76.55600000, DATE_SUB(NOW(), INTERVAL 17 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 17 DAY), INTERVAL 15 DAY), 'vencida', 'FUERA DE PLAZO (>15 días): Evidencia el problema histórico expuesto en el caso.'),
(7, 'SOL-2026-0007', 8, 2, 'Carmen Alicia', 'Blandón Ortiz', 'CC', '38765432', 'rural', NULL, 'Pance', 'Pance', 'Vereda San Francisco Km 4', '3146665544', 3.32800000, -76.56200000, DATE_SUB(NOW(), INTERVAL 6 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 6 DAY), INTERVAL 15 DAY), 'radicada', 'Zona rural - Dificultad de acceso, requiere traslado temprano.'),
(8, 'SOL-2026-0008', 8, 2, 'Víctor Manuel', 'Lozano Quintero', 'CC', '16890123', 'urbana', 17, NULL, 'Ciudad Capri', 'Cl. 10A # 78-19', '3138887766', 3.39100000, -76.53900000, DATE_SUB(NOW(), INTERVAL 4 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 4 DAY), INTERVAL 15 DAY), 'en_revision', 'Encuesta realizada, en mesa de revisión por equipo de calidad.'),
(9, 'SOL-2026-0009', 2, 2, 'Nury Stella', 'Valencia Cárdenas', 'CC', '31908765', 'urbana', 6, NULL, 'Petecuy I', 'Cra. 2A # 76-18', '3129991122', 3.48700000, -76.49600000, DATE_SUB(NOW(), INTERVAL 7 DAY), DATE_ADD(DATE_SUB(NOW(), INTERVAL 7 DAY), INTERVAL 15 DAY), 'aprobada_apto', 'Encuesta dictaminada: APTO para beneficio crediticio internacional.');

-- 4. HOJA DE RUTA DE EJEMPLO
INSERT INTO `hojas_ruta` (`id`, `codigo_hoja`, `enrutador_id`, `encuestador_id`, `punto_atencion_id`, `fecha_programada`, `camioneta_asignada`, `punto_encuentro`, `hora_salida`, `hora_recogida`, `total_encuestas`, `estado`, `observaciones_enrutador`) VALUES
(1, 'HR-2026-1108-01', 3, 4, 8, CURDATE(), 'Van 1 (Placa WCY-452)', 'Oficina Principal Pasoancho - Punto de partida', '07:00:00', '12:30:00', 2, 'entregada_a_encuestador', 'Ruta programada para zona sur Comuna 17. Recogida obligatoria 12:30 p.m. por seguridad.');

-- 5. ITEMS DE LA HOJA DE RUTA
INSERT INTO `hoja_ruta_items` (`id`, `hoja_ruta_id`, `solicitud_id`, `orden_visita`, `llamada_previa_realizada`, `fecha_llamada_previa`, `observacion_llamada`, `estado_visita`) VALUES
(1, 1, 1, 1, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Usuario contactado, confirmó disponibilidad a las 8:00 AM.', 'pendiente'),
(2, 1, 2, 2, 1, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Usuario confirmó estar en casa para visita a las 9:30 AM.', 'pendiente');

-- 6. ENCUESTAS REALIZADAS
INSERT INTO `encuestas` (`id`, `solicitud_id`, `hoja_ruta_id`, `encuestador_id`, `fecha_levantamiento`, `jefe_hogar_nombre`, `numero_integrantes`, `tiene_menores`, `menor_nombre`, `menor_fecha_nacimiento`, `menor_edad_calculada`, `ingreso_mensual`, `tipo_vivienda`, `estrato`, `servicios_basicos`, `motivo_prestamo_detalle`, `latitud_visita`, `longitud_visita`, `firma_digital`, `observaciones_encuestador`) VALUES
(1, 8, NULL, 4, DATE_SUB(NOW(), INTERVAL 1 DAY), 'Víctor Manuel Lozano Quintero', 4, 1, 'Mateo Lozano Gómez', '2016-04-12', 10, 1450000.00, 'arrendada', 2, 'Agua, Energía, Gas, Internet', 'Capital de trabajo para microempresa familiar de calzado en Cali.', 3.39100000, -76.53900000, 'Víctor Lozano Q. (Firma capturada)', 'Visita realizada satisfactoriamente con documentos en regla.'),
(2, 9, NULL, 4, DATE_SUB(NOW(), INTERVAL 3 DAY), 'Nury Stella Valencia Cárdenas', 3, 0, NULL, NULL, NULL, 1200000.00, 'propia', 1, 'Agua, Energía, Gas', 'Fortalecimiento de taller de confección y confecciones para exportación.', 3.48700000, -76.49600000, 'Nury S. Valencia (Firma capturada)', 'Vivienda verificada, cumple con requisitos de vulnerabilidad socioeconómica.');

-- 7. REVISIONES DE CALIDAD
INSERT INTO `revisiones_calidad` (`id`, `encuesta_id`, `usuario_calidad_id`, `fecha_revision`, `consistencia_barrio_comuna`, `consistencia_edad_menor`, `formulario_completo`, `identificacion_encuestador_valida`, `resultado_oficial`, `dictamen_justificacion`, `observaciones_auditoria`) VALUES
(1, 2, 5, DATE_SUB(NOW(), INTERVAL 2 DAY), 1, 1, 1, 1, 'apto', 'Cumple con el 100% de los criterios normativos, correlación geográfica exacta y vulnerabilidad comprobada.', 'Aprobado para envío al fondo del gobierno extranjero.');

-- 8. BITÁCORA DE AUDITORÍA
INSERT INTO `bitacora_eventos` (`usuario_id`, `rol_usuario`, `modulo`, `accion`, `detalle`, `ip_origen`) VALUES
(1, 'administrador', 'SISTEMA', 'INICIO_SISTEMA', 'Inicialización de la base de datos SIRCE Cali y carga de sedes estáticas.', '127.0.0.1'),
(2, 'ventanilla', 'VENTANILLA', 'RADICACION', 'Radicación presencial con georreferenciación de solicitud SOL-2026-0001.', '127.0.0.1'),
(3, 'enrutador', 'ENRUTAMIENTO', 'CREACION_HOJA_RUTA', 'Generación de hoja de ruta HR-2026-1108-01 asignada a encuestador 1.', '127.0.0.1');
