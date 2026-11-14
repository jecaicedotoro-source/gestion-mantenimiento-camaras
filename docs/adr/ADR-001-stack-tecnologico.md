
# ADR-001: Elección de PHP y Laravel

**Fecha:** 2026-11-14
**Estado:** Aceptada

## Contexto

El Sistema Web de Inventario y Mantenimiento de Cámaras de Seguridad de la Universidad Santiago de Cali requiere una arquitectura que permita implementar autenticación, autorización, administración de inventario, gestión de incidencias, API REST e integración con inteligencia artificial.

El proyecto debe desarrollarse y consolidarse en un plazo académico limitado, priorizando la mantenibilidad, seguridad y facilidad de implementación.

## Decisión

Se seleccionó PHP 8.5 y Laravel 13 como tecnologías principales para el backend.

Para la presentación se adoptaron Blade, Livewire 4, Tailwind CSS 4 y Flux UI.

Se decidió implementar una arquitectura monolítica, organizando controladores, modelos, servicios, políticas de autorización y adaptadores de infraestructura.

## Alternativas consideradas

1. **Python con Django:** ofrece un framework completo, pero habría requerido adaptar la implementación y el entorno de desarrollo a otro ecosistema.

2. **Node.js con Express:** proporciona flexibilidad para construir APIs, pero habría requerido seleccionar e integrar más componentes para las funcionalidades previstas.

## Consecuencias

### Ventajas
- Integración de autenticación y autorización.
- Uso de Eloquent para persistencia.
- Estructura organizada para servicios y reglas de negocio.
- Pruebas automatizadas mediante PHPUnit/Pest.
- Desarrollo de interfaces dinámicas con Livewire.
- Integración de servicios externos mediante HTTP.

### Costos y limitaciones
- Dependencia del ecosistema Laravel.
- Necesidad de mantener compatibles las dependencias.
- El escalamiento independiente de frontend y backend no forma parte del diseño monolítico actual.

## Resultado

Se mantiene PHP y Laravel como plataforma principal del MVP, priorizando la entrega funcional, la seguridad y la mantenibilidad.
