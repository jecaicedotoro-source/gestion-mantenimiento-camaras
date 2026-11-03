# Gestión del inventario y mantenimiento de cámaras — USC

Sistema web para la Ciudadela Pampalinda de la Universidad Santiago de Cali. Este repositorio continúa el MVP existente; su alcance prioritario es **CU-01: registrar una cámara de seguridad**. La clasificación asistida de incidencias es una funcionalidad complementaria que requiere decisión humana.

## Objetivos

Conservar el inventario y apoyar su mantenimiento mediante autenticación institucional, permisos por rol, validaciones y persistencia verificable.

## Tecnologías verificadas

Auditoría del 3 de noviembre de 2026: PHP local 8.5.12; `composer.lock`: Laravel 13.39.0, Livewire 4.4.8, Flux UI 2.21.0, Fortify 1.41.0 y Sanctum 4.3.3. Se conserva SQLite. Las pruebas existentes usan Pest sobre PHPUnit.

## Estado de la auditoría inicial

- Remoto `main`: `9ef06ca`; rama `feature/registro-camaras`: `3ae9d87`.
- Ninguna de esas referencias contenía README al comenzar esta continuación.
- La instalación local de Herd contenía trabajo adicional sin commit: inventario, formulario de cámaras, servicio de registro, API con Sanctum y pruebas.
- Ese trabajo se conserva como base separada antes de las mejoras. El código de inventario admite repetidos; la serie es única si se proporciona.
- La administración de usuarios aún borraba cuentas físicamente y concentraba lógica en Livewire. Faltaban incidencias, adaptador OpenAI, Docker y documentación de entrega.
- No se ha modificado la instalación original de Herd ni ejecutado migraciones sobre su base de datos.

## Arquitectura y estructura

Monolito modular con MVC y capas: presentación en Blade/Livewire, aplicación en `app/Services`, reglas compartidas en `app/Support`, autorización mediante Gates/policies e infraestructura Eloquent/SQLite. Se incorporan abstracciones únicamente donde separan una integración externa.

### Interfaz

Se conserva Livewire/Flux y se aplica una renovación moderada: navegación azul marino, índigo principal, turquesa para estados positivos y ámbar/coral como acentos. La portada es propia del proyecto, sin atribuir autorización de marca institucional. El dashboard consulta indicadores reales según los permisos del usuario. Formularios y tablas conservan modo oscuro, adaptación a pantallas pequeñas, etiquetas y mensajes en español, foco visible y estados de carga.

`npm run build` y `php artisan view:cache` se ejecutaron correctamente. La inspección visual en navegador sigue pendiente por fallo del controlador de interfaz del entorno; no se declara una auditoría WCAG ni una verificación visual móvil completa.

## Instalación y configuración

Requisitos: PHP 8.5, Composer 2, Node 22 o superior compatible con el lockfile, y extensiones PHP habituales de Laravel más SQLite, mbstring, intl y zip. Se mantienen las dependencias bloqueadas de Composer/npm. PHP y SQLite simplifican el MVP; Livewire evita una segunda aplicación frontend y Fortify/Sanctum reutilizan autenticación probada.

En una copia nueva, desde PowerShell:

```powershell
composer install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) { New-Item -ItemType File database/database.sqlite }
php artisan migrate --force
php artisan db:seed --force
php artisan app:create-admin
npm ci
npm run build
php artisan serve
```

En Linux/macOS sustituir `Copy-Item` por `cp` y crear la base mediante `touch database/database.sqlite`. La cuenta inicial se crea de forma interactiva con contraseña oculta fuerte, correo único y verificación por el operador de consola. Los seeders solo crean/actualizan roles; nunca una contraseña predeterminada. El registro público está deshabilitado.

Abrir `http://localhost:8000`. Configurar `APP_URL` con el origen real para passkeys y enlaces de verificación; fuera de localhost, WebAuthn requiere un contexto seguro apropiado. En Herd conservar el proyecto original y su configuración existente; aplicar los cambios revisados después de respaldar.

Variables principales: `APP_NAME`, `APP_URL`, `APP_KEY`, `APP_LOCALE=es`, `DB_CONNECTION=sqlite`, `DB_DATABASE` opcional (por defecto `database/database.sqlite`), `SESSION_DRIVER=database`, `CACHE_STORE=database`. Configurar un transporte de correo operativo para verificaciones de cuentas creadas desde la administración; `MAIL_MAILER=log` es solo para desarrollo y sus registros deben mantenerse privados.

`.env`, respaldos y bases SQLite son locales. No publicar secretos ni ejecutar `migrate:fresh` sobre una base con información real. Antes de modificar estructuras existentes:

```powershell
php artisan app:backup-database
php artisan migrate --force
```

El respaldo utiliza `VACUUM INTO` para obtener una copia consistente en `storage/app/backups`. Proteger y conservar esa carpeta; la retención automática no se ha definido institucionalmente. Una restauración debe realizarse sobre una copia revisada, con la aplicación detenida y autorización del responsable.

## Base de datos y estructura del incremento

`roles` y `users` conservan la autenticación original; `users.is_active/auth_version` controlan vigencia del acceso. `cameras` almacena el inventario. `incidents` referencia cámara, reportante y responsable de revisión; `incident_classifications` registra solicitudes y decisiones humanas con vínculo a la propuesta. Las claves foráneas impiden borrar registros con historial. Se agregan migraciones sin eliminar usuarios/cámaras.

Directorios principales: `app/Services` (casos de uso), `app/Policies` (autorización), `app/Contracts` y `app/Infrastructure/AI` (puerto/adaptador), `app/Livewire` y `resources/views` (presentación), `app/Http/Controllers/Api` (HTTP), `database/migrations`, `tests`, `docker` y `docs`. Ubicaciones normalizadas, programación y mantenimiento completo se mantienen como alcance futuro, no como tablas implementadas.

## Autenticación, roles y funcionalidades

Roles existentes: administrador, coordinador, técnico y auxiliar. Fortify administra autenticación y verificación; Sanctum protege la API. Las reglas existentes permiten registrar cámaras a administrador y coordinador; CU-01 tiene al administrador como actor principal.

### Bloque de usuarios consolidado

`UserManagementService` concentra las operaciones y `UserPolicy` autoriza cada acción en el servidor. Los cambios se ejecutan en transacciones; en SQLite se adquiere un bloqueo de escritura antes de contar administradores activos.

Las cuentas se desactivan y reactivan conservando identidad e historiales. Un administrador no puede desactivarse ni cambiar su propio rol; debe permanecer un administrador activo. Se revocan tokens Sanctum, sesiones y cookies de recuerdo al desactivar o modificar credenciales, correo o rol. `auth_version` impide revivir sesiones antiguas tras reactivar; también se invalidan desafíos 2FA previos. Contraseña y callback de passkeys rechazan cuentas inactivas. El perfil de una cuenta no administrativa permite desactivación con contraseña; no hay borrado físico.

Se añadieron 22 pruebas de usuarios y se adaptaron las de perfil. La ejecución global intermedia verificó 105 pruebas aprobadas, dos omitidas y 309 aserciones. Los resultados finales y límites de la prueba de passkeys se documentan en `docs/evidencias.md`; el callback probado no equivale a una prueba con hardware WebAuthn.

## API HTTP

CU-01 está implementado mediante formulario Livewire, `CameraRegistrationService` y API Sanctum: `GET /api/cameras`, `POST /api/cameras` y `GET /api/cameras/{id}`. La consulta admite búsqueda y paginación. Todas las rutas exigen cuenta activa y correo verificado; solo administrador/coordinador registran cámaras.

Se valida IP opcional, campos obligatorios y longitudes. El inventario puede repetirse y la serie opcional es única; blancos opcionales se convierten en `null`. La restricción de SQLite resuelve también conflictos simultáneos. No se introduce activo fijo como requisito adicional.

Consultar [rutas, validaciones, ejemplos y códigos HTTP](docs/api.md), [colección Postman sin credenciales](docs/api.postman_collection.json) y la colección Bruno heredada en `USC - Inventario de Cámaras/`. Postman está preparado y sus scripts fueron validados; no se declara una ejecución real desde esa aplicación. Las peticiones OpenAI y las confirmaciones se omiten por defecto y requieren activación/revisión explícita.

Las pruebas verifican registro desde Livewire, persistencia, consulta individual, búsquedas, duplicados, valores ausentes, correo verificado, roles y errores. Los ejemplos de documentación son datos ficticios, no inventario institucional.

## Inteligencia artificial

Implementados `IClasificadorIncidencias`, `IncidentManagementService`, el adaptador HTTP real `OpenAIIncidentClassifier`, Livewire y la API `/api/v1/incidencias`. Las incidencias se guardan antes de clasificar. El catálogo inicial es provisional: conectividad, alimentación, imagen, hardware, configuración y otra; requiere validación institucional.

El flujo muestra una vista previa minimizada y requiere autorización para enviar el texto. Se omiten datos conocidos de cámara y usuario, IP, correo, URL y patrones de credenciales/identificadores; la detección automática puede ser incompleta y debe revisarse. Solo se envía descripción textual, nunca vídeo, imágenes, tablas de usuarios ni datos completos de la cámara.

El adaptador usa `POST https://api.openai.com/v1/responses`, `store:false`, JSON Schema estricto y validación local de categoría/justificación. No sigue redirecciones ni reintenta automáticamente. Se acotan timeout (máximo 30 s) y salida (máximo 1000 tokens). `store:false` no sustituye revisar las condiciones de tratamiento y retención del proveedor.

La IA solo propone. Aceptar, corregir o clasificar manualmente persiste categoría final, responsable, fecha y relación con la propuesta. El historial no se borra. Se impiden propuestas de otra incidencia, decisiones duplicadas y respuestas tardías que sustituyan una propuesta vigente. Una solicitud interrumpida se recupera al vencer timeout más 60 s; una configuración ausente no agota el cupo permanente de la incidencia.

Configuración exclusivamente en `.env` o el gestor de secretos del entorno:

```dotenv
OPENAI_ENABLED=false
OPENAI_API_KEY=
OPENAI_MODEL=
OPENAI_TIMEOUT=15
OPENAI_MAX_OUTPUT_TOKENS=300
OPENAI_REQUESTS_PER_MINUTE=5
OPENAI_REQUESTS_PER_DAY=50
OPENAI_ATTEMPTS_PER_INCIDENT=3
```

Para habilitar: configurar una clave válida y un modelo compatible con Responses y salidas estructuradas, activar `OPENAI_ENABLED=true` y refrescar la configuración. Los límites se comprueban en servidor por usuario/incidencia; intentos fallidos también consumen cuota temporal para evitar abuso. El consumo de tokens devuelto por el proveedor se registra cuando existe; no se inventan confianza ni costos. La facturación depende del modelo y de la cuenta API configurada.

**Verificado:** 32 pruebas del bloque, 144 aserciones, con clasificador falso y `Http::fake`: respuestas válidas/malformadas, categoría desconocida, errores HTTP/429, timeout, límites, permisos, consentimiento, confirmación/corrección, alternativa manual y recuperación. **Pendiente:** llamada real controlada con clave/modelo; no se han efectuado solicitudes reales ni cargos a OpenAI. La alternativa manual funciona con la integración deshabilitada.

Referencias de implementación: [Responses API](https://developers.openai.com/api/reference/resources/responses/methods/create) y [salidas estructuradas](https://developers.openai.com/api/docs/guides/structured-outputs?api-mode=responses).

## Pruebas y ejecución reproducible

Pruebas con Pest 5 sobre PHPUnit 13 y SQLite en memoria, sin llamadas externas de IA. Ejecutar:

```powershell
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build
php artisan view:cache
```

La suite incluye casos positivos, negativos, autorización, preservación de identidad, CU-01 desde Livewire/API, integración simulada OpenAI, confirmación humana y comandos operativos. La CI ejecuta la configuración estándar y conserva JUnit. Los resultados y las limitaciones del entorno local se registran en [evidencias verificadas](docs/evidencias.md); no se afirma cobertura porcentual sin medirla.

Última ejecución local del 3 de noviembre de 2026: **116 aprobadas, 2 omitidas, 398 aserciones**. Omisiones: registro público deshabilitado. Pint, build y compilación de plantillas aprobados. La configuración temporal local de Pest y la limitación de lectura de PHPStan se explican en las evidencias; los resultados de CI se registran por separado.

## Docker y ejecución reproducible

La imagen usa PHP 8.5 y una etapa Node 22 para recursos; ejecución con usuario sin privilegios, configuración sin depuración y puerto ligado a localhost. Compose conserva SQLite y respaldos en el volumen `app-storage`; las migraciones se preceden de un respaldo consistente.

Preparar `.env` y una `APP_KEY` válida mediante los pasos anteriores; mantener OpenAI deshabilitado hasta configurarlo de forma controlada. Ejecutar:

```powershell
docker compose build
docker compose up -d --wait
docker compose exec app php artisan app:create-admin
docker compose ps
docker compose down
```

Abrir `http://localhost:8000`. `down` conserva el volumen; **no añadir `--volumes` sobre datos que deban conservarse**. El comando con eliminación de volumen del workflow se limita a recursos efímeros de CI. El servidor incluido sirve a la demostración académica; un despliegue institucional requiere revisar servidor HTTP, TLS, copias, correo y operación.

Docker no está instalado localmente en este entorno. La CI prepara construcción, arranque, salud HTTP y reinicio; su estado real se registra en las evidencias y el PR, sin asumir que los archivos equivalen a una ejecución aprobada.

## Control de versiones y evidencias académicas

Cada bloque incorpora código, pruebas y una actualización de este documento. Los commits conservan la procedencia de los cambios locales y las mejoras de esta continuación. Los PR deben revisarse antes de integrarse.

La rama `codex/consolidacion-mvp-usc` organiza la continuación por bloques; los enlaces verificables de commits/PR y el estado de CI están en [evidencias](docs/evidencias.md). La revisión e integración de un PR se registran solo cuando ocurren.

Se leyeron íntegramente el taller original del Módulo 3 y los textos de Módulos 1/2. Consultar [matriz de diez entregables](docs/matriz-entregables.md), [trazabilidad y diferencias con el diseño](docs/trazabilidad-diseno.md), [informe por bloques](docs/informe-bloques.md) y [guion de demostración](docs/demostracion.md). El PDF académico reúne evidencia y brechas; la entrega al aula, sustentación y video/demostración final corresponden al responsable académico.

## Funcionalidades pendientes y límites

Pendientes: llamada real controlada a OpenAI; revisión institucional del catálogo y permisos; comprobación visual completa y WebAuthn con hardware; revisión/fusión humana del PR y demostración final; validación Docker según su ejecución de CI. No se implementan vídeo, reconocimiento facial, conexiones a cámaras/NVR/DVR ni diagnósticos automáticos. El modelo conceptual más amplio y sus partes diferidas se explican en la trazabilidad.

## Autores, contribuciones y uso responsable de IA

Se conserva el historial del repositorio. No se atribuyen aportaciones a integrantes sin evidencia. Esta continuación utiliza un asistente de IA para apoyar implementación y revisión; los resultados de pruebas y las limitaciones se documentan explícitamente.

El Módulo 1 identifica a **John Edward Caicedo Toro**. Un borrador del Módulo 3 declara autorización docente para trabajo individual; esa autorización original todavía debe acreditarse. Los asistentes de IA no se presentan como integrantes académicos. El autor debe comprender, revisar y sustentar los cambios.

## Licencia y consideraciones institucionales

La licencia de las dependencias no implica una licencia para este proyecto ni una autorización institucional. Pendiente confirmar estos datos con los responsables.
