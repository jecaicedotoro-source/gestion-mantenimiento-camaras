# Gestión del inventario y mantenimiento de cámaras — USC

[![CI](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/workflows/tests.yml/badge.svg?branch=codex%2Fconsolidacion-mvp-usc)](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/workflows/tests.yml)

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

Se corrigió la distribución compartida de las páginas internas: el contenido principal vuelve a ser hijo directo del mismo contenedor que la barra lateral, como exige Flux. Esto evita que el menú se superponga a los formularios y que el contenido empiece debajo de la altura de la navegación; el enlace de salto accesible apunta directamente al contenido principal.

La corrección de distribución se comprobó mediante compilación de recursos y plantillas y siete pruebas existentes de acceso a cámaras y panel, todas aprobadas con nueve aserciones; queda la comprobación visual en el navegador del usuario.

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

En Linux/macOS sustituir `Copy-Item` por `cp` y crear la base mediante `touch database/database.sqlite`. El operador autoriza la cuenta inicial desde la consola con contraseña oculta fuerte y correo único. Los seeders solo crean/actualizan roles; nunca una contraseña predeterminada. El registro público está deshabilitado.

Abrir `http://localhost:8000`. Configurar `APP_URL` con el origen real para passkeys y enlaces de recuperación; fuera de localhost, WebAuthn requiere un contexto seguro apropiado. En Herd conservar el proyecto original y su configuración existente; aplicar los cambios revisados después de respaldar.

Variables principales: `APP_NAME`, `APP_URL`, `APP_KEY`, `APP_LOCALE=es`, `DB_CONNECTION=sqlite`, `DB_DATABASE` opcional (por defecto `database/database.sqlite`), `SESSION_DRIVER=database`, `CACHE_STORE=database`. El alta y el inicio de sesión no necesitan SMTP; si se utiliza la recuperación por correo, configurar un transporte operativo. `MAIL_MAILER=log` es solo para desarrollo y sus registros deben mantenerse privados.

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

Roles existentes: administrador, coordinador, técnico y auxiliar. Fortify administra la autenticación y Sanctum protege la API. Las reglas existentes permiten registrar cámaras a administrador y coordinador; CU-01 tiene al administrador como actor principal.

El alta institucional la autoriza el administrador: una cuenta activa puede acceder con sus credenciales y los permisos de su rol sin confirmar un enlace por correo. El correo sigue siendo obligatorio, válido y único; el administrador debe comprobarlo antes de crear la cuenta, pues también se usa en la recuperación de contraseña. Se conserva `email_verified_at` como campo histórico sin usarlo para conceder acceso ni declarar una verificación nueva. El registro público, los permisos y el bloqueo de cuentas inactivas conservan su comportamiento.

Verificación local del cambio: **122 pruebas aprobadas, 2 omitidas y 454 aserciones**, incluidos acceso con una cuenta creada por el administrador, ausencia de notificaciones de confirmación, rutas de verificación y registro público inaccesibles, permisos por rol y rechazo de cuentas inactivas. No requiere migraciones.

### Bloque de usuarios consolidado

`UserManagementService` concentra las operaciones y `UserPolicy` autoriza cada acción en el servidor. Los cambios se ejecutan en transacciones; en SQLite se adquiere un bloqueo de escritura antes de contar administradores activos.

Las cuentas se desactivan y reactivan conservando identidad e historiales. Un administrador no puede desactivarse ni cambiar su propio rol; debe permanecer un administrador activo. Se revocan tokens Sanctum, sesiones y cookies de recuerdo al desactivar o modificar credenciales, correo o rol. `auth_version` impide revivir sesiones antiguas tras reactivar; también se invalidan desafíos 2FA previos. Contraseña y callback de passkeys rechazan cuentas inactivas. El perfil de una cuenta no administrativa permite desactivación con contraseña; no hay borrado físico.

Se añadieron 22 pruebas de usuarios y se adaptaron las de perfil. La ejecución global intermedia verificó 105 pruebas aprobadas, dos omitidas y 309 aserciones. Los resultados finales y límites de la prueba de passkeys se documentan en `docs/evidencias.md`; el callback probado no equivale a una prueba con hardware WebAuthn.

## API HTTP

### Baja de cámaras y conservación del historial

La baja está reservada a administradores activos. Retira la cámara de las consultas y los indicadores del inventario y conserva su registro, número de serie e incidencias. Las incidencias existentes siguen consultables y las cámaras retiradas dejan de admitir incidencias nuevas.

`DELETE /api/cameras/{id}` requiere un token Sanctum de administrador y devuelve HTTP 200 al dar de baja. Devuelve 403 si el usuario carece de permiso y 404 si la cámara no existe o ya fue retirada. En instalaciones existentes, ejecuta un respaldo y aplica la migración de baja antes de usar esta versión.

### Confirmación de baja en el inventario

En **Inventario de cámaras**, el administrador encuentra **Dar de baja** junto a cada registro. La confirmación explica que la cámara dejará de aparecer en el inventario y sus incidencias seguirán disponibles. Al aceptar, el sistema comprueba el permiso en el servidor, actualiza el listado y muestra un mensaje de éxito. Cancelar mantiene la cámara en el inventario.

CU-01 está implementado mediante formulario Livewire, `CameraRegistrationService` y API Sanctum: `GET /api/cameras`, `POST /api/cameras` y `GET /api/cameras/{id}`. La consulta admite búsqueda y paginación. Todas las rutas exigen autenticación y cuenta activa; solo administrador/coordinador registran cámaras.

Se valida IP opcional, campos obligatorios y longitudes. El inventario puede repetirse y la serie opcional es única; blancos opcionales se convierten en `null`. La restricción de SQLite resuelve también conflictos simultáneos. No se introduce activo fijo como requisito adicional.

Consultar [rutas, validaciones, ejemplos y códigos HTTP](docs/api.md), [colección Postman sin credenciales](docs/api.postman_collection.json) y la colección Bruno heredada en `USC - Inventario de Cámaras/`. Postman está preparado y sus scripts fueron validados; no se declara una ejecución real desde esa aplicación. Las peticiones OpenAI y las confirmaciones se omiten por defecto y requieren activación/revisión explícita.

Las pruebas verifican registro desde Livewire, persistencia, consulta individual, búsquedas, duplicados, valores ausentes, acceso sin confirmación de correo, roles y errores. Los ejemplos de documentación son datos ficticios, no inventario institucional.

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

### Ejecución de los cinco casos de la matriz — 7 de noviembre de 2026

Se inspeccionó `Planificacion_Ejecucion_Cinco_Casos_Camaras.xlsx` y se añadieron los cinco casos exactos en `tests/Feature/Api/CameraExecutionPlanTest.php`: alta/consulta/persistencia, código vacío, código repetido permitido, serie duplicada rechazada y serie JSON nula. Usan datos sintéticos, token Sanctum efímero real y SQLite en memoria. CP-03/04 recrean su precondición CP-01 para no depender del orden.

Resultado local de esta revisión, sobre `78f38eb` más las nuevas pruebas: **5/5 aprobados, 128 aserciones**. Suite completa: **129 aprobados, 2 omitidos, 601 aserciones**, sin fallos ni errores. Las omisiones corresponden al registro público deshabilitado. Pint pasó sobre el archivo nuevo. La cobertura de criterios es 5/5; no se midió cobertura de líneas/ramas.

Los mismos cinco casos también aprobaron mediante **10 solicitudes HTTP reales y 16 aserciones** contra un servidor temporal del código nuevo en `127.0.0.1:8017`, con SQLite y token QA propios: 201/200 para altas/consultas, 422 para código vacío/serie duplicada y NULL verificado por PDO. No había instancia accesible en 8000/8001. El servidor temporal se detuvo al terminar; las bases reales nueva y antigua conservaron sus SHA-256.

Para repetir sin heredar la base ni las claves del `.env`, utilizar el lanzador aislado:

```powershell
./docs/qa/ejecutar-pest-aislado.ps1 -Scope plan
./docs/qa/ejecutar-pest-aislado.ps1 -Scope full
# Si PHP no está disponible en PATH, añadir -PhpPath con la ruta del ejecutable.
```

El lanzador verifica el aislamiento antes de `RefreshDatabase`, desactiva OpenAI, utiliza SQLite `:memory:`, separa cachés/vistas y restaura las variables del proceso al terminar. Conserva JUnit, preflight, comando y salida depurada en la carpeta que indica; acepta `-EvidenceDirectory`. Se verificó realmente con los cinco casos: **5 aprobados / 128 aserciones**, exit 0. En Windows reutiliza `phpunit.windows.xml` si existe; en otro caso usa `phpunit.xml`. No instala dependencias ni limpia cachés de la instancia.

La regresión complementaria de incidencias/OpenAI aprobó **32 pruebas / 144 aserciones** con dobles y HTTP simulado. Separadamente, una llamada **real** al adaptador OpenAI respondió HTTP 200 con categoría `imagen`, modelo configurado `gpt-5.4-nano`, 141 tokens de entrada y 47 de salida. Solo se envió texto sintético; no se escribieron incidencias reales. Una muestra valida conectividad y contrato, no precisión general ni costo facturado. Los resultados, comandos, limitaciones y evidencias depuradas están en [el informe de esta ejecución](docs/evidencias/2026-11-07-cinco-casos/Informe_ejecucion.md).

Pruebas con Pest 5 sobre PHPUnit 13 y SQLite en memoria, sin llamadas externas de IA. Ejecutar:

```powershell
php artisan test
vendor/bin/pint --test
vendor/bin/phpstan analyse --no-progress --memory-limit=1G
npm run build
php artisan view:cache
```

La suite incluye casos positivos, negativos, autorización, preservación de identidad, CU-01 desde Livewire/API, integración simulada OpenAI, confirmación humana y comandos operativos. La CI ejecuta la configuración estándar y conserva JUnit. Los resultados y las limitaciones del entorno local se registran en [evidencias verificadas](docs/evidencias.md); no se afirma cobertura porcentual sin medirla.

Las excepciones públicas de la API se normalizan al español y ocultan trazas incluso con depuración local activa. Se conservan estado y cabeceras de límite (`Retry-After`), errores de validación por campo y la respuesta manual alternativa del servicio IA. Este último ajuste añadió seis pruebas aprobadas con 37 aserciones; su integración global se confirma en la CI final.

**CI final del incremento aprobada:** [ejecución 41635991294](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41635991294), sobre el código `12212273`: **122 aprobadas, 2 omitidas y 435 aserciones**, PHPStan sin errores, Pint y build correctos. Docker verificó arranque, API y persistencia idéntica tras reiniciar. Se conservan [respuestas HTTP sintéticas sin credenciales](docs/evidencias/http-cu01.json).

Ejecución local del incremento inicial, anterior al ajuste de confirmación de correo: **116 aprobadas, 2 omitidas, 398 aserciones**. Omisiones: registro público deshabilitado. Pint, build y compilación de plantillas aprobados. La configuración temporal local de Pest y la limitación de lectura de PHPStan se explican en las evidencias; los resultados de CI se registran por separado.

El [PR #1](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/1) está abierto en borrador. La primera CI Linux confirmó los mismos 116 casos aprobados con configuración estándar y detectó seis avisos de tipos que se corrigieron sin desactivar reglas. Se añadieron exclusiones de credenciales Composer y cachés locales al contexto Docker; el estado final se mantiene en las evidencias.

La [segunda CI](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41634878889) aprobó completamente ambos trabajos: PHPStan sin errores, Pint/build y pruebas; Docker construyó, arrancó y verificó CU-01 mediante HTTP y persistencia del mismo registro después de reiniciar.

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

Docker no está instalado localmente en este entorno. La ejecución remota de CI sí verificó construcción, arranque, salud HTTP y persistencia después de reinicio; sus enlaces están en las evidencias. Esto acredita la demostración técnica reproducible y no implica un despliegue institucional de producción.

## Control de versiones y evidencias académicas

Cada bloque incorpora código, pruebas y una actualización de este documento. Los commits conservan la procedencia de los cambios locales y las mejoras de esta continuación. Los PR deben revisarse antes de integrarse.

La rama `codex/consolidacion-mvp-usc` organiza la continuación por bloques; los enlaces verificables de commits/PR y el estado de CI están en [evidencias](docs/evidencias.md). La revisión e integración de un PR se registran solo cuando ocurren.

Los paquetes de código incluyen este README y las evidencias. Se retiró la exclusión heredada del starter kit que omitía el README al exportar Git; el ZIP de entrega se comprueba sin secretos, bases ni dependencias locales.

Se leyeron íntegramente el taller original del Módulo 3 y los textos de Módulos 1/2. Consultar [matriz de diez entregables](docs/matriz-entregables.md), [trazabilidad y diferencias con el diseño](docs/trazabilidad-diseno.md), [informe por bloques](docs/informe-bloques.md) y [guion de demostración](docs/demostracion.md). El PDF académico reúne evidencia y brechas; la entrega al aula, sustentación y video/demostración final corresponden al responsable académico.

## Funcionalidades pendientes y límites

Pendientes: llamada real controlada a OpenAI; revisión institucional del catálogo y permisos; comprobación visual completa y WebAuthn con hardware; revisión/fusión humana del PR y demostración final; validación Docker según su ejecución de CI. No se implementan vídeo, reconocimiento facial, conexiones a cámaras/NVR/DVR ni diagnósticos automáticos. El modelo conceptual más amplio y sus partes diferidas se explican en la trazabilidad.

## Autores, contribuciones y uso responsable de IA

Se conserva el historial del repositorio. No se atribuyen aportaciones a integrantes sin evidencia. Esta continuación utiliza un asistente de IA para apoyar implementación y revisión; los resultados de pruebas y las limitaciones se documentan explícitamente.

El Módulo 1 identifica a **John Edward Caicedo Toro**. Un borrador del Módulo 3 declara autorización docente para trabajo individual; esa autorización original todavía debe acreditarse. Los asistentes de IA no se presentan como integrantes académicos. El autor debe comprender, revisar y sustentar los cambios.

## Licencia y consideraciones institucionales

La licencia de las dependencias no implica una licencia para este proyecto ni una autorización institucional. Pendiente confirmar estos datos con los responsables.
