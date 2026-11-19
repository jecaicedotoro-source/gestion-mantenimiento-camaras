# Trazabilidad entre requisitos diseño e implementación

El incremento continúa el mismo sistema de inventario y mantenimiento de cámaras de la Ciudadela Pampalinda. CU-01, registrar una cámara, es el caso prioritario; la clasificación textual de incidencias mediante OpenAI es un complemento obligatorio por decisión posterior del proyecto. Esta relación se comprobó con el taller original del Módulo 3, los textos de los Módulos 1 y 2 y el código existente.

Los documentos académicos originales se consultaron de forma privada y no se incorporan al repositorio. La lectura textual incluye tablas; los diagramas incrustados requieren una revisión visual independiente antes de afirmar equivalencia gráfica completa.

## Fuentes y precedencia de decisiones

| Fuente consultada | Decisión o exigencia relevante |
| --- | --- |
| `Modulo_1_JohnCaicedo.docx` | Requisitos, historias de usuario, perfiles, inventario y mantenimiento. El texto original excluía IA dentro del producto y contemplaba desarrollo individual |
| `Informe_Arquitectura_Modulo_2.docx` | Monolito modular por capas, modelo relacional, API REST, `IClasificadorIncidencias`, adaptador OpenAI, minimización, sugerencias separadas de la confirmación humana y continuidad manual |
| `Actividad Evaluable Modulo 3 Desarrollo Integracion Servicios.docx` | Un caso completo, persistencia, API comprobada, ramas/commits/PR integrado, configuración segura, contenedores, documentación y demostración; diez entregables y PDF único |
| `desarrollo actividad.docx` | Borrador de avances de Módulo 3. Declara permiso docente para modalidad individual, pero no contiene la autorización original recuperada. Sus resultados históricos deben contrastarse con las ejecuciones actuales |
| Prompt maestro 68342 y solicitud actual | Conservación de Laravel 13, Livewire 4 y SQLite, desactivación segura, rediseño moderado, inventario repetible, serie única opcional, OpenAI real con confirmación humana, código comentado y README incremental |

El Módulo 2 reconoció expresamente que incorporar IA ampliaba el alcance de Módulo 1. La solicitud posterior adopta esa ampliación; se registra aquí y debe reflejarse en la versión académica actualizada de visión, requisitos y backlog. La descripción antigua que excluía IA no representa el alcance vigente.

## Mapeo de requisitos a código y evidencia

| Requisito o decisión | Implementación del incremento | Verificación y límite |
| --- | --- | --- |
| Acceso por perfiles y administración de cuentas | Fortify, roles, gates, `UserPolicy`, `UserManagementService` y `EnsureUserIsActive` | Pruebas de autorización, edición, roles y estado. No confundir controles implementados con una auditoría externa completa |
| Conservar identidad e historial | `users.is_active`, desactivación/reactivación y referencias restrictivas | Pruebas de autodesactivación, último administrador activo, sesiones existentes, tokens y vías alternativas. No se ejecutan borrados de cuentas como flujo de administración |
| CU-01 con validación y persistencia | Componentes de cámaras, `CameraRegistrationService`, `CameraValidationRules`, `Camera` y migraciones | Pruebas del formulario, permisos, servicio, API y consulta. La grabación visual final se registra separadamente |
| Inventario repetible y serie opcional única | Validación compartida, normalización de vacíos a `NULL`, índice único de serie y manejo de conflicto | Pruebas de códigos repetidos, series duplicadas y series vacías. El número de activo fijo no se introduce en este incremento |
| API HTTP real | `CameraController`, `IncidentController` y `routes/api.php`, protegidos por Sanctum, estado activo, verificación y permisos | Pruebas HTTP y documentación. Se requiere además evidencia de la colección o herramienta equivalente con datos ficticios |
| Responsabilidad única y capas | Presentación Blade/Livewire; servicios de aplicación; policies, reglas y contrato; Eloquent/SQLite y adaptador OpenAI | Los componentes delegan reglas y escrituras al servicio; las plantillas y modelos no invocan OpenAI. Eloquent sigue siendo una dependencia de los servicios: es un monolito Laravel por capas, no una separación física absoluta del framework |
| Inversión de dependencias para IA | `IClasificadorIncidencias`, `IncidentSuggestion`, binding en proveedor y `OpenAIIncidentClassifier` | Clasificador simulado en pruebas y sustitución del adaptador sin cambiar el flujo de negocio. No se crean interfaces vacías para cada modelo |
| Sugerencia sin decisión automática | `IncidentManagementService`, `Incident`, `IncidentClassification` y componente de incidencias | Pruebas de propuesta pendiente, aceptación, corrección, decisión manual, responsable y fecha. La sugerencia no asigna por sí sola categoría confirmada |
| Datos mínimos y respuesta restringida | `IncidentTextMinimizer`, vista previa, aprobación del texto, catálogo permitido y validación estructurada del adaptador | Pruebas de datos sensibles reconocibles, salida malformada, categoría ajena al catálogo y errores. La minimización automática no garantiza detectar todo dato sensible; el usuario revisa la vista previa |
| Continuidad ante errores de IA | Tiempo de espera, límites de solicitudes e intentos, excepción controlada y clasificación manual | Pruebas simuladas de HTTP, tiempo de espera, respuesta inválida y límite. Disponibilidad real OpenAI pendiente de llamada controlada |
| Interfaz moderada y en español | Tailwind/Flux existentes, navegación por permiso, panel con consultas autorizadas, portada propia y traducciones | Compilación de recursos y plantillas aprobada; prueba visual responsive, teclado y temas pendiente de captura verificable |
| Ejecución fuera de Herd | Dockerfile, Compose, volumen de almacenamiento y script de inicio | Preparado; construcción y ejecución Docker no realizadas localmente por falta de CLI |

Los resultados definitivos, comandos y enlaces comprobados se mantienen en [Evidencias verificadas](evidencias.md), evitando convertir los resultados esperados en resultados ejecutados.

## Diferencias explícitas frente al diseño anterior

### Identificación de cámaras

La historia del Módulo 1 exigía número de activo único; el modelo del Módulo 2 representaba `codigo_activo (UQ)`. La regla institucional posterior distingue inventario y activo fijo. En el incremento, `codigo_inventario` admite repeticiones y `numero_serie` debe ser único cuando existe. El activo fijo único se mantiene como decisión futura. No se interpreta el inventario como la misma clave conceptual que el activo fijo.

### Roles del registro

Módulo 1 formulaba el registro como historia de un técnico. El caso prioritario posterior identifica al administrador como actor principal. El permiso `create-cameras` existente admite administrador y coordinador; técnico y auxiliar consultan según `view-cameras`, sin asumir autorización de creación. Esta matriz de permisos necesita validación institucional al actualizar las historias. El actor principal se demuestra con administrador y no se simula equivalencia entre todos los perfiles.

### Datos normalizados y alcance del modelo

El modelo conceptual del Módulo 2 incluía nueve entidades. Este incremento no declara completas programación y mantenimiento. La ubicación de cámara permanece como texto; la normalización a la entidad UBICACION no está implementada. El catálogo de incidencia utiliza un enum provisional en lugar de una tabla institucional de categorías activas. `incident_classifications` reúne eventos de propuesta y decisiones humanas vinculadas, conservando por separado la categoría confirmada en `incidents`.

El enum contempla conectividad, alimentación, imagen, hardware, configuración y otra. Estos valores son una propuesta técnica inicial y requieren validación institucional; no se presentan como catálogo aprobado. Una futura tabla de categorías deberá preservar la trazabilidad de los registros existentes.

### Versionado y nombres de API

El diseño del Módulo 2 proponía rutas bajo `/api/v1`, con nombres en español. Se conservan las rutas existentes de cámaras bajo `/api/cameras` para no romper su contrato, y se incorporan incidencias bajo `/api/v1/incidencias`. Las operaciones `POST /api/v1/incidencias/{id}/sugerencias-ia` y `PATCH /api/v1/incidencias/{id}/categoria` mantienen el propósito del diseño. Los nombres JSON corresponden al código real y se documentan expresamente; no se declara que la implementación sea idéntica al contrato conceptual anterior.

### Framework y persistencia

Módulo 2 dejaba abierta la selección de framework y motor. Las decisiones posteriores conservan Laravel 13, Livewire 4 y SQLite. Laravel aporta autenticación, validación, migraciones, transacciones y cliente HTTP; Livewire mantiene interacción sin una aplicación frontend separada; SQLite facilita instalación y prueba del MVP. La posible concurrencia institucional futura necesita medición antes de justificar un cambio de motor, sin introducir microservicios ni migraciones prematuras.

### Colaboración y revisión

El taller exige pareja y contribuciones de ambos integrantes. El borrador declara modalidad individual autorizada. La autorización original debe acreditarse en la entrega o resolverse la discrepancia con el docente. Un asistente de IA o un subagente de desarrollo no constituye un segundo integrante académico. Una rama y un PR tampoco prueban por sí solos revisión humana ni integración.

## Pendientes de armonización académica

1. Actualizar visión, requisitos y backlog del Módulo 1 con el alcance de IA y la distinción inventario/activo fijo/serie.
2. Validar con responsables institucionales permisos de registro, categorías y tratamiento de descripciones; usar datos ficticios hasta entonces.
3. Definir retención operativa de descripciones e historial de clasificación y procedimientos de acceso y depuración autorizada. No se implementa borrado automático de historiales para aparentar una política aprobada.
4. Actualizar diagramas y modelo lógico para reflejar tablas, campos y partes diferidas, conservando como futuro lo que aún no existe.
5. Incorporar evidencia real de OpenAI, Docker, PR revisado e integrado, demostración y PDF final cuando estén disponibles.

El alcance excluye vídeo, imágenes, reconocimiento facial, conexión u operación de cámaras/NVR/DVR y diagnósticos automáticos. La IA propone una categoría textual; una persona autorizada conserva la decisión.
