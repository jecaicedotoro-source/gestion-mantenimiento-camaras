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

Las instrucciones reproducibles se completarán junto con su verificación. `.env` y las bases SQLite son archivos locales y no deben publicarse. No utilizar `migrate:fresh` sobre una base con información real.

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

Se registrarán comandos y resultados después de ejecutar cada bloque en la copia de trabajo. La existencia de archivos de prueba no acredita por sí sola que hayan pasado. Docker todavía no está implementado en esta línea base.

## Control de versiones y evidencias académicas

Cada bloque incorpora código, pruebas y una actualización de este documento. Los commits conservan la procedencia de los cambios locales y las mejoras de esta continuación. Los PR deben revisarse antes de integrarse.

La matriz de diez entregables y el guion de demostración se agregarán en `docs/`. El taller original y los artefactos aprobados de Módulos 1 y 2 deben contrastarse cuando estén disponibles; el prompt maestro aportado por el usuario es la guía de alcance actual.

## Autores, contribuciones y uso responsable de IA

Se conserva el historial del repositorio. No se atribuyen aportaciones a integrantes sin evidencia. Esta continuación utiliza un asistente de IA para apoyar implementación y revisión; los resultados de pruebas y las limitaciones se documentan explícitamente.

## Licencia y consideraciones institucionales

La licencia de las dependencias no implica una licencia para este proyecto ni una autorización institucional. Pendiente confirmar estos datos con los responsables.
