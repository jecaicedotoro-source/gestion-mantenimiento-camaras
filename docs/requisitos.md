
# Requisitos - Sistema Web de Inventario y Mantenimiento de Cámaras de Seguridad

## Descripción

El proyecto consiste en desarrollar un prototipo de aplicación web para la gestión del inventario, las incidencias y el mantenimiento de cámaras de seguridad de la Ciudadela Pampalinda de la Universidad Santiago de Cali (USC).

El sistema contempla autenticación de usuarios, autorización basada en roles, registro y consulta de cámaras, gestión de incidencias y clasificación asistida mediante inteligencia artificial con OpenAI API y revisión humana.

La aplicación utiliza PHP 8.5, Laravel 13, Blade, Livewire 4, Tailwind CSS 4, Flux UI, SQLite, PHPUnit/Pest y Git/GitHub, bajo una arquitectura monolítica modular organizada por capas.

El alcance de esta especificación se limita a las funcionalidades existentes o sujetas a verificación, considerando un plazo máximo de dos días para consolidar y presentar el MVP.

---

## Historias de usuario

### HU-01: Autenticación de usuarios

**Historia de usuario**

Como usuario registrado, quiero iniciar sesión en el sistema, para acceder de forma segura a las funcionalidades autorizadas.

**Criterios de aceptación**

**CA-01.1: Inicio de sesión exitoso**

- **Dado** que el usuario tiene una cuenta activa y credenciales válidas.
- **Cuando** introduce sus credenciales e inicia sesión.
- **Entonces** el sistema autentica al usuario y permite el acceso.

**CA-01.2: Credenciales incorrectas**

- **Dado** que el usuario introduce credenciales incorrectas.
- **Cuando** intenta iniciar sesión.
- **Entonces** el sistema rechaza el acceso y muestra un mensaje de error sin revelar información sensible.

**Prioridad:** Alta.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante pruebas de autenticación.

---

### HU-02: Control de acceso basado en roles

**Historia de usuario**

Como administrador, quiero que las operaciones del sistema estén protegidas mediante permisos asociados a roles, para evitar accesos no autorizados.

**Criterios de aceptación**

**CA-02.1: Acceso autorizado**

- **Dado** que un usuario autenticado tiene permisos para realizar una operación.
- **Cuando** intenta ejecutar dicha operación.
- **Entonces** el sistema permite su ejecución.

**CA-02.2: Acceso no autorizado**

- **Dado** que un usuario no dispone de los permisos requeridos.
- **Cuando** intenta acceder a una operación protegida.
- **Entonces** el sistema rechaza la solicitud sin modificar la información almacenada.

**Prioridad:** Alta.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante pruebas de autorización y revisión de roles.

---

### HU-03: Registro de cámaras de seguridad

**Historia de usuario**

Como usuario autorizado, quiero registrar cámaras de seguridad en el inventario, para mantener identificados los dispositivos de la institución.

**Criterios de aceptación**

**CA-03.1: Registro correcto**

- **Dado** que se proporcionan datos válidos de una cámara.
- **Cuando** el usuario registra el dispositivo.
- **Entonces** el sistema almacena correctamente la información en SQLite y permite recuperarla.

**CA-03.2: Código de inventario repetido**

- **Dado** que existe una cámara con un código de inventario determinado.
- **Cuando** se registra otra cámara con el mismo código y los demás datos son válidos.
- **Entonces** el sistema permite el registro sin generar conflictos por duplicidad del código.

**CA-03.3: Número de serie duplicado**

- **Dado** que existe una cámara con un número de serie registrado.
- **Cuando** se intenta registrar otra cámara con ese mismo número de serie.
- **Entonces** el sistema rechaza el registro y mantiene la integridad de la información.

**Prioridad:** Alta.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante pruebas de registro, validaciones y persistencia.

---

### HU-04: Consulta del inventario de cámaras

**Historia de usuario**

Como usuario autorizado, quiero consultar las cámaras registradas, para conocer la información disponible en el inventario institucional.

**Criterios de aceptación**

**CA-04.1: Consulta exitosa**

- **Dado** que existen cámaras registradas en la base de datos.
- **Cuando** el usuario realiza una consulta autorizada.
- **Entonces** el sistema devuelve la información correspondiente a los registros existentes.

**CA-04.2: Cámara inexistente**

- **Dado** que se solicita información de una cámara con un identificador inexistente.
- **Cuando** el sistema procesa la consulta.
- **Entonces** devuelve una respuesta controlada indicando que el recurso no fue encontrado.

**Prioridad:** Alta.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante consultas a la aplicación o API REST.

---

### HU-05: Registro de incidencias

**Historia de usuario**

Como usuario autorizado, quiero registrar incidencias relacionadas con las cámaras de seguridad, para documentar las anomalías detectadas.

**Criterios de aceptación**

**CA-05.1: Registro válido**

- **Dado** que se proporciona información válida para una incidencia.
- **Cuando** el usuario envía la solicitud de registro mediante la API REST.
- **Entonces** el sistema almacena la incidencia y permite identificar el registro creado.

**CA-05.2: Datos inválidos**

- **Dado** que la información enviada no cumple las reglas de validación.
- **Cuando** el sistema procesa la solicitud.
- **Entonces** devuelve los errores correspondientes y no almacena información inválida.

**Prioridad:** Alta.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante pruebas de API y persistencia de incidencias.

---

### HU-06: Consulta de incidencias

**Historia de usuario**

Como usuario autorizado, quiero consultar la información de una incidencia registrada, para conocer su descripción y clasificación disponible.

**Criterios de aceptación**

**CA-06.1: Consulta válida**

- **Dado** que existe una incidencia registrada.
- **Cuando** el usuario autorizado consulta su identificador mediante la API REST.
- **Entonces** el sistema devuelve la información correspondiente a la incidencia.

**CA-06.2: Incidencia inexistente**

- **Dado** que se consulta un identificador que no corresponde a ninguna incidencia.
- **Cuando** el sistema procesa la solicitud.
- **Entonces** devuelve una respuesta controlada de recurso no encontrado.

**Prioridad:** Media.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante pruebas del endpoint de consulta de incidencias.

---

### HU-07: Clasificación asistida por inteligencia artificial

**Historia de usuario**

Como usuario autorizado, quiero solicitar una sugerencia de clasificación de una incidencia mediante inteligencia artificial, para facilitar su evaluación sin reemplazar la decisión humana.

**Criterios de aceptación**

**CA-07.1: Clasificación disponible**

- **Dado** que existe información válida de la incidencia y se ha otorgado el consentimiento requerido.
- **Cuando** se solicita una sugerencia y OpenAI API responde correctamente.
- **Entonces** el sistema presenta una categoría sugerida pendiente de confirmación humana.

**CA-07.2: Ausencia de consentimiento**

- **Dado** que no se ha otorgado el consentimiento requerido.
- **Cuando** se intenta solicitar una clasificación mediante IA.
- **Entonces** el sistema impide el envío de información a OpenAI API.

**CA-07.3: Servicio de IA no disponible**

- **Dado** que OpenAI API no responde correctamente o se produce un error de comunicación.
- **Cuando** el sistema intenta obtener una clasificación.
- **Entonces** devuelve un error controlado y no modifica automáticamente la categoría de la incidencia.

**Prioridad:** Alta.

**Estado:** Pendiente.

**Evidencia:** Por verificar mediante pruebas automatizadas y, cuando esté disponible, una solicitud real a OpenAI API.

---

### HU-08: Validación humana de la clasificación

**Historia de usuario**

Como usuario autorizado, quiero revisar y confirmar la categoría de una incidencia, para garantizar que la clasificación final esté bajo supervisión humana.

**Criterios de aceptación**

**CA-08.1: Confirmación válida**

- **Dado** que existe una incidencia y se ha seleccionado una categoría permitida.
- **Cuando** un usuario autorizado confirma la clasificación.
- **Entonces** el sistema almacena la categoría aceptada y permite consultarla posteriormente.

**CA-08.2: Categoría inválida**

- **Dado** que se intenta asignar una categoría que no pertenece al conjunto permitido.
- **Cuando** el sistema procesa la solicitud.
- **Entonces** rechaza la modificación y conserva la información registrada previamente.

**Prioridad:** Alta.

**Estado:** Hecha.

**Evidencia:** Por verificar mediante pruebas de actualización de categorías y persistencia.

---

## Requisitos no funcionales

Los siguientes criterios cuantitativos constituyen objetivos de aceptación propuestos para la evaluación académica. No representan resultados ya comprobados.

### RNF-01: Rendimiento

**Requisito**

El sistema deberá responder a las consultas del inventario en un tiempo aceptable para su utilización durante la demostración.

**Condición medible**

El percentil 95 de los tiempos de respuesta no deberá superar los 2 segundos en una prueba de 30 consultas consecutivas, utilizando una base de datos de prueba con 100 cámaras.

**Verificación**

Registrar los tiempos de respuesta y calcular el percentil 95.

**Estado:** Pendiente de verificación.

### RNF-02: Seguridad

**Requisito**

El sistema deberá impedir el acceso de usuarios no autenticados o sin los permisos requeridos a operaciones protegidas.

**Condición medible**

El 100 % de los casos de acceso no autorizado incluidos en la matriz de pruebas deberá ser rechazado sin modificar los datos protegidos.

**Verificación**

Ejecutar pruebas de autenticación y autorización sobre los endpoints y operaciones incluidas en el alcance del MVP.

**Estado:** Pendiente de verificación.

### RNF-03: Disponibilidad local

**Requisito**

El sistema deberá permanecer operativo durante la demostración del prototipo mientras el entorno local de ejecución esté activo.

**Condición medible**

El sistema deberá responder correctamente a 10 solicitudes consecutivas realizadas a una ruta existente, sin errores HTTP 5xx atribuibles a la aplicación.

**Verificación**

Ejecutar las solicitudes, registrar los códigos HTTP y comprobar la ausencia de errores internos.

**Estado:** Pendiente de verificación.

### RNF-04: Usabilidad

**Requisito**

Las interfaces implementadas deberán permitir realizar las operaciones básicas mediante controles identificables y mensajes comprensibles.

**Condición medible**

Se deberán completar dos tareas existentes de la interfaz sin errores bloqueantes: inicio de sesión y consulta del inventario, siempre que ambas estén disponibles.

**Verificación**

Ejecutar las dos operaciones y documentar su resultado mediante una lista de comprobación y evidencias visuales.

**Estado:** Pendiente de verificación.

---

## Cambios frente a la IA

Durante la revisión de los requisitos inicialmente generados con asistencia de inteligencia artificial, se identificaron dos condiciones que no se ajustaban adecuadamente al alcance del MVP.

### Cambio 1: Corrección del requisito de disponibilidad

**Propuesta inicial**

Exigir una disponibilidad mínima del 99 % durante una sesión de 60 minutos con 120 solicitudes.

**Corrección realizada**

Se sustituyó esta condición por una verificación de funcionamiento local mediante 10 solicitudes consecutivas sin errores internos atribuibles a la aplicación.

**Justificación**

El proyecto corresponde a un prototipo académico ejecutado en un entorno de desarrollo local. No se ha establecido un despliegue productivo ni una infraestructura de alta disponibilidad.

Por esta razón, exigir métricas propias de un servicio en producción no resulta representativo del alcance actual del sistema.

La corrección permite evaluar su disponibilidad durante la demostración sin ampliar la infraestructura del proyecto.

### Cambio 2: Corrección del requisito de usabilidad

**Propuesta inicial**

Realizar una evaluación con dos usuarios que completaran dos tareas sin asistencia y dentro de un tiempo máximo de cuatro minutos.

**Corrección realizada**

Se reemplazó por una comprobación funcional de dos operaciones disponibles de la interfaz, verificando que puedan completarse sin errores bloqueantes.

**Justificación**

El MVP dispone de un plazo limitado de consolidación y presentación.

La realización de una evaluación formal con participantes externos requiere organización y recursos que no son prioritarios para demostrar el funcionamiento actual del prototipo.

La corrección permite verificar aspectos básicos de usabilidad sin comprometer la entrega de las funcionalidades principales.

### Resultado de los cambios

Se mantienen exactamente cuatro requisitos no funcionales relacionados con rendimiento, seguridad, disponibilidad y usabilidad.

Las modificaciones permiten establecer condiciones de aceptación más realistas y acordes con el entorno técnico y académico del proyecto.

---

## Aspectos pendientes de aclaración

Las siguientes cuestiones deberán contrastarse con el código fuente, las rutas y las pruebas existentes:

1. **HU-01:** Confirmar los estados de cuenta y el comportamiento real de las sesiones.
2. **HU-02:** Identificar los roles y permisos efectivos de cada operación.
3. **HU-03:** Confirmar los campos obligatorios y el tratamiento de números de serie nulos o vacíos.
4. **HU-04:** Confirmar las operaciones y campos de consulta del inventario.
5. **HU-05:** Verificar las validaciones y la persistencia de las incidencias.
6. **HU-06:** Comprobar el endpoint y la información disponible en las consultas.
7. **HU-07:** Confirmar el funcionamiento real de OpenAI API y sus mecanismos de error.
8. **HU-08:** Verificar los permisos y las reglas para confirmar una categoría.

No se incorpora una historia adicional de ejecución o planificación de mantenimiento porque no se dispone de evidencia suficiente sobre la implementación completa de dicho flujo.

---


## Estado de implementación

Se ejecutó la suite de pruebas automatizadas del proyecto mediante el comando `php artisan test`.

### Resultados obtenidos

- Pruebas aprobadas: 129.
- Pruebas omitidas: 2.
- Pruebas fallidas: 0.
- Aserciones ejecutadas: 601.
- Tiempo de ejecución: 13,11 segundos.

Las dos pruebas omitidas corresponden al registro público de usuarios, funcionalidad deshabilitada por decisión de diseño del MVP.

### Estado de las historias de usuario

| Historia | Estado | Evidencia |
|---|---|---|
| HU-01 | Hecha | Pruebas de autenticación y control de sesiones aprobadas. |
| HU-02 | Hecha | Pruebas de autorización y permisos por roles aprobadas. |
| HU-03 | Hecha | Pruebas de registro, validaciones y persistencia aprobadas. |
| HU-04 | Hecha | Pruebas de consulta del inventario mediante API y Livewire aprobadas. |
| HU-05 | Hecha | Pruebas de registro y validación de incidencias aprobadas. |
| HU-06 | Hecha | Pruebas de consulta de incidencias mediante API aprobadas. |
| HU-07 | Pendiente | Pruebas automatizadas del adaptador OpenAI aprobadas; falta verificar una clasificación real exitosa con el proveedor. |
| HU-08 | Hecha | Pruebas de confirmación y corrección humana de categorías aprobadas. |

### Observaciones de calidad

La ejecución evidencia un funcionamiento satisfactorio de los componentes evaluados mediante pruebas automatizadas.

Se comprobaron las reglas de negocio relacionadas con el inventario, la protección de operaciones mediante roles y la persistencia de información.

También se verificaron mecanismos de tratamiento de errores de la API y de conservación de las decisiones humanas en la clasificación de incidencias.

La clasificación asistida mediante inteligencia artificial dispone de pruebas automatizadas satisfactorias. Sin embargo, estas no acreditan por sí solas una conexión real exitosa con OpenAI API.

Los requisitos no funcionales relacionados con rendimiento, disponibilidad y usabilidad conservan el estado Pendiente hasta ejecutar sus verificaciones específicas.

---

## Criterio de cierre del MVP

El prototipo se considerará preparado para su presentación cuando las funcionalidades declaradas como terminadas dispongan de evidencias verificables, se hayan ejecutado las pruebas pertinentes y las limitaciones identificadas estén documentadas.

Se priorizará la consolidación de autenticación, autorización, inventario, incidencias, persistencia de datos y clasificación asistida por IA.

No se incorporarán nuevas funcionalidades que comprometan el plazo de presentación.

La integración de inteligencia artificial se presentará según el estado realmente comprobado, diferenciando las pruebas automatizadas simuladas de las solicitudes reales al proveedor.
