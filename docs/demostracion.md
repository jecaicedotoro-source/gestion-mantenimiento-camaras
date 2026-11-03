# Demostración reproducible del MVP

Este guion demuestra CU-01 de principio a fin y, en un flujo separado, la clasificación de incidencias con revisión humana. Utiliza datos ficticios en una base SQLite exclusiva de demostración. Los resultados esperados de esta guía no deben presentarse como ejecuciones realizadas: registrar únicamente resultados obtenidos en [Evidencias verificadas](evidencias.md).

## Preparación sin modificar la instalación de trabajo

Usar una copia del repositorio con dependencias instaladas y archivos de entorno locales. Conservar el `.env` existente, no imprimir su contenido, y no ejecutar `migrate:fresh`, `db:wipe`, `migrate:reset` ni eliminación de volúmenes sobre una base que contenga información útil. Si se reutiliza una base, respaldarla antes de migrar con `php artisan app:backup-database`.

En PowerShell, desde la copia de demostración, asignar una base nueva con nombre propio de esa sesión. El identificador generado evita reutilizar por accidente un archivo anterior:

```powershell
$demoDatabasePath = Join-Path (Get-Location) ('storage/app/demo-' + [guid]::NewGuid().ToString('N') + '.sqlite')
New-Item -ItemType File -Path $demoDatabasePath | Out-Null
$env:APP_ENV = 'local'
$env:APP_DEBUG = 'false'
$env:APP_LOCALE = 'es'
$env:DB_CONNECTION = 'sqlite'
$env:DB_DATABASE = $demoDatabasePath
$env:OPENAI_ENABLED = 'false'
php artisan config:clear
php artisan migrate --force
php artisan app:create-admin
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

La aplicación necesita un `APP_KEY` local válido. Si no existe `.env`, copiar `.env.example` y generar la clave únicamente en esa copia nueva. `app:create-admin` solicita nombre, correo y contraseña de forma interactiva; no incluye una contraseña predeterminada. Usar identidad ficticia y no grabar el momento de introducir credenciales. Mantener esta terminal abierta para conservar las variables de demostración. Abrir la aplicación en `http://127.0.0.1:8000`.

## Escena 1 Acceso y permisos

1. Mostrar la portada en español y acceder con el administrador de prueba.
2. Abrir el panel y el inventario. Los indicadores deben corresponder a la base de demostración, incluyendo ceros cuando esté vacía.
3. Desde Usuarios, crear un técnico de prueba. Mostrar que el menú y la autorización se adaptan a su rol; el registro de cámaras queda reservado al permiso `create-cameras` vigente.
4. Intentar acceder a la administración con el técnico. Debe rechazarse en el servidor, aunque se escriba la URL directamente.
5. El administrador no puede desactivarse a sí mismo ni dejar el sistema sin administrador activo. Desactivar una segunda cuenta y comprobar que conserva el registro e historial y pierde acceso, incluida una sesión previamente abierta. Reactivarla cuando corresponda.

No grabar correos reales, contraseñas, claves de acceso ni tokens. Las restricciones del último administrador y de sesiones se respaldan además con pruebas automatizadas; no alterar cuentas de producción para demostrarlas.

## Escena 2 CU-01 Registrar cámara

Usar los siguientes datos ficticios y ajustar solo la serie si ya se empleó en esta misma demostración:

| Campo | Valor de demostración |
| --- | --- |
| Código de inventario | DEMO-CAM-001 |
| Número de serie | DEMO-SERIE-001 |
| Marca | Marca de prueba |
| Modelo | Modelo académico |
| Tipo | IP |
| Dirección IP | Omitida |
| Ubicación | Área ficticia de demostración |
| Estado | Activo |
| Observaciones | Registro ficticio para demostrar CU-01 |

1. Abrir Registrar cámara y enviar un formulario incompleto. Mostrar los mensajes de validación en español.
2. Introducir una IP inválida y comprobar su rechazo. Quitarla para conservarla como dato opcional.
3. Completar los campos, guardar y mostrar el aviso de éxito.
4. Consultar el inventario, buscar `DEMO-CAM-001` y mostrar el registro guardado.
5. Registrar una segunda cámara con el mismo código de inventario y otra serie: debe permitirse.
6. Intentar reutilizar `DEMO-SERIE-001`: debe rechazarse. Dos cámaras sin serie deben poder registrarse.
7. Recargar o cerrar y abrir la consulta para demostrar persistencia. No limitar la evidencia a la pantalla de confirmación.

El código de inventario no es una clave única. El número de serie, cuando se informa, sí lo es. El activo fijo único previsto para un incremento futuro no es un campo adicional obligatorio de este caso.

## Escena 3 API y persistencia

Preparar un cliente Postman, Bruno u otra herramienta HTTP y configurar la URL local. Usar un token Sanctum de una cuenta activa, verificada y con el permiso necesario, guardado como secreto del cliente. Su creación y revocación son responsabilidad del operador; no incluir el token en la colección exportada ni en la grabación. Todos los ejemplos deben llevar `Accept: application/json` y las escrituras JSON deben llevar `Content-Type: application/json`.

1. Ejecutar `POST /api/cameras` con datos ficticios equivalentes al formulario. Mostrar código 201, mensaje y el identificador recibido.
2. Ejecutar `GET /api/cameras/{id}` con el identificador devuelto. Mostrar 200 y que los datos persisten.
3. Consultar `GET /api/cameras?search=DEMO-CAM-001` y mostrar la búsqueda y paginación.
4. Enviar una serie duplicada o un campo obligatorio ausente. Mostrar 422 y los errores asociados al campo.
5. Mostrar 401 sin autenticación y 403 con un rol sin el permiso de escritura. Mostrar 404 para un identificador inexistente autorizado.
6. Conservar solicitudes y respuestas sin datos sensibles junto al resultado de la colección. No capturar información de cámaras reales.

Las pruebas HTTP automatizadas complementan esta ejecución. La documentación final de API y colección debe corresponder a las rutas de la versión que se demuestra; enlazarla desde [Evidencias](evidencias.md).

## Escena 4 Incidencia y decisión humana

1. Registrar una incidencia de una cámara ficticia con el texto «La imagen se interrumpe de forma repetida desde la revisión de prueba».
2. Mostrar que la incidencia existe con categoría confirmada vacía y revisión pendiente.
3. Con OpenAI deshabilitado, demostrar que sigue siendo posible elegir una categoría manual y guardar responsable y fecha.
4. En una incidencia nueva, solicitar la vista previa de clasificación. Mostrar el texto minimizado que se propone enviar y la necesidad de confirmarlo.
5. Una prueba automatizada con clasificador simulado debe rotularse expresamente **simulada**. Puede verificar sugerencia, rechazo, corrección y persistencia sin realizar consumo externo.

### Prueba real OpenAI cuando esté configurada

Configurar de manera privada clave y modelo compatibles, habilitar la integración y limpiar la configuración cacheada. La clave permanece en servidor. Usar únicamente una descripción ficticia ya revisada. Autorizar explícitamente el texto que saldrá de la aplicación y pulsar Clasificar con IA una sola vez para la demostración.

Mostrar categoría sugerida y justificación breve. Antes de pulsar Aceptar, comprobar que la categoría definitiva sigue sin confirmarse. Aceptar o corregir; mostrar categoría final, responsable, fecha e historial. Registrar modelo y uso reportado cuando exista, sin inventar confianza, costos o precisión. Si el proveedor falla, mostrar el mensaje controlado y continuar manualmente.

Hasta realizar esa llamada no afirmar «integración real verificada». Las pruebas con dobles verifican el comportamiento del código, no la disponibilidad de la cuenta OpenAI.

## Escena 5 Pruebas y contenedor

Ejecutar las pruebas aisladas y conservar su salida final:

```powershell
php artisan test --compact
php vendor/bin/pint --test
npm.cmd run build
```

La configuración de pruebas debe apuntar a SQLite en memoria. No cambiar la conexión para que use la base principal. Explicar las pruebas omitidas de registro público, deshabilitado por la administración institucional de cuentas, y cualquier omisión adicional real.

Cuando exista Docker en el equipo de demostración, comprobar primero su disponibilidad y revisar la configuración sin mostrar secretos. En un entorno dedicado:

```powershell
docker compose build
docker compose up -d
docker compose ps
docker compose exec app php artisan migrate:status
docker compose exec app php artisan app:create-admin
```

Abrir la aplicación, registrar un dato ficticio y reiniciar el servicio con `docker compose restart app`. Comprobar que el registro sigue disponible y que la comprobación de salud es satisfactoria. Al terminar, `docker compose down` conserva el volumen. No añadir `--volumes` ni borrar el almacenamiento sin autorización. No presentar Docker como ejecutado si únicamente se revisaron sus archivos.

## Escena 6 Git y sustentación

Mostrar la rama, commits por bloque y PR reales de la versión demostrada. Explicar las observaciones de revisión y mostrar su integración solo cuando suceda. Identificar contribuciones auténticas; si se mantiene modalidad individual, anexar la autorización docente que la respalda.

Explicar en palabras propias dónde están las reglas de negocio, por qué SQLite resulta adecuado para el MVP, qué datos salen hacia OpenAI, cómo se impide una confirmación automática y cómo se conserva la clasificación manual ante errores.

## Registro y PDF de entrega

Publicar video o una evidencia de demostración accesible al evaluador y verificar su enlace con los permisos previstos. El PDF único del taller debe incluir portada, modalidad/autores, proyecto, MVP, stack y justificación, repositorio, API y persistencia, PR, estado real de IA, contenedorización y enlace de demostración. Usar [Matriz de entregables](matriz-entregables.md) para no ocultar pendientes. Registrar resultados y URLs finales en [Evidencias verificadas](evidencias.md); esta guía por sí sola no constituye una demostración realizada.
