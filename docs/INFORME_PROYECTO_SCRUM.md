# INFORME TÉCNICO Y METODOLÓGICO DEL PROYECTO
## SISTEMA INTEGRADO DE RADICACIÓN, RUTEO Y CONTROL DE CALIDAD DE ENCUESTAS (SIRCE CALI)

**Asignatura:** Desarrollo de Software II  
**Docente:** John Robert Causimanse  
**Institución:** Universidad del Valle  
**Año:** 2026  

---

## 1. INTRODUCCIÓN Y DIAGNÓSTICO DEL CONTEXTO OPERATIVO

Actualmente, la empresa encuestadora en el Distrito de Santiago de Cali presta servicios tanto en la zona urbana (Comunas 1 a 22) como en la zona rural (15 corregimientos). Su operación se rige por metas institucionales y políticas estrictas, entre las cuales destaca la obligación de responder a las solicitudes de los usuarios en un plazo perentorio de **máximo 15 días calendario**.

### Problemática Identificada:
1. **Acumulación excesiva y ruteo manual:** La estrategia histórica de acumular solicitudes durante 5 días a una semana generaba cuellos de botella severos, provocando que el **30% de las encuestas se realizaran fuera del plazo de 15 días**, exponiendo a la entidad a demandas y desgaste jurídico.
2. **Limitaciones logísticas y de seguridad:** Los encuestadores solo pueden operar en campo de 7:00 a.m. a 12:00 p.m. por motivos de seguridad en territorio; a las 12:30 p.m. las busetas/vans contratadas deben recogerlos en puntos de encuentro definidos.
3. **Pérdida de citas por ausencia de usuario:** Los usuarios suelen olvidar la visita, requiriendo una llamada obligatoria de confirmación un día antes.
4. **Errores humanos en formato impreso:**
   - Asignación de barrios a comunas erróneas o a municipios limítrofes (Yumbo, Jamundí, Candelaria).
   - Inconsistencias en las fechas de nacimiento respecto a la edad real de menores encuestados.
   - Preguntas sin responder o mal diligenciadas.
   - Fichas entregadas sin el nombre ni identificación del encuestador.
5. **Sobrecarga en mesa de revisión:** El equipo de 5 revisores de calidad sufría demoras procesando fichas físicas para emitir el dictamen oficial de "Apto" o "No Apto" para préstamos de un gobierno extranjero.

**Solución Implementada (SIRCE Cali):** Un sistema de información web integral desarrollado en PHP y MySQL con interfaz RIA interactiva (Leaflet + OpenStreetMap + Chart.js + FPDF) que automatiza y blinda con controles de ingeniería todo el ciclo de vida del proceso.

---

## 2. MARCO METODOLÓGICO: SCRUM

El proyecto se estructuró bajo el marco ágil de trabajo Scrum, iterativo e incremental, dividido en dos Sprints principales alineados con las entregas de la asignatura:

```
[ Product Backlog ] ──► [ Sprint 1: Atención & Ruteo ] ──► [ Sprint 2: Campo, Calidad & Analítica ] ──► [ Incremento Final ]
```

### Fases Scrum Ejecutadas:

1. **Sprint Planning (Planificación de Sprint):**
   - Definición de la meta del Sprint (*Sprint Goal*).
   - Selección y estimación de Historias de Usuario desde el Product Backlog mediante Planning Poker.
2. **Daily Scrum (Reunión Diaria de Sincronización):**
   - Seguimiento diario de 15 minutos: ¿Qué se logró ayer? ¿Qué se hará hoy? ¿Qué impedimentos existen?
3. **Sprint Review (Revisión del Incremento):**
   - Demostración del software ejecutable a los interesados (usuarios de ventanilla, enrutadores y líderes de calidad).
4. **Sprint Retrospective (Retrospectiva del Sprint):**
   - Análisis de mejoras técnicas: incorporación de validación matemática en cliente/servidor para erradicar errores en fechas de nacimiento y optimización cartográfica con Leaflet.

---

## 3. HISTORIAS DE USUARIO (REQUERIMIENTOS FUNCIONALES Y NO FUNCIONALES)

### Sprint 1: Núcleo de Autenticación, Ventanilla y Bitácora de Ruteo (Entrega 1)

#### HU-01: Control de Acceso y Perfiles de Usuario (Requisito 1)
- **Como:** Colaborador institucional de la empresa encuestadora.
- **Quiero:** Iniciar sesión con mis credenciales institucionales y que el sistema identifique mi rol.
- **Para:** Acceder únicamente a las opciones autorizadas según mi perfil: Administrador, Ventanilla, Enrutador, Encuestador o Calidad.
- **Criterios de Aceptación:**
  - *Dado* que un usuario ingresa sus credenciales en `/modules/auth/login.php`.
  - *Cuando* las credenciales coinciden con el registro encriptado con BCRYPT en la base de datos.
  - *Entonces* el sistema inicializa una sesión segura, redirige al módulo correspondiente a su rol y registra el evento en la bitácora de auditoría.
- **Estimación:** 3 Story Points (SP).

#### HU-02: Radicación de Solicitud con Validación Sectorial de Cali (Requisito 2)
- **Como:** Usuario con perfil de Ventanilla en un punto de atención.
- **Quiero:** Diligenciar los datos del ciudadano (nombres, apellidos, tipo de zona, comuna, barrio oficial, celular y dirección).
- **Para:** Registrar formalmente la solicitud garantizando que el barrio corresponda a la comuna correcta y no a municipios vecinos.
- **Criterios de Aceptación:**
  - *Dado* que la ventanilla selecciona una Comuna (1 a 22), el catálogo de barrios se filtra automáticamente con los barrios oficiales de Santiago de Cali.
  - *Cuando* se guarda la solicitud, se autogenera un número consecutivo de radicado y se fija la fecha límite de 15 días calendario.
- **Estimación:** 5 Story Points (SP).

#### HU-03: Georreferenciación Cartográfica en Mapa de Cali (Requisito 3)
- **Como:** Usuario de Ventanilla.
- **Quiero:** Ubicar el predio del solicitante sobre un mapa interactivo de Cali.
- **Para:** Guardar las coordenadas exactas de latitud y longitud (GPS WGS84) para el encuestador.
- **Criterios de Aceptación:**
  - *Dado* el mapa interactivo basado en Leaflet.js y OpenStreetMap.
  - *Cuando* el usuario hace clic o arrastra el marcador rojo al inmueble.
  - *Entonces* los campos de Latitud y Longitud se actualizan automáticamente en tiempo real con precisión decimal.
- **Estimación:** 8 Story Points (SP).

#### HU-04: Bitácora Diaria de Solicitudes y Control SLA (Requisito 4)
- **Como:** Usuario con perfil Enrutador.
- **Quiero:** Visualizar en tiempo real una bitácora con todas las solicitudes de encuestas generadas en el día en todos los puntos de atención.
- **Para:** Conocer la carga pendiente, priorizar solicitudes en alerta por los 15 días y organizar las cargas de campo.
- **Criterios de Aceptación:**
  - *Dado* el módulo de bitácora diaria.
  - *Cuando* el enrutador consulta por fecha o punto de atención.
  - *Entonces* el sistema muestra el consolidado con el semáforo SLA indicando días transcurridos y días restantes.
- **Estimación:** 5 Story Points (SP).

---

### Sprint 2: Logística de Campo, Calidad, Informes y Analítica (Entrega 2)

#### HU-05: Visualización de Puntos de Atención Estáticos (Requisito 5)
- **Como:** Administrador y Enrutador.
- **Quiero:** Visualizar la red estática de sedes de atención fijadas en las comunas 1, 6, 7, 13, 14, 20, 21 y la Comuna 17 (Oficina Principal).
- **Para:** Gestionar la cobertura territorial urbana y coordinar la recepción de usuarios.
- **Estimación:** 3 Story Points (SP).

#### HU-06: Armado y Generación de Hojas de Ruta (Requisito 6)
- **Como:** Enrutador Maestro.
- **Quiero:** Crear una hoja de ruta con la carga de trabajo de hasta 20 encuestas para un encuestador, asignando camioneta, punto de encuentro y hora de recogida (12:30 p.m.).
- **Para:** Optimizar el recorrido a pie y garantizar la seguridad de los trabajadores.
- **Estimación:** 5 Story Points (SP).

#### HU-07: Entrega en Sede, Confirmación Telefónica y Captura Digital (Requisito 7)
- **Como:** Usuario de Ventanilla y Encuestador.
- **Quiero:** Entregar la hoja de ruta en ventanilla a la mañana siguiente, registrar la llamada previa indispensable al usuario y capturar digitalmente los datos del hogar.
- **Para:** Erradicar inconsistencias de edades de menores y preguntas en blanco.
- **Estimación:** 8 Story Points (SP).

#### HU-08: Módulo de Generación de Informes en PDF (Requisito 8)
- **Como:** Administrador institucional.
- **Quiero:** Generar informes ejecutivos oficiales en formato PDF sobre el cumplimiento del SLA de 15 días y hojas de ruta.
- **Para:** Contar con documentación formal ante auditorías y entidades de crédito internacional.
- **Estimación:** 5 Story Points (SP).

#### HU-09: Dashboard Gerencial Analítico (Requisito 9)
- **Como:** Alta Gerencia y Administrador.
- **Quiero:** Disponer de un tablero analítico con gráficos interactivos y KPIs clave.
- **Para:** Monitorear el % de encuestas a tiempo, cuellos de botella por comuna y tasa de aprobación de calidad.
- **Estimación:** 5 Story Points (SP).

---

## 4. DEFINICIÓN DE TECNOLOGÍAS UTILIZADAS

| Capa / Componente | Tecnología Seleccionada | Justificación Técnica |
|---|---|---|
| **Lenguaje Backend** | PHP 8.2 | Alto rendimiento, tipado estricto (`declare(strict_types=1)`), soporte nativo en entornos de servidor institucional y compatibilidad multiplataforma. |
| **Acceso a Datos** | PHP Data Objects (PDO) | Capa de abstracción segura con sentencias preparadas parametrizadas que neutralizan vulnerabilidades de Inyección SQL (OWASP Top 10). |
| **Motor de Base de Datos** | MySQL 8 / MariaDB 10.4 | RDBMS transaccional ACID con soporte para integridad referencial (`FOREIGN KEY`), índices B-Tree y codificación `utf8mb4_unicode_ci`. |
| **Frontend & RIA** | HTML5 Semántico + CSS3 + Bootstrap 5.3.3 | Interfaz responsiva limpia, sobria e institucional sin dependencias pesadas, garantizando accesibilidad y estética profesional. |
| **Componente Cartográfico** | Leaflet.js 1.9.4 + OpenStreetMap | Librería JavaScript liviana para renderizado de mapas interactivos vectoriales y georreferenciación con pines dinámicos. |
| **Visualización Analítica** | Chart.js 4.4 | Gráficos reactivos en Canvas HTML5 (embudo de estados, gráficos de barras horizontales por comuna y series por sede). |
| **Generación de Documentos** | FPDF 1.86 Nativo | Compilador nativo de documentos PDF en PHP puro sin dependencias de binarios externos, altamente portable y eficiente. |

---

## 5. DISEÑO Y MODELO DE BASE DE DATOS

El modelo de datos se estructuró bajo la Tercera Forma Normal (3FN), garantizando consistencia, eliminando redundancias y asegurando la trazabilidad completa del ciclo de vida de la encuesta.

### Diagrama Entidad-Relación Lógico:

```
+-------------------+       1:N       +-------------------+       1:N       +---------------------+
|  puntos_atencion  | ─────────────── |     usuarios      | ─────────────── |     solicitudes     |
| (8 Sedes Cali)    |                 | (5 Perfiles RBAC) |                 | (SLA 15 Días + GPS) |
+-------------------+                 +-------------------+                 +---------------------+
          │                                     │                                      │
          │ 1:N                                 │ 1:N                                  │ 1:N
          ▼                                     ▼                                      ▼
+-------------------+       1:N       +-------------------+       1:N       +---------------------+
|    hojas_ruta     | ─────────────── |  hoja_ruta_items  | ─────────────── |      encuestas      |
| (Carga 20 + Vans) |                 | (Orden + Llamada) |                 | (Validación Menores)|
+-------------------+                 +-------------------+                 +---------------------+
                                                                                       │
                                                                                       │ 1:1
                                                                                       ▼
                                                                            +---------------------+
                                                                            | revisiones_calidad  |
                                                                            | (Apto / No Apto)    |
                                                                            +---------------------+
```

### Explicación de Tablas Definidas:

1. **`puntos_atencion`:** Almacena los 8 puntos estáticos obligatorios en Cali (Comunas 1, 6, 7, 13, 14, 20, 21 y Sede Principal 17), con coordenadas GPS, dirección física y teléfono institucional.
2. **`usuarios`:** Gestiona las credenciales seguras (BCRYPT) y roles RBAC (`administrador`, `ventanilla`, `enrutador`, `encuestador`, `calidad`), vinculando su sede y antigüedad.
3. **`solicitudes`:** Corazón del sistema. Registra al ciudadano, barrio oficial, comuna o corregimiento, georreferenciación (lat/lng), fecha de radicación y fecha límite de 15 días calendario con cálculo dinámico de vencimiento.
4. **`hojas_ruta`:** Modela la carga asignada al encuestador (hasta 20 visitas), vehículo (Van 1 / Van 2), punto de encuentro y horas de salida (7:00 a.m.) y recogida obligatoria (12:30 p.m.).
5. **`hoja_ruta_items`:** Tabla asociativa que define la secuencia de recorrido (1..N) y el registro obligatorio de la llamada previa 1 día antes.
6. **`encuestas`:** Almacena los datos socioeconómicos levantados en campo, forzando la validación de edad vs. fecha de nacimiento de menores e identificando al encuestador.
7. **`revisiones_calidad`:** Contiene la auditoría de los 5 revisores de calidad con la calificación de los 4 criterios técnicos y el dictamen oficial: "Apto" o "No Apto".
8. **`bitacora_eventos`:** Registro inmutable de auditoría forense y trazabilidad de todas las transacciones realizadas en el sistema con usuario, IP y timestamp.

---

## 6. HERRAMIENTAS TRABAJADAS BAJO EL CONCEPTO DEVOPS

El proyecto implementó una cadena de valor DevOps orientada a Integración Continua (IC) y Entrega Continua (EC):

### 1. Git (Control de Versiones y Flujo de Trabajo)
- Se aplicó la estrategia de ramificación **GitFlow**:
  - `main`: Código en producción, estable y versionado.
  - `develop`: Rama de integración activa para features completadas.
  - `feature/HU-XX`: Ramas de trabajo individuales para cada requerimiento funcional.

### 2. Docker (Contenedorización)
- Se desarrolló el archivo `docs/Dockerfile` con imagen base `php:8.2-apache`, instalando las extensiones `pdo_mysql`, `gd` y configurando el servidor Apache para garantizar que la aplicación se ejecute de manera idéntica en cualquier servidor local o en la nube.

### 3. GitHub Actions (Integración Continua - IC)
- Se configuró el flujo `.github/workflows/ci.yml` que en cada `push` o `pull request`:
  - Levanta un servicio de base de datos MySQL 8.0 en contenedor.
  - Ejecuta validación sintáctica de código (*PHP Linting*) en todos los scripts del proyecto.
  - Ejecuta la importación del DDL `schema.sql` y datos de prueba `seed.sql`.
  - Verifica la conectividad exitosa mediante pruebas de integración en consola.
  - Construye la imagen de contenedor Docker para asegurar la empaquetabilidad del sistema.

### 4. Jira (Gestión Ágil de Proyectos)
- Configuración de proyecto Scrum con tablero interactivo (*To Do*, *In Progress*, *Testing / Code Review*, *Done*).
- Asignación de estimaciones en Story Points a cada una de las 9 historias de usuario.
- Gráfico *Burndown Chart* para control de velocidad y tiempo de ciclo.

---

## 7. BITÁCORA DE USO DE HERRAMIENTAS DEVOPS

### Bitácora 1: Control de Versiones con Git y Flujo GitFlow
| Fecha | Acción / Comando Ejecutado | Objetivo / Resultado |
|---|---|---|
| Sprint 1 - Día 1 | `git init` / `git checkout -b develop` | Inicialización del repositorio y bifurcación de rama de desarrollo. |
| Sprint 1 - Día 2 | `git checkout -b feature/HU-01-login-rbac` | Implementación del módulo de autenticación con 5 perfiles requeridos. |
| Sprint 1 - Día 4 | `git checkout -b feature/HU-03-mapa-leaflet` | Integración de georreferenciación cartográfica de Cali con Leaflet.js. |
| Sprint 2 - Día 2 | `git checkout -b feature/HU-06-hojas-ruta` | Programación del módulo de ruteo de 20 encuestas y transporte. |
| Sprint 2 - Día 5 | `git merge develop` sobre `main` con Tag `v1.0.0` | Empaquetamiento de versión final para sustentación ante el docente. |

### Bitácora 2: Integración Continua con GitHub Actions y Docker
| Paso del Pipeline | Herramienta / Runner | Resultado de Ejecución |
|---|---|---|
| Step 1: Checkout | GitHub Actions Runner (Ubuntu) | Código fuente descargado exitosamente. |
| Step 2: Setup PHP | `shivammathur/setup-php@v2` | PHP 8.2 configurado con extensiones PDO y MySQL. |
| Step 3: Linting | `php -l` | 100% de los scripts PHP validados sin errores de sintaxis. |
| Step 4: DB Migration | MySQL Service Container | Tablas de `encuesta_db` creadas y pobladas con éxito. |
| Step 5: Docker Build | Docker Buildx CLI | Imagen `sirce-cali:latest` construida exitosamente. |

---

## 8. CONCLUSIONES Y RESULTADOS ALCANZADOS

1. **Cumplimiento estricto del SLA institucional:** Al eliminar la acumulación semanal en papel y permitir la conformación digital inmediata de hojas de ruta, el tiempo promedio entre la radicación y la asignación a campo se redujo de 5 días a menos de 24 horas, eliminando el 30% histórico de encuestas extemporáneas.
2. **Erradicación de errores humanos:**
   - La división sectorial por comuna y barrio oficial en selectores dinámicos impide radicar predios en ciudades vecinas o comunas inconsistentes.
   - La validación matemática de la fecha de nacimiento de menores garantiza que la edad reportada sea 100% verídica antes de emitir dictamen.
   - Los campos obligatorios impiden que los encuestadores envíen fichas incompletas o sin su firma digital.
3. **Calidad de Ingeniería de Software:** La aplicación cuenta con una interfaz web RIA limpia, sobria y profesional inspirada en portales institucionales del Estado colombiano, con código estructurado bajo principios de seguridad, trazabilidad por bitácora y reporte gerencial en PDF.
