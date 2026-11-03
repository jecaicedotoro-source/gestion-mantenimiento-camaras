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

## Instalación y configuración

Las instrucciones reproducibles se completarán junto con su verificación. `.env` y las bases SQLite son archivos locales y no deben publicarse. No utilizar `migrate:fresh` sobre una base con información real.

## Autenticación, roles y funcionalidades

Roles existentes: administrador, coordinador, técnico y auxiliar. Fortify administra autenticación y verificación; Sanctum protege la API. Las reglas existentes permiten registrar cámaras a administrador y coordinador; CU-01 tiene al administrador como actor principal.

## API HTTP

Base existente: `GET /api/cameras` y `POST /api/cameras`, con `auth:sanctum` y permisos por rol. La documentación completa y una colección sin credenciales se incorporarán en el bloque CU-01.

## Inteligencia artificial

Pendiente en esta línea base: contrato `IClasificadorIncidencias`, adaptador real OpenAI, sugerencias separadas de decisiones humanas, controles de privacidad y pruebas. No hay evidencia aún de una llamada real a OpenAI.

## Pruebas y ejecución reproducible

Se registrarán comandos y resultados después de ejecutar cada bloque en la copia de trabajo. La existencia de archivos de prueba no acredita por sí sola que hayan pasado. Docker todavía no está implementado en esta línea base.

## Control de versiones y evidencias académicas

Cada bloque incorpora código, pruebas y una actualización de este documento. Los commits conservan la procedencia de los cambios locales y las mejoras de esta continuación. Los PR deben revisarse antes de integrarse.

La matriz de diez entregables y el guion de demostración se agregarán en `docs/`. El taller original y los artefactos aprobados de Módulos 1 y 2 deben contrastarse cuando estén disponibles; el prompt maestro aportado por el usuario es la guía de alcance actual.

## Autores, contribuciones y uso responsable de IA

Se conserva el historial del repositorio. No se atribuyen aportaciones a integrantes sin evidencia. Esta continuación utiliza un asistente de IA para apoyar implementación y revisión; los resultados de pruebas y las limitaciones se documentan explícitamente.

## Licencia y consideraciones institucionales

La licencia de las dependencias no implica una licencia para este proyecto ni una autorización institucional. Pendiente confirmar estos datos con los responsables.
