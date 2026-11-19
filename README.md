# Sistema Web de Inventario y Mantenimiento de Cámaras de Seguridad — USC

[![CI y Docker](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/workflows/tests.yml/badge.svg?branch=codex%2Fconsolidacion-mvp-usc)](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/workflows/tests.yml)

**Proyecto académico para la Ciudadela Pampalinda de la Universidad Santiago de Cali (USC).**

Aplicación web para centralizar el inventario de cámaras de seguridad, registrar y consultar incidencias, documentar mantenimientos preventivos y correctivos, administrar usuarios institucionales y apoyar la clasificación de fallas mediante inteligencia artificial **con revisión y decisión humanas**.

> **Alcance:** es un prototipo MVP de gestión administrativa y técnica. No transmite vídeo en directo, no se conecta a NVR/DVR ni controla cámaras de forma remota. El despliegue accesible es una demostración del proyecto; no debe interpretarse como una implantación institucional oficial.

| Recurso | Enlace |
| --- | --- |
| **Aplicación demostrativa (Railway)** | [Abrir sistema](https://usc-camaras-web-production.up.railway.app) |
| **Manual de usuario v1.0 con 19 capturas** | [`docs/manual-usuario.md`](docs/manual-usuario.md) |
| **Documentación de API** | [`docs/api.md`](docs/api.md) |
| **Arquitectura y decisiones técnicas** | [`docs/arquitectura.md`](docs/arquitectura.md) · [ADR-001](docs/adr/ADR-001-stack-tecnologico.md) · [ADR-002](docs/adr/ADR-002-base-datos-sqlite.md) |
| **Pruebas y evidencias** | [Evidencias](docs/evidencias.md) · [Sesión 3 / CI](docs/evidencias-s3-pruebas-ci.md) |
| **Demostración guiada** | [`docs/demostracion.md`](docs/demostracion.md) |

**Estado de referencia:** 19 de noviembre de 2026, rama `codex/consolidacion-mvp-usc`. Las disponibilidades externas y los resultados de ejecución pueden cambiar posteriormente.

## Funcionalidades implementadas

| Área | Funcionalidad |
| --- | --- |
| Autenticación y administración | Inicio/cierre de sesión; cuentas creadas por administradores; roles institucionales; desactivación y reactivación de usuarios; revocación de acceso; registro público deshabilitado |
| Inventario | Alta, listado, búsqueda y consulta de cámaras; validaciones; baja lógica autorizada con conservación del historial |
| Incidencias | Registro y consulta de fallas asociadas a cámaras, categoría confirmada por una persona y seguimiento separado de la reparación |
| IA supervisada | Vista previa del texto minimizado, autorización expresa, sugerencia de categoría, aceptación/corrección humana y clasificación manual alternativa |
| **HU-09** | Registro de mantenimientos con fecha, cámara, tipo, descripción, responsable y resultado |
| **HU-10** | Historial paginado de mantenimientos por cámara, incluso para cámaras dadas de baja |
| **HU-11** | Clasificación de cada mantenimiento como **preventivo** o **correctivo** |
| **HU-12** | Vinculación de un mantenimiento **correctivo** con una incidencia; confirmación explícita de si la avería quedó corregida; trazabilidad de intervenciones y estado técnico |

Las HU-09 a HU-12 se integraron mediante los [PR #16](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/16), [#21](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/21), [#22](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/22) y [#23](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/23). El [manual v1.0 se incorporó en el PR #24](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/24).

### Roles y autorización

Los permisos se comprueban **en el servidor**, no solo ocultando botones. Las reglas siguientes representan la configuración implementada del MVP y están sujetas a validación institucional:

| Operación | Administrador | Coordinador | Técnico | Auxiliar |
| --- | :---: | :---: | :---: | :---: |
| Consultar inventario e historial | Sí | Sí | Sí | Sí |
| Registrar cámaras | Sí | Sí | No | No |
| Dar de baja cámaras | Sí | No | No | No |
| Consultar incidencias | Sí | Sí | Sí | Sí |
| Registrar y clasificar incidencias | Sí | Sí | Sí | No |
| Registrar mantenimientos | Sí | Sí | Sí | No |
| Administrar usuarios | Sí | No | No | No |

Las cuentas inactivas o sin el rol requerido no pueden ejecutar las operaciones protegidas. Una cuenta de demostración no constituye un entorno aislado ni un mecanismo automático de reversión de datos: los permisos efectivos dependen del rol asignado y de los controles del servidor. **No publicar contraseñas ni tokens de acceso.**

### Reglas de integridad y trazabilidad

- El **código de inventario puede repetirse**; el número de serie, cuando se informa, **debe ser único**. Se permiten números de serie nulos.
- El alta de cámaras valida campos obligatorios, formatos y dirección IP opcional.
- La baja de cámaras es lógica: conserva el historial y evita nuevas intervenciones sobre equipos retirados.
- Las incidencias conservan por separado la **categoría revisada** (clasificación) y el **estado técnico** (pendiente/corregida).
- Un mantenimiento asociado a una incidencia debe ser **correctivo**, pertenecer a la misma cámara y contar con una confirmación humana explícita de reparación. Registrar una intervención fallida no marca la incidencia como corregida.
- El historial mantiene las intervenciones registradas y sus vinculaciones; la consulta no modifica los datos.

## Tecnologías y arquitectura

| Componente | Tecnología |
| --- | --- |
| Backend | PHP 8.5 en los entornos verificados; Laravel 13 |
| Interfaz | Blade, Livewire 4, Tailwind CSS 4 y Flux UI |
| Persistencia | SQLite con Eloquent ORM y migraciones Laravel |
| Autenticación / API | Laravel Fortify y Sanctum |
| IA | Contrato `IClasificadorIncidencias`; adaptadores OpenAI (predeterminado) y Groq (alternativo desactivado por defecto) |
| Calidad | Pest/PHPUnit, Laravel Pint, PHPStan (Larastan), pruebas HTTP/API |
| Construcción / entrega | Composer, Node.js 22, npm, Vite Plus, GitHub Actions |
| Ejecución | Laravel Herd en desarrollo Windows, Docker Compose para ejecución reproducible y Railway para la demostración desplegada |

Arquitectura **monolítica modular**: las vistas, componentes Livewire, servicios de aplicación, modelos Eloquent y controladores REST pertenecen a una misma aplicación Laravel; no hay frontend y backend desplegados como microservicios independientes.

Rutas y código representativos: `routes/web.php`, `routes/api.php`, `app/Services`, `app/Policies`, `app/Livewire`, `app/Http/Controllers/Api`, `app/Contracts` y `app/Infrastructure/AI`.

## Instalación local (Windows / PowerShell)

**Requisitos:** PHP compatible con `composer.json` (se ha verificado PHP 8.5), Composer 2, Node.js 22, npm y extensiones PHP requeridas por Laravel y SQLite. Se recomienda una copia del repositorio fuera de OneDrive y usar VS Code o Laravel Herd.

En una **copia nueva** del proyecto:

```powershell
git clone https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras.git
cd gestion-mantenimiento-camaras
git switch codex/consolidacion-mvp-usc

composer install
Copy-Item .env.example .env
php artisan key:generate
if (-not (Test-Path database/database.sqlite)) {
    New-Item -ItemType File database/database.sqlite | Out-Null
}
php artisan migrate --force
php artisan db:seed --force
php artisan app:create-admin
npm ci
npm run build
php artisan serve
```

Abrir `http://127.0.0.1:8000`. El comando `app:create-admin` solicita los datos de la cuenta administrativa; el proyecto no incluye una contraseña institucional predeterminada.

**Si ya tienes una instalación y datos existentes**, no repitas la inicialización ni sustituyas `.env` o `database.sqlite`. Comprueba el estado del repositorio, crea un respaldo y aplica únicamente las migraciones revisadas:

```powershell
git status
php artisan app:backup-database
php artisan migrate --force
```

Nunca ejecutes `migrate:fresh`, `db:wipe` ni borres un volumen o base con información que debas conservar. Protege `.env`, las copias de seguridad, los tokens y los archivos SQLite. Para entornos remotos configura `APP_URL` y HTTPS correctamente; el correo saliente requiere un transporte real si se desea recuperar contraseñas por email.

## API REST

La API requiere autenticación **Sanctum**, cuenta activa, autorización por rol y límites de solicitudes. Rutas principales:

| Método | Ruta | Propósito |
| --- | --- | --- |
| GET | `/api/cameras` | Listar y buscar cámaras |
| POST | `/api/cameras` | Registrar cámara |
| GET | `/api/cameras/{id}` | Consultar cámara |
| DELETE | `/api/cameras/{id}` | Baja lógica de cámara (administrador) |
| POST | `/api/v1/incidencias` | Registrar incidencia |
| GET | `/api/v1/incidencias/{id}` | Consultar incidencia |
| GET | `/api/v1/incidencias/{id}/sugerencias-ia/vista-previa` | Revisar texto antes de la IA |
| POST | `/api/v1/incidencias/{id}/sugerencias-ia` | Solicitar sugerencia con consentimiento |
| PATCH | `/api/v1/incidencias/{id}/categoria` | Guardar decisión humana |
| POST | `/api/v1/mantenimientos` | Registrar mantenimiento |

Las rutas web adicionales permiten consultar el historial por cámara y abrir el formulario de mantenimiento correctivo desde una incidencia. Para parámetros, ejemplos JSON, permisos y respuestas HTTP consulta [la guía de API](docs/api.md) y la [colección Postman](docs/api.postman_collection.json). La guía API es anterior a HU-12; el flujo actualizado está documentado en el [manual de usuario](docs/manual-usuario.md).

## Clasificación de incidencias mediante IA

El sistema utiliza `IClasificadorIncidencias` para desacoplar el dominio del proveedor externo. **OpenAI es la opción predeterminada del código**; **Groq es una alternativa** que debe seleccionarse y habilitarse expresamente. La disponibilidad efectiva en cada entorno depende de sus propias variables y credenciales.

Flujo operativo:

1. Una persona autorizada registra la incidencia.
2. El sistema presenta una **vista previa minimizada** de la descripción y exige autorización para enviarla.
3. El proveedor, si está disponible, propone una de las categorías admitidas.
4. Una persona **acepta, corrige o clasifica manualmente**; solo entonces se guarda la categoría final.
5. La clasificación no acredita que se haya reparado la avería.

Configuración orientativa en `.env`, **sin incluir claves reales en el repositorio**:

```dotenv
AI_PROVIDER=openai
OPENAI_ENABLED=false
OPENAI_API_KEY=
OPENAI_MODEL=
OPENAI_TIMEOUT=15
OPENAI_MAX_OUTPUT_TOKENS=300

# Alternativa opcional, desactivada por defecto:
GROQ_ENABLED=false
GROQ_API_KEY=
GROQ_MODEL=openai/gpt-oss-20b
```

Se implementan límites de solicitudes e intentos, validación de salida, manejo de errores y alternativa manual. Las pruebas automatizadas del proveedor usan dobles y HTTP simulado; se registró además **una comprobación real y controlada de OpenAI con texto sintético** en evidencias históricas. Esa comprobación puntual no garantiza que la cuenta, cuota o proveedor estén disponibles actualmente, ni mide precisión clínica/técnica de la clasificación.

No enviar vídeo, imágenes, credenciales ni datos personales innecesarios. La minimización es una ayuda y **no sustituye la revisión del texto por el usuario**.

## Mantenimientos y resolución de incidencias (HU-09 a HU-12)

Desde la interfaz puede registrarse un mantenimiento **preventivo** o **correctivo** para una cámara activa. El historial por cámara incluye fecha, descripción, responsable, resultado y, cuando corresponda, vinculación a una incidencia.

Para resolver una incidencia mediante **HU-12**:

1. Abrir una incidencia con estado técnico `Pendiente de corrección` y seleccionar **Registrar mantenimiento correctivo**.
2. Comprobar la cámara y la incidencia precargadas; documentar la intervención.
3. Indicar explícitamente si **el fallo quedó corregido**.
4. Guardar. Si se confirmó la reparación, el estado técnico pasa a `Corregida`; en caso contrario, permanece pendiente.
5. Consultar el historial para verificar la trazabilidad.

La confirmación de reparación es independiente de la clasificación asistida por IA. Una incidencia ya corregida no admite nuevas intervenciones vinculadas bajo las validaciones actuales. Consulta los pasos ilustrados en [el manual, sección 12](docs/manual-usuario.md#seccion-12).

## Docker

El repositorio incluye `Dockerfile`, `compose.yaml` y `docker/entrypoint.sh`. La imagen utiliza PHP 8.5 y construye recursos con Node 22; Compose expone el servicio localmente en `127.0.0.1:8000` y conserva la aplicación/SQLite en el volumen `app-storage`.

Con Docker Desktop operativo, un `.env` propio y `APP_KEY` configurada:

```powershell
docker compose build
docker compose up -d --wait
docker compose ps
# Primera ejecución, si aún no existe administrador:
docker compose exec app php artisan app:create-admin
# Para detener, conservando el volumen:
docker compose down
```

**No utilices `docker compose down --volumes` en instalaciones con datos reales.** El flujo CI sí elimina sus volúmenes porque son efímeros y contienen únicamente información ficticia.

## Demostración desplegada en Railway

- **Proyecto:** `usc-camaras-demo`.
- **Entorno:** `production` de Railway (entorno de demostración académica, no implantación institucional).
- **Servicio:** `usc-camaras-web`.
- **URL:** https://usc-camaras-web-production.up.railway.app
- **Persistencia:** volumen `usc-camaras-sqlite` montado en `/app/storage/app/data`.
- **Comprobación:** el 19/11/2026 Railway mostró servicio **Online**, una réplica funcionando, despliegue **SUCCESS** y sin incidencias activas en su estado consultado.
- **HU-12:** el despliegue y la ejecución de su migración se verificaron en Railway; las capturas funcionales quedaron incorporadas al [manual v1.0](docs/manual-usuario.md).

La configuración de Railway (volumen, claves, entorno, política de despliegue y HTTPS) no debe reproducirse desde un `.env` público ni alterarse sin respaldo y revisión. La rama del repositorio y el despliegue activo pueden avanzar en momentos distintos: la presencia de un commit en GitHub no demuestra por sí sola su ejecución en Railway.

## Calidad, pruebas e integración continua

El workflow [`.github/workflows/tests.yml`](.github/workflows/tests.yml) ejecuta dos trabajos:

- **`ci`:** dependencias bloqueadas, compilación de recursos, Laravel Pint, PHPStan y pruebas Pest/PHPUnit con SQLite aislada; conserva evidencia JUnit.
- **`docker`:** construcción, healthcheck HTTP, comprobación de migraciones y prueba de persistencia de una cámara ficticia tras reiniciar el contenedor.

**Verificación reciente:** los trabajos `ci` y `docker` del [PR #24 / ejecución GitHub Actions del 19/11/2026](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/44543280744) concluyeron satisfactoriamente. La [evidencia de la sesión 3](docs/evidencias-s3-pruebas-ci.md) documenta **167 pruebas aprobadas, dos omitidas y 714 aserciones** a fecha 17/11/2026; **no se presenta esa cifra histórica como el recuento exacto de la suite posterior a HU-12**. No se declara un porcentaje de cobertura sin medición.

Para ejecutar comprobaciones en un entorno de pruebas aislado:

```powershell
composer test
npm run build
# O ejecutar solo las pruebas Laravel:
php artisan test
```

Para los cinco casos de la matriz QA existe también el [lanzador PowerShell de Pest aislado](docs/qa/ejecutar-pest-aislado.ps1), que evita trabajar sobre la base de datos ordinaria.

## Documentación y trazabilidad

- [Manual de usuario v1.0 con capturas](docs/manual-usuario.md)
- [API REST y códigos HTTP](docs/api.md) y [colección Postman](docs/api.postman_collection.json)
- [Arquitectura](docs/arquitectura.md), [ADR de stack](docs/adr/ADR-001-stack-tecnologico.md) y [ADR de SQLite](docs/adr/ADR-002-base-datos-sqlite.md)
- [Especificación de requisitos](docs/requisitos.md) y [trazabilidad respecto al diseño](docs/trazabilidad-diseno.md)
- [Matriz de entregables](docs/matriz-entregables.md), [informe de desarrollo](docs/informe-bloques.md) y [guion de demostración](docs/demostracion.md)
- [Evidencias históricas y ejecución reproducible](docs/evidencias.md) y [pruebas/CI de sesión 3](docs/evidencias-s3-pruebas-ci.md)

**Nota documental:** algunos informes, la especificación de requisitos y la guía API fueron redactados en iteraciones anteriores y contienen estados históricos de tareas pendientes. Para el estado incorporado más reciente de HU-09 a HU-12 deben contrastarse el código, los PR fusionados y el manual v1.0.

## Límites y consideraciones pendientes

- Validación institucional final de roles, categorías, uso operativo y políticas de retención.
- Comprobaciones funcionales integrales con datos autorizados; las pruebas automatizadas y capturas no sustituyen una aceptación institucional formal.
- Evaluación de calidad de las sugerencias de IA con un conjunto representativo de incidencias; no se declara precisión porcentual.
- Seguridad y operación de una eventual implantación institucional: control de acceso, respaldos, restauración, observabilidad, HTTPS, gestión de secretos y autorización para usar información real.
- El sistema no incluye streaming de vídeo, control de cámaras, detección automática de fallos, reconocimiento facial ni integración NVR/DVR.

## Autoría, asistencia de IA y licencia

Desarrollo académico: **John Edward Caicedo Toro**. El proyecto utiliza herramientas de IA como apoyo para análisis, documentación, implementación y revisión; sus sugerencias requieren comprensión, verificación técnica y responsabilidad humana. El historial de commits, las pruebas y los PR conservan la trazabilidad del trabajo.

Las licencias de las dependencias no conceden automáticamente una licencia al código y materiales propios del proyecto. **El régimen de uso y publicación institucional debe confirmarse con los responsables** antes de una distribución distinta de la demostración académica.
