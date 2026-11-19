# Evidencias verificadas del incremento

Fecha de revisión: **3 de noviembre de 2026**, zona de referencia Europe/Madrid. Este registro distingue ejecución real, preparación y comprobaciones pendientes. No constituye la entrega definitiva al aula virtual.

## Procedencia y conservación

Repositorio privado existente: [gestion-mantenimiento-camaras](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras). Referencias iniciales: `main` en `9ef06ca`; `feature/registro-camaras` en `3ae9d87`. No había README en esas referencias. Se conservaron avances locales adicionales de CU-01 en un commit de base separado y se trabajó sobre una copia en la carpeta del chat; no se modificó la instalación original de Herd ni se migró su SQLite.

Se leyeron el taller original completo, el texto de Módulos 1/2 y el borrador local del Módulo 3. El archivo del taller se pudo leer después de que el usuario lo cerrara. Los documentos originales, bases y secretos permanecen fuera de Git. Las figuras incrustadas de los diseños anteriores no se interpretaron visualmente; las diferencias verificadas se explican en [trazabilidad](trazabilidad-diseno.md).

## Verificación local

| Comprobación | Resultado verificado |
| --- | --- |
| Versiones | PHP 8.5.12; Laravel 13.39.0; Livewire 4.4.8; Flux 2.21.0; Fortify 1.41.0; Sanctum 4.3.3 |
| Primera suite integrada | 105 aprobadas, 2 omitidas, 309 aserciones; estado intermedio previo a últimos casos |
| Bloque incidencias/IA | 32 aprobadas, 144 aserciones; fakes y respuestas HTTP simuladas |
| Comandos operativos | 4 aprobadas, 33 aserciones; respaldo SQLite validado con PDO, altas fuertes y rechazo de duplicados/débiles |
| Recursos frontend | `npm run build`, exit 0; recursos y manifiesto generados |
| Plantillas | `php artisan view:cache`, exit 0 |
| Formato | Pint global aprobado después de correcciones de estilo |
| Base aislada de demostración | Respaldo consistente y 12 migraciones aplicadas; `RoleSeeder` ejecutado |
| Colección Postman | JSON válido, 23 scripts con sintaxis validada; 11 peticiones. No ejecutada en Postman |
| Docker/CI YAML | Sintaxis de ambos YAML validada con Symfony; script de inicio con LF |

**Suite integrada local, anterior al último ajuste de idioma API:** 118 casos, **116 aprobados, 2 omitidos, 398 aserciones**, exit 0; duración informada por Pest 9,425 s. Se generó JUnit. Las omisiones corresponden a registro público deshabilitado mediante Fortify; no son pruebas fallidas. Comando: PHP 8.5 directo, `vendor/bin/pest --configuration phpunit.windows.xml --no-coverage --log-junit ../junit-final.xml`, con caché de vistas aislada. Pint global terminó `passed`.

Una ejecución previa detectó dos aserciones heredadas de seguridad que esperaban texto inglés; se adaptaron para comprobar las etiquetas traducidas. No se cambió la funcionalidad ni se eliminaron las pruebas.

En el entorno restringido de Windows, PHP `is_readable()` devuelve falso para archivos que sí puede leer. PHPUnit se detenía antes de ejecutar. Se usó una configuración temporal equivalente que omite el bootstrap redundante ya cargado por Pest, con SQLite en memoria; no se modificaron dependencias, ACL ni la configuración estándar de CI. PHPStan local se detiene al comprobar la legibilidad del archivo de configuración; su aprobación requiere ejecución real en CI.

## Demostración HTTP y persistencia

Las pruebas automatizadas ejecutan solicitudes HTTP dentro del kernel Laravel y comprueban registros reales en SQLite aislado, autorización y códigos 201/200/401/403/404/422. La tentativa adicional de peticiones por socket local fue bloqueada por el entorno; no se presenta como una demostración HTTP externa aprobada. El token temporal de esa tentativa se revocó y no se publicó.

La CI contiene una demostración reproducible sobre Docker con datos ficticios: POST de cámara (201), serie duplicada (422), consulta (200), reinicio del contenedor y comparación del mismo registro persistido. El token efímero se captura sin imprimirlo y no se incluye en artefactos; únicamente se conservan respuestas JSON sintéticas. Su ejecución y los artefactos se enlazarán con el resultado real.

## IA: diferencia entre código y conectividad

El adaptador OpenAI es HTTP real, con endpoint fijo, configuración segura, salida estructurada y validación. Las pruebas utilizan sustitutos explícitos y `Http::fake`; no verifican conectividad con una cuenta del proveedor. No se efectuó una llamada real ni se conocen costos reales de esta entrega. Falta configurar clave/modelo y realizar una solicitud controlada con texto ficticio revisado. La clasificación manual y la confirmación humana sí están cubiertas por pruebas.

## GitHub y CI

Rama publicada: `codex/consolidacion-mvp-usc`. [PR #1 en borrador](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/1), abierto y sin fusionar. El contenido de la rama inicial publicada se comprobó contra la copia local mediante el mismo árbol Git `431bdec1ea3a144b378b2f7572d8b30594d30d96`. Los SHA de commits locales y remotos difieren porque la publicación se realizó mediante el conector GitHub; el contenido es equivalente y no se alteró el historial previo.

| Bloque publicado | Commit remoto |
| --- | --- |
| chore: conservar avances locales CU-01 y documentar auditoría inicial | [9a9c551](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/9a9c5512f59e1d06403c199b1292cf48a4b23093) |
| feat: consolidar usuarios con policies desactivación y revocación de acceso | [314440b](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/314440be384e52cb2c316266466bd91f6b18b658) |
| style: renovar panel y navegación institucional con etiquetas en español | [46b6e46](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/46b6e46cfba12d3f840c481639344293c28b20e3) |
| feat: completar CU-01 con validación persistencia y API documentada | [f898c31](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/f898c31181b5e6996b92a89a0f0aeb8011bf1a36) |
| feat: integrar incidencias y OpenAI con revisión humana y resiliencia | [413558b](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/413558b1725dfef07dc2ca8f9c0334ca76391cf8) |
| build: integrar seguridad y APIs con Docker CI y comandos operativos | [69b1b0e](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/69b1b0e3d87130e04d1e80656a12afe6aa265ff0) |
| docs: contrastar diez entregables y registrar evidencia reproducible | [7d4bf09](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/7d4bf09573a27c81cfc7e255a08e55d502e6b02d) |
| fix: resolver tipos detectados en CI y excluir secretos de builds locales | [1889b98](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/1889b985dc89fbe8178656a05816567929f88da1) |
| fix: esperar salud del contenedor antes de comprobar persistencia tras reinicio | [cb96191](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/cb96191e4bee8359f67a3154d21cd8b18ea75347) |
| fix: responder errores API en español sin detalles internos y conservar evidencias CI | [1221227](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/commit/12212273b1708d44154a1c597b8a5b20d42feb2f) |

Primera CI remota iniciada: [Actions 41633905889](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41633905889), con trabajos `ci` y `docker`. Su resultado se registra tras comprobarlo; no se deduce de la existencia del workflow. La revisión humana y la fusión se mantienen pendientes hasta efectuarse; los subagentes no se consideran revisores académicos ni un segundo integrante.

La primera ejecución Linux confirmó **116 aprobadas, 2 omitidas y 398 aserciones**, con el archivo estándar `phpunit.xml`. El trabajo CI falló únicamente en PHPStan: seis avisos de tipos (fechas Carbon, relación genérica, arrays de reglas/entrada, acceso nullsafe redundante y conversión de configuración Sanctum). Se corrigieron sin baselines ni exclusiones de análisis; falta confirmar la nueva ejecución. JUnit de esa ejecución: [artefacto de pruebas](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41633905889/artifacts/13228549524), retención siete días.

Se reforzó `.dockerignore` para excluir también `auth.json`, cachés PHP locales que podrían incorporar configuración sensible y `public/storage`. La imagen regenera sus cachés desde las dependencias propias; no reutiliza la configuración del equipo del operador.

La primera ejecución Docker sí construyó la imagen, aplicó migraciones, arrancó con salud correcta y verificó `/up`, POST 201, rechazo de serie duplicada 422 y consulta GET previa al reinicio. El trabajo se detuvo por `curl (52) Empty reply from server` al consultar inmediatamente después del reinicio; no demuestra pérdida de datos. Se corrigió la comprobación para esperar de nuevo al healthcheck de Compose antes de consultar. La persistencia tras reinicio requiere confirmación en la segunda ejecución. [Respuestas sintéticas del primer intento](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41633905889/artifacts/13229597488), retención siete días.

**Segunda CI aprobada completamente:** [Actions 41634878889](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41634878889), sobre `cb96191e4bee8359f67a3154d21cd8b18ea75347`. Los trabajos `ci` y `docker` concluyeron `success`. PHPStan informó `[OK] No errors`; Pest confirmó **116 aprobadas, 2 omitidas y 398 aserciones**; Pint y compilación frontend aprobados. Docker construyó, arrancó sano, aplicó las doce migraciones y comprobó registro HTTP 201, serie duplicada 422, consulta y comparación idéntica del registro antes y después de reiniciar. La espera por salud corrigió la carrera de disponibilidad anterior.

Artefactos de esa ejecución: [JUnit](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41634878889/artifacts/13228963903) y [demostración HTTP CU-01](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41634878889/artifacts/13229802631). Contienen datos ficticios y tienen retención de siete días; los resultados y sus límites se conservan en este documento.

Última revisión de idioma: el framework devolvía literales ingleses en errores API estándar. Se normalizaron excepciones al español, sin datos internos incluso con debug activo, conservando cabeceras y validaciones. Se ejecutaron **6 pruebas adicionales aprobadas y 37 aserciones**. La CI final comprobará el conjunto integrado. Las respuestas JSON sintéticas de la demostración se imprimen con una marca identificable, sin tokens, para conservarlas de forma duradera en la documentación.

### Resultado final de implementación

La [CI final 41635991294](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41635991294) concluyó con **ambos trabajos aprobados**, sobre el código `12212273b1708d44154a1c597b8a5b20d42feb2f`, árbol `473b20a8e45b7b78738417baf23b0e9d05124b05`:

- **124 casos: 122 aprobados, 2 omitidos, 435 aserciones**, configuración estándar. Pest informó 5,13 s. Las omisiones son exclusivamente registro público deshabilitado.
- PHPStan nivel 7: `[OK] No errors`. Pint y compilación de recursos aprobados.
- Docker: imagen construida, aplicación sana, doce migraciones aplicadas, creación HTTP 201, duplicado 422, consultas 200 y cámara idéntica antes/después de reiniciar conservando el volumen.
- [Respuestas HTTP conservadas en el repositorio](evidencias/http-cu01.json), extraídas de la ejecución real con datos ficticios; no son respuestas inventadas ni contienen tokens.
- Artefactos originales: [JUnit](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41635991294/artifacts/13230711808) y [demostración HTTP](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/actions/runs/41635991294/artifacts/13230139593), con vencimiento el 10 de noviembre de 2026. La evidencia JSON y este registro permanecen versionados.

El cierre posterior modifica documentación y evidencia, sin cambiar la implementación probada. El PR sigue abierto en borrador; no se declara revisión humana, fusión ni entrega al aula. Se prepara un PDF de avance verificable con las brechas que permanecen; su producción y revisión visual se confirman en el informe de entrega del chat.

## Límites de la evidencia

- No se ejecutó Docker localmente porque no está instalado; su ejecución remota sí fue comprobada en las CI aprobadas y enlazadas.
- La interfaz compila y sus flujos tienen pruebas; falta inspección visual completa en navegador, móvil, teclado y temas. Computer Use no pudo iniciarse por fallo de su controlador, incluso después de reintento y reinicio.
- Las pruebas de passkeys comprueban el callback real de autorización del paquete; falta una demostración con hardware WebAuthn.
- El borrador académico declara autorización para desarrollo individual; falta acreditar la autorización original. No se inventan aportes de una pareja.
- Falta el video o demostración final accesible al evaluador y la revisión docente de permisos/categorías y cambios de alcance.
- Las cifras de pruebas son ejecuciones concretas, no una medida de cobertura ni una garantía de ausencia total de defectos.
