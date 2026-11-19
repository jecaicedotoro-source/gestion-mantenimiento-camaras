# Evidencia S3 - Pruebas e integración continua

Proyecto: Sistema Web de Inventario y Mantenimiento
de Cámaras de Seguridad - USC.

Fecha: 17 de noviembre de 2026.

## 1. Tecnologías

- PHP 8.5 y Laravel 13.
- Pest 5 sobre PHPUnit.
- SQLite en memoria para pruebas.
- GitHub Actions para integración continua.

## 2. Pruebas unitarias

Se implementaron nueve pruebas sobre tres funciones reales:

| Función | Pruebas | Resultado |
|---|---|---|
| User::initials() | U01-U03 | 3 aprobadas |
| CameraValidationRules::rules() | U04-U06 | 3 aprobadas |
| IncidentTextMinimizer::minimize() | U07-U09 | 3 aprobadas |

Resultado: 9 pruebas aprobadas y 19 aserciones.

## 3. Pruebas de API

Se reutilizan pruebas automatizadas del proyecto.

| Endpoint | Éxito | Escenarios de error |
|---|---|---|
| POST /api/cameras | 201 | 422 |
| POST /api/v1/mantenimientos | 201 | 401, 403, 422 |
| POST /api/v1/incidencias | 201 | 422 |
| POST /api/v1/incidencias/{id}/sugerencias-ia | 201 | 422, 403, 503 |
| PATCH /api/v1/incidencias/{id}/categoria | 200 | 422, 403 |

Las pruebas comprueban respuestas HTTP, atributos JSON,
validaciones, permisos y persistencia.

## 4. Resultado de la suite completa

- 167 pruebas aprobadas.
- 2 pruebas omitidas.
- 714 aserciones.
- Duración: 42,40 segundos.
- Ejecución aislada con SQLite en memoria.
- Integración externa de IA deshabilitada.

No se midió cobertura de líneas ni de ramas.

## 5. Demostración de detección de errores

Se utilizó una copia temporal de IncidentTextMinimizer.

Defecto introducido:
Se sustituyó el texto esperado "[dato omitido]"
por "[dato expuesto]".

Resultado:
La prueba de la copia defectuosa detectó la diferencia
y produjo el fallo esperado.

Después se ejecutó U08 sobre el código original
y se obtuvo 1 prueba aprobada.

El archivo de producción no fue modificado.
La prueba temporal fue retirada.

## 6. Integración continua

Workflow: .github/workflows/tests.yml

Controles:
- Instalación de dependencias.
- Compilación de recursos.
- Laravel Pint.
- PHPStan.
- Pest.
- Construcción y verificación Docker.

Se incorporó un badge de CI al README.

La ejecución correspondiente al Pull Request de esta
sesión se verificará después de su publicación.

## 7. Conclusión

El proyecto cumple el mínimo de ocho pruebas exigido
por la guía y dispone de pruebas unitarias, pruebas
de API, aislamiento de datos y verificación automatizada.

La aprobación final de esta entrega queda condicionada
a comprobar el pipeline de GitHub Actions en verde.