# API HTTP del MVP

Base de desarrollo: `http://localhost:8000/api`. Todas las rutas exigen token **Sanctum**, cuenta activa, correo verificado y permisos institucionales. Formato: `Accept: application/json`, `Content-Type: application/json`, `Authorization: Bearer {{token}}`. El límite general es 60 peticiones/minuto; IA añade cuotas inferiores.

La colección [Postman](api.postman_collection.json) usa únicamente variables vacías y datos sintéticos. Importar, configurar `base_url`, guardar `token` como secreto local y no exportarlo. El token tiene los permisos actuales del usuario: nunca eleva un técnico a administrador.

## Preparar un token local

Crear primero una cuenta con `php artisan app:create-admin`. Desde una consola autorizada ejecutar `php artisan tinker`, buscar esa cuenta y crear un token con vencimiento corto:

```php
$user = App\Models\User::where('email', 'tu-correo-configurado-localmente')->firstOrFail();
$user->createToken('evaluacion-local', ['*'], now()->addHour())->plainTextToken;
```

El resultado es un secreto: introducirlo solo en el almacén local de Postman/Bruno, no en capturas, README, chats ni commits. La desactivación y los cambios de rol, correo o contraseña revocan tokens existentes. Borrar el token al terminar la demostración desde la misma consola autorizada.

## CU-01: cámaras

| Método y ruta | Permiso | Resultado |
| --- | --- | --- |
| `GET /cameras?search=DEMO&page=1` | Cualquiera de los cuatro roles | 200, paginación de 10 registros y búsqueda por código/serie/ubicación |
| `POST /cameras` | Administrador o coordinador | 201, cámara persistida |
| `GET /cameras/{id}` | Cualquiera de los cuatro roles | 200 o 404 |

El administrador es el actor principal del CU-01. Se conserva el permiso previo del coordinador. Técnico y auxiliar pueden consultar; no registrar cámaras.

### Solicitud de registro

```json
{
  "codigo_inventario": "DEMO-01",
  "numero_serie": "SERIE-DEMO-01",
  "marca": "Demostración",
  "modelo": "MVP",
  "tipo": "IP",
  "direccion_ip": "192.0.2.10",
  "ubicacion": "Laboratorio de demostración",
  "estado": "activo",
  "observaciones": "Datos sintéticos para evaluación"
}
```

| Campo | Validación |
| --- | --- |
| `codigo_inventario` | Obligatorio, texto, máximo 50; **puede repetirse** |
| `numero_serie` | Opcional, máximo 100; único si se proporciona. Vacío se convierte en `null` |
| `marca`, `modelo` | Obligatorios, texto, máximo 100 |
| `tipo` | Obligatorio, texto, máximo 50 |
| `direccion_ip` | Opcional, IPv4 o IPv6, máximo 45 |
| `ubicacion` | Obligatorio, texto, máximo 150 |
| `estado` | `activo`, `inactivo`, `en_mantenimiento`, `fuera_de_servicio` |
| `observaciones` | Opcional, texto, máximo 2000 |

No se solicita número de activo fijo. Se recortan espacios y se descartan campos no validados. La base también impone la unicidad de la serie frente a concurrencia.

Respuesta 201 (extracto del contrato, los identificadores y fechas los asigna el servidor):

```json
{"message":"Cámara registrada correctamente.","data":{"id":1,"codigo_inventario":"DEMO-01","numero_serie":"SERIE-DEMO-01","marca":"Demostración","modelo":"MVP","tipo":"IP","direccion_ip":"192.0.2.10","ubicacion":"Laboratorio de demostración","estado":"activo","observaciones":"Datos sintéticos para evaluación"}}
```

La respuesta real incluye `created_at` y `updated_at`. Consulta individual: `{"data":{...}}`. Listado: `{"current_page":1,"data":[...],"per_page":10,"total":1,...}`.

## Incidencias y clasificación supervisada

Administrador, coordinador y técnico registran y revisan incidencias; auxiliar consulta. Este catálogo de permisos y las seis categorías iniciales son decisiones provisionales del MVP, pendientes de validación institucional.

| Método y ruta bajo `/api` | Entrada | Resultado |
| --- | --- | --- |
| `POST /v1/incidencias` | `camera_id`, `description` (10-2000 caracteres) | 201; categoría final nula |
| `GET /v1/incidencias/{id}` | Identificador | 200; estado y decisión humana |
| `GET /v1/incidencias/{id}/sugerencias-ia/vista-previa` | Identificador | 200; texto minimizado y `approved_input_hash` |
| `POST /v1/incidencias/{id}/sugerencias-ia` | Hash vigente de 64 caracteres, `consent:true` | 201; propuesta pendiente de revisión |
| `PATCH /v1/incidencias/{id}/categoria` | `category`, `suggestion_id` opcional | 200; decisión humana manual, aceptada o corregida |

Categorías: `conectividad`, `alimentacion`, `imagen`, `hardware`, `configuracion`, `otra`. La vista previa debe revisarse antes de autorizar el envío; la minimización automática no garantiza detectar todos los datos sensibles.

Ejemplo de confirmación manual: `{"category":"imagen"}`. Para aceptar o corregir una propuesta: añadir su `suggestion_id`; debe ser la propuesta vigente de esa misma incidencia. La IA nunca confirma ni modifica automáticamente la categoría final.

Sugerencia 201: `{"message":"Sugerencia pendiente de confirmación humana.","data":{"id":1,"incident_id":1,"suggested_category":"imagen","explanation":"Síntomas compatibles con pérdida de imagen.","status":"sugerida"}}`. Este es un ejemplo de contrato, no una respuesta de OpenAI real obtenida en esta entrega.

## Errores

| HTTP | Situación |
| --- | --- |
| 401 | Token ausente, inválido o revocado |
| 403 | Cuenta inactiva, correo sin verificar o permiso insuficiente |
| 404 | Cámara o incidencia inexistente |
| 422 | Validación, serie duplicada, consentimiento/hash inválido, propuesta ajena/antigua, decisión ya confirmada o cuota local de IA |
| 429 | Límite general de solicitudes HTTP |
| 503 | OpenAI deshabilitado, mal configurado, timeout, rechazo, fallo HTTP o respuesta inválida; `manual_available:true` |

La API devuelve `message` y, para validaciones, `errors` por campo. Un token revocado normalmente da 401; una sesión/token ya resuelto durante una petición concurrente puede ser rechazado por el control de cuenta con 403. No se muestran credenciales, respuestas internas del proveedor ni excepciones de infraestructura al usuario.

## Verificación

Las pruebas de `tests/Feature/Api`, `CameraCompletenessTest` e `Incidents/IncidentApiTest` ejercitan HTTP dentro de Laravel con SQLite real en memoria. Los tests de IA emplean `Http::fake` y bloquean solicitudes inesperadas. La colección queda preparada para la demostración externa con Postman; los resultados realmente ejecutados están en [evidencias](evidencias.md).
