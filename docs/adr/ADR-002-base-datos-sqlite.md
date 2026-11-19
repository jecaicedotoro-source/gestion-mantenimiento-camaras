
# ADR-002: Elección de SQLite

**Fecha:** 2026-11-14
**Estado:** Aceptada

## Contexto

El MVP requiere almacenar usuarios, roles, cámaras, incidencias y registros relacionados con su gestión.

Se necesita una solución de persistencia sencilla, compatible con Laravel, que permita implementar migraciones, validar reglas de negocio y ejecutar pruebas automatizadas.

La prioridad es disponer de un prototipo funcional con una configuración local de baja complejidad.

## Decisión

Se seleccionó SQLite como motor de base de datos principal del MVP.

Laravel utiliza Eloquent como mecanismo de acceso a los datos.

La configuración activa fue verificada mediante el siguiente comando:

`php artisan tinker --execute="echo config('database.default');"`

Resultado: `sqlite`.

## Alternativas consideradas

1. **MySQL:** motor ampliamente utilizado en aplicaciones web. Requiere administrar un servicio de base de datos independiente.

2. **PostgreSQL:** ofrece capacidades avanzadas para aplicaciones de mayor complejidad y concurrencia, pero implica administrar un servidor adicional.

## Consecuencias

### Ventajas
- Configuración sencilla.
- Base de datos almacenada en un archivo local.
- Integración con Eloquent y migraciones de Laravel.
- Adecuada para desarrollo y pruebas del MVP.
- Menor complejidad de infraestructura inicial.

### Costos y limitaciones
- Menor capacidad de concurrencia de escritura que una base de datos cliente-servidor.
- Requiere mecanismos de respaldo del archivo.
- Un despliegue con múltiples instancias necesitaría reconsiderar la estrategia de persistencia.
- Una migración futura a otro motor exigiría pruebas de compatibilidad.

## Resultado

Se mantiene SQLite para el MVP académico.

Se reconsiderará MySQL o PostgreSQL si los requisitos futuros demandan mayor concurrencia, escalabilidad o una infraestructura distribuida.
