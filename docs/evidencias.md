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

**Suite final local:** 118 casos, **116 aprobados, 2 omitidos, 398 aserciones**, exit 0; duración informada por Pest 9,425 s. Se generó JUnit. Las omisiones corresponden a registro público deshabilitado mediante Fortify; no son pruebas fallidas. Comando: PHP 8.5 directo, `vendor/bin/pest --configuration phpunit.windows.xml --no-coverage --log-junit ../junit-final.xml`, con caché de vistas aislada. Pint global terminó `passed`.

Una ejecución previa detectó dos aserciones heredadas de seguridad que esperaban texto inglés; se adaptaron para comprobar las etiquetas traducidas. No se cambió la funcionalidad ni se eliminaron las pruebas.

En el entorno restringido de Windows, PHP `is_readable()` devuelve falso para archivos que sí puede leer. PHPUnit se detenía antes de ejecutar. Se usó una configuración temporal equivalente que omite el bootstrap redundante ya cargado por Pest, con SQLite en memoria; no se modificaron dependencias, ACL ni la configuración estándar de CI. PHPStan local se detiene al comprobar la legibilidad del archivo de configuración; su aprobación requiere ejecución real en CI.

## Demostración HTTP y persistencia

Las pruebas automatizadas ejecutan solicitudes HTTP dentro del kernel Laravel y comprueban registros reales en SQLite aislado, autorización y códigos 201/200/401/403/404/422. La tentativa adicional de peticiones por socket local fue bloqueada por el entorno; no se presenta como una demostración HTTP externa aprobada. El token temporal de esa tentativa se revocó y no se publicó.

La CI contiene una demostración reproducible sobre Docker con datos ficticios: POST de cámara (201), serie duplicada (422), consulta (200), reinicio del contenedor y comparación del mismo registro persistido. El token efímero se captura sin imprimirlo y no se incluye en artefactos; únicamente se conservan respuestas JSON sintéticas. Su ejecución y los artefactos se enlazarán con el resultado real.

## IA: diferencia entre código y conectividad

El adaptador OpenAI es HTTP real, con endpoint fijo, configuración segura, salida estructurada y validación. Las pruebas utilizan sustitutos explícitos y `Http::fake`; no verifican conectividad con una cuenta del proveedor. No se efectuó una llamada real ni se conocen costos reales de esta entrega. Falta configurar clave/modelo y realizar una solicitud controlada con texto ficticio revisado. La clasificación manual y la confirmación humana sí están cubiertas por pruebas.

## GitHub y CI

Publicación de la rama, commits remotos, PR y resultados de CI: **pendientes de registro**. La revisión humana y la fusión se mantienen pendientes hasta efectuarse; los subagentes no se consideran revisores académicos ni un segundo integrante.

## Límites de la evidencia

- No se ejecutó Docker localmente porque no está instalado; la ejecución remota debe comprobarse independientemente.
- La interfaz compila y sus flujos tienen pruebas; falta inspección visual completa en navegador, móvil, teclado y temas. Computer Use no pudo iniciarse por fallo de su controlador, incluso después de reintento y reinicio.
- Las pruebas de passkeys comprueban el callback real de autorización del paquete; falta una demostración con hardware WebAuthn.
- El borrador académico declara autorización para desarrollo individual; falta acreditar la autorización original. No se inventan aportes de una pareja.
- Falta el video o demostración final accesible al evaluador y la revisión docente de permisos/categorías y cambios de alcance.
- Las cifras de pruebas son ejecuciones concretas, no una medida de cobertura ni una garantía de ausencia total de defectos.
