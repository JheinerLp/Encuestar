================================================================================
SISTEMA INTEGRADO DE RADICACIÓN, RUTEO Y CALIDAD DE ENCUESTAS (SIRCE CALI)
PROYECTO: DESARROLLO DE SOFTWARE II
DOCENTE: JOHN ROBERT CAUSIMANSE
UNIVERSIDAD DEL VALLE
================================================================================

1. INFORMACIÓN GENERAL Y URL DE LA APLICACIÓN WEB
--------------------------------------------------------------------------------
• Nombre del Sistema: SIRCE Cali (Sistema de Encuestas Domiciliarias)
• URL Local de Ejecución: http://localhost/Encuesta/
• Entorno de Ejecución: XAMPP (Apache 2.4, MariaDB 10.4 / MySQL, PHP 8.2+)
• Directorio de Archivos: c:\xampp\htdocs\Encuesta\
• Base de Datos: encuesta_db (MySQL / MariaDB en puerto 3306)

2. CUMPLIMIENTO RIGUROSO DE LOS 9 REQUERIMIENTOS DEL PROYECTO
--------------------------------------------------------------------------------
1. Login de usuario con validación de 5 perfiles obligatorios:
   - Administrador
   - Ventanilla
   - Enrutador
   - Encuestador
   - Calidad
   (Incluye barra de acceso rápido con 1-click para sustentación y evaluación).

2. Módulo de Ventanilla para atención y radicación presencial:
   - Captura y almacena: nombres, apellidos, barrio, comuna, número de celular
     y dirección.
   - Referencia la división sectorial oficial de Santiago de Cali:
     Comunas 1 a 22 con sus respectivos barrios oficiales, y los 15 Corregimientos
     rurales de Cali (Pance, Villacarmelo, La Buitrera, Pichindé, Felidia, etc.).

3. Georreferenciación de cada solicitud de encuesta:
   - Mapa interactivo con Leaflet y OpenStreetMap centrado en Cali.
   - Marcador arrastrable y selector interactivo con clic en el plano.
   - Captura y cálculo en tiempo real de coordenadas GPS (latitud y longitud WGS84).

4. Módulo de Enrutador con Bitácora de solicitudes:
   - Bitácora diaria en tiempo real de todas las solicitudes de encuestas
     generadas en el día en todos los puntos de atención de la ciudad.
   - Control de los 15 días calendario de plazo máximo con semáforo SLA
     (A tiempo, Alerta crítica, Vencido).

5. Puntos de atención estáticos desplegados en el mapa y en el sistema:
   - Ubicados fijamente en las comunas:
     * Comuna 1: Punto Terrón Colorado
     * Comuna 6: Punto Los Mangos - Petecuy
     * Comuna 7: Punto Alfonso López
     * Comuna 13: Punto El Vergel - Oriente
     * Comuna 14: Punto Manuela Beltrán
     * Comuna 20: Punto Siloé
     * Comuna 21: Punto Desepaz
     * Comuna 17: Oficina Principal Pasoancho / El Caney (Atención y Despacho)

6. Generación de Hoja de Ruta con carga de trabajo (hasta 20 encuestas):
   - Asignación a encuestador de la planta de 20 encuestadores.
   - Asignación de vehículos (Van 1 y Van 2).
   - Control de jornada territorial de 7:00 a.m. a 12:00 p.m. y recogida
     obligatoria en buseta a las 12:30 p.m. por motivos de seguridad.
   - Parámetros de control: orden de visita, punto de encuentro, observaciones.

7. Entrega matutina de hoja de ruta en punto de atención:
   - El usuario de ventanilla recibe al encuestador a primera hora y formaliza la
     entrega física y digital de la hoja generada por el enrutador.
   - El encuestador dispone de su interfaz de campo para registrar la llamada
     previa obligatoria (1 día antes) y diligenciar el formulario digital sin
     errores de papel (validación automática de edad de menor, preguntas completas,
     nombre inalterable del encuestador).

8. Módulo de Generación de Informes en PDF:
   - Generación de informe gerencial ejecutivo en PDF para el Administrador con
     indicadores de cumplimiento del SLA de 15 días calendario.
   - Generación de hojas de ruta oficiales en formato PDF imprimible para campo.
   - Implementado con la librería nativa en PHP FPDF 1.86 (sin dependencias externas).

9. Dashboard Gerencial Analítico:
   - Indicadores Clave de Desempeño (KPIs): Total solicitudes, % de cumplimiento
     de los 15 días, solicitudes vencidas, distribución urbana vs. rural.
   - Gráficos interactivos con Chart.js: Embudo del proceso, distribución por
     comunas de Cali y carga en los 8 puntos estáticos.
   - Tabla de alertas tempranas para solicitudes que superan los 10 días.

3. CREDENCIALES DE ACCESO DE PRUEBA (PARA LOS 5 PERFILES)
--------------------------------------------------------------------------------
Todos los usuarios se encuentran preconfigurados en la base de datos con contraseñas
seguras encriptadas con BCRYPT. Puede usar el formulario de login o el botón de
acceso rápido disponible en la pantalla de inicio de sesión:

• ADMINISTRADOR:
  - Usuario: admin
  - Contraseña: admin123
  - Acceso: Dashboard gerencial, auditoría, usuarios, puntos de atención, reportes PDF.

• VENTANILLA (Punto Comuna 17 Principal o Comuna 13):
  - Usuario: ventanilla1
  - Contraseña: ventanilla123
  - Acceso: Radicación con mapa georreferenciado, entrega matutina de hojas de ruta.

• ENRUTADOR (Logística y despacho):
  - Usuario: enrutador1
  - Contraseña: enrutador123
  - Acceso: Bitácora diaria de todos los puntos, armado de hoja de ruta (carga 20).

• ENCUESTADOR (Trabajo de campo):
  - Usuario: encuestador1
  - Contraseña: encuestador123
  - Acceso: Hoja de ruta móvil, confirmación de llamada previa, captura digital.

• CALIDAD (Equipo revisor de 5):
  - Usuario: calidad1
  - Contraseña: calidad123
  - Acceso: Mesa de revisión diaria, verificación de criterios y dictamen Apto/No Apto.

4. PASOS PARA LA INSTALACIÓN Y PUESTA EN MARCHA
--------------------------------------------------------------------------------
1. Iniciar los servicios de Apache y MySQL en el panel de control de XAMPP.
2. La base de datos `encuesta_db` se encuentra ubicada en:
   - Estructura: database/schema.sql
   - Datos iniciales: database/seed.sql
3. Si requiere recrear la base de datos desde la consola de comandos de MySQL:
   mysql -u root < database/schema.sql
   mysql -u root < database/seed.sql
4. Abrir el navegador web e ingresar a:
   http://localhost/Encuesta/
5. Seleccione cualquiera de los 5 roles en el botón de acceso rápido para iniciar
   el recorrido del flujo completo.

5. ARQUITECTURA TECNOLÓGICA Y ESTÁNDARES DE INGENIERÍA
--------------------------------------------------------------------------------
• Backend: PHP 8.2 (Programación orientada a buenas prácticas, PDO con sentencias
  preparadas contra inyección SQL, sesiones seguras, control de acceso RBAC).
• Base de Datos: MySQL / MariaDB 10.4 (Modelo relacional normalizado en 3FN con
  claves foráneas, índices y soporte UTF-8 MB4).
• Frontend: HTML5 semántico, CSS3 moderno con paleta institucional sobria
  (libre de estética artificial o genérica de IA), Bootstrap 5.3.3.
• Componentes RIA / Interactivos:
  - Leaflet.js 1.9.4 + OpenStreetMap para georreferenciación cartográfica de Cali.
  - Chart.js para visualización de métricas en el Dashboard.
• Generación de Documentos: FPDF 1.86 nativo para compilación de PDFs oficiales.

6. DOCUMENTACIÓN Y ENTREGABLE ACADÉMICO OFICIAL
--------------------------------------------------------------------------------
• Informe Oficial Entrega 1 (PDF): docs/INFORME_ENTREGA_1_SIRCE_CALI.pdf
  (Incluye metodología Scrum, 9 Historias de Usuario, modelo relacional 3FN y MER,
   herramientas DevOps: GitFlow, Docker, GitHub Actions CI, Jira, bitácoras y matriz de pruebas).
• Script de Regeneración de Informe PDF: generar_informe_pdf.php
• Enlace Directo en la Aplicación: En pantalla de Login y en "Informes PDF" del Administrador.
================================================================================
