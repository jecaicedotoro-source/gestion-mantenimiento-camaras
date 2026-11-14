# Requisitos - Sistema Web de Inventario y Mantenimiento de Cámaras de Seguridad

## 1. Descripción del proyecto

El proyecto consiste en desarrollar una aplicación web para la administración del inventario, las incidencias y los mantenimientos de cámaras de seguridad de la Ciudadela Pampalinda de la Universidad Santiago de Cali (USC).

El sistema busca centralizar la información de los dispositivos, facilitar la identificación y consulta de cámaras de seguridad, registrar incidencias técnicas y mantener la trazabilidad de las intervenciones realizadas sobre los equipos.

El prototipo dispone de autenticación de usuarios, autorización basada en roles, registro y consulta de cámaras, servicios API REST para la gestión de información y clasificación asistida de incidencias mediante inteligencia artificial, con revisión y confirmación humana.

La aplicación utiliza las siguientes tecnologías:

- **Backend:** PHP 8.5 y Laravel 13.
- **Frontend:** Blade, Livewire 4, Tailwind CSS 4 y Flux UI.
- **Base de datos:** SQLite.
- **Pruebas automatizadas:** PHPUnit/Pest.
- **Control de versiones:** Git y GitHub.
- **Integración de inteligencia artificial:** OpenAI API.
- **Arquitectura:** Monolítica modular organizada por capas.

La especificación inicial comprendía ocho historias de usuario relacionadas con autenticación, autorización, inventario, incidencias e inteligencia artificial.

Posteriormente, se amplió el backlog con cuatro historias correspondientes al módulo de mantenimientos, agrupadas en la épica EP-03.

Esta ampliación se encuentra en fase de análisis y planificación. No debe confundirse con funcionalidades ya implementadas.

---

## 2. Objetivos del sistema

### Objetivo general

Desarrollar un sistema web que permita administrar de manera centralizada el inventario, las incidencias y los registros de mantenimiento de las cámaras de seguridad de la Universidad Santiago de Cali.

### Objetivos específicos

1. Gestionar el acceso al sistema mediante autenticación y autorización basada en roles.
2. Registrar y consultar las cámaras de seguridad de la institución.
3. Aplicar reglas de negocio que garanticen la integridad de los datos del inventario.
4. Registrar y consultar incidencias asociadas a las cámaras.
5. Incorporar clasificación asistida de incidencias mediante inteligencia artificial con supervisión humana.
6. Incorporar, en una nueva iteración, el registro y la consulta de mantenimientos preventivos y correctivos.
7. Mantener trazabilidad entre las cámaras, las incidencias y las intervenciones técnicas.
8. Verificar el funcionamiento del sistema mediante pruebas automatizadas y evidencias técnicas.

---

## 3. Historias de usuario

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

**Prioridad:** Alta (P0).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/Auth/AuthenticationTest.php`
- Pruebas automatizadas de autenticación, contraseñas incorrectas y cierre de sesión aprobadas.

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

**Reglas de negocio verificadas**

- El administrador puede consultar y registrar cámaras.
- El coordinador puede consultar y registrar cámaras.
- El técnico puede consultar cámaras, pero no registrarlas.
- El auxiliar puede consultar cámaras, pero no registrarlas.
- Los usuarios sin rol institucional no pueden acceder al inventario.

**Prioridad:** Alta (P0).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/CameraAuthorizationTest.php`
- `tests/Feature/CameraWebAccessTest.php`
- `tests/Feature/Api/CameraApiTest.php`

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

**Reglas de negocio verificadas**

- Los códigos de inventario pueden repetirse.
- Los números de serie proporcionados deben ser únicos.
- Se permiten números de serie nulos.
- Los campos obligatorios no pueden estar vacíos.
- Las direcciones IP deben cumplir las validaciones establecidas.
- El registro requiere autorización.

**Prioridad:** Alta (P0).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/CameraRegistrationTest.php`
- `tests/Feature/CameraCompletenessTest.php`
- `tests/Feature/Api/CameraExecutionPlanTest.php`
- `tests/Feature/Api/CameraApiTest.php`

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

**Reglas de negocio verificadas**

- La consulta requiere autenticación y autorización.
- Los roles institucionales autorizados pueden consultar el inventario.
- Los usuarios sin rol no pueden acceder a la información protegida.
- Los datos consultados corresponden a los registros persistidos.

**Prioridad:** Alta (P0).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/CameraAuthorizationTest.php`
- `tests/Feature/CameraCompletenessTest.php`
- `tests/Feature/CameraWebAccessTest.php`
- `tests/Feature/Api/CameraApiTest.php`

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

**Reglas de negocio verificadas**

- El registro requiere autenticación y autorización.
- La incidencia debe estar relacionada con una cámara existente.
- Los datos deben cumplir las reglas de validación.
- La información debe persistir en SQLite.
- La clasificación mediante IA no reemplaza la decisión humana.

**Prioridad:** Alta (P0).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/Incidents/IncidentApiTest.php`
- `tests/Feature/Incidents/IncidentWorkflowTest.php`

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

**Reglas de negocio verificadas**

- La consulta requiere autenticación y autorización.
- La información corresponde a incidencias persistidas.
- Las categorías sugeridas por IA y las confirmadas por usuarios deben mantenerse diferenciadas.
- Los errores de la API no deben revelar detalles internos.

**Prioridad:** Media (P1).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/Incidents/IncidentApiTest.php`
- `tests/Feature/Incidents/IncidentWorkflowTest.php`
- `tests/Feature/Api/ApiErrorResponsesTest.php`

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

**Reglas de negocio verificadas mediante pruebas automatizadas**

- La solicitud requiere autenticación y autorización.
- El sistema exige consentimiento para utilizar el proveedor.
- La IA propone una clasificación, pero no toma la decisión final.
- Las respuestas inválidas deben rechazarse.
- Los errores del proveedor deben tratarse de forma controlada.
- Las claves y detalles internos no deben exponerse.

**Prioridad:** Alta (P0).

**Estado:** Pendiente de verificación real.

**Evidencia:**

- `tests/Feature/Incidents/OpenAIClassifierTest.php`
- `tests/Feature/Incidents/IncidentWorkflowTest.php`
- `tests/Feature/Incidents/IncidentApiTest.php`

**Actividades pendientes**

- [x] Implementar el adaptador OpenAI.
- [x] Validar las respuestas del clasificador.
- [x] Implementar el consentimiento.
- [x] Manejar los errores del proveedor.
- [x] Ejecutar las pruebas automatizadas.
- [ ] Verificar una clasificación exitosa mediante una solicitud real a OpenAI API.
- [ ] Registrar evidencia de la respuesta real y su presentación para revisión humana.

**Observación**

Las pruebas automatizadas son satisfactorias, pero no acreditan por sí solas una conexión real exitosa con OpenAI API.

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

**Reglas de negocio verificadas**

- La decisión final corresponde a un usuario autorizado.
- La categoría sugerida y la confirmada deben permanecer diferenciadas.
- Las categorías inválidas deben rechazarse.
- La clasificación manual puede realizarse cuando el proveedor de IA falla.
- Las decisiones confirmadas deben conservar su trazabilidad.

**Prioridad:** Alta (P0).

**Estado:** Hecha.

**Evidencia:**

- `tests/Feature/Incidents/IncidentWorkflowTest.php`
- `tests/Feature/Incidents/IncidentApiTest.php`

---

## 4. Épica EP-03: Gestión de Mantenimientos de Cámaras de Seguridad

**Tipo:** Épica funcional.

**Estado:** Propuesta de implementación.

**Objetivo**

Incorporar funcionalidades que permitan registrar, clasificar y consultar intervenciones de mantenimiento sobre las cámaras de seguridad de la USC, manteniendo la trazabilidad de las actuaciones técnicas y su relación con las incidencias detectadas.

**Alcance inicial**

- HU-09: Registro de mantenimientos.
- HU-10: Consulta del historial de mantenimientos.
- HU-11: Clasificación de mantenimientos preventivos y correctivos.
- HU-12: Asociación de mantenimientos con incidencias.

Las cuatro historias representan una ampliación del backlog y permanecerán pendientes hasta completar su implementación y verificación.

---

### HU-09: Registro de mantenimientos de cámaras

**Historia de usuario**

Como usuario autorizado, quiero registrar las intervenciones de mantenimiento realizadas a las cámaras de seguridad, para conservar información técnica de las actividades ejecutadas.

**Criterios de aceptación**

**CA-09.1: Registro exitoso**

- **Dado** que existe una cámara registrada y el usuario tiene autorización.
- **Cuando** introduce los datos obligatorios válidos de un mantenimiento.
- **Entonces** el sistema guarda la intervención en SQLite y la relaciona con la cámara correspondiente.

**CA-09.2: Cámara inexistente**

- **Dado** que se intenta registrar un mantenimiento para una cámara inexistente.
- **Cuando** el usuario envía el registro.
- **Entonces** el sistema rechaza la operación y no almacena información inconsistente.

**CA-09.3: Datos incompletos**

- **Dado** que faltan campos obligatorios.
- **Cuando** el usuario intenta guardar el mantenimiento.
- **Entonces** el sistema muestra los errores correspondientes y no almacena el registro.

**Datos propuestos para el registro**

- Cámara intervenida.
- Fecha del mantenimiento.
- Tipo de mantenimiento.
- Descripción de la intervención.
- Responsable de la intervención.
- Resultado del mantenimiento.

**Reglas de negocio propuestas**

- Cada mantenimiento debe asociarse a una cámara existente.
- El registro requiere autenticación y autorización.
- Los campos obligatorios deben validarse.
- La información debe persistir en SQLite.
- Los datos del mantenimiento deben permitir identificar la intervención realizada.
- La relación con una incidencia se gestionará mediante HU-12.

**Prioridad:** Alta (P0).

**Estado:** Pendiente.

**Evidencia:** Funcionalidad todavía no implementada ni verificada.

**Ubicación Kanban:** Backlog.

---

### HU-10: Consulta del historial de mantenimientos

**Historia de usuario**

Como usuario autorizado, quiero consultar el historial de mantenimientos de una cámara de seguridad, para conocer las intervenciones efectuadas y facilitar su seguimiento técnico.

**Criterios de aceptación**

**CA-10.1: Consulta exitosa**

- **Dado** que una cámara tiene mantenimientos registrados.
- **Cuando** el usuario consulta su historial.
- **Entonces** el sistema muestra las intervenciones asociadas, incluyendo fecha, tipo, descripción y resultado.

**CA-10.2: Historial vacío**

- **Dado** que una cámara no tiene mantenimientos registrados.
- **Cuando** el usuario consulta su historial.
- **Entonces** el sistema muestra un mensaje indicando que no existen intervenciones registradas.

**CA-10.3: Acceso no autorizado**

- **Dado** que un usuario no cuenta con permisos de consulta.
- **Cuando** intenta acceder al historial de mantenimientos.
- **Entonces** el sistema rechaza la solicitud sin revelar información protegida.

**Reglas de negocio propuestas**

- La consulta requiere autenticación y autorización.
- Los registros deben corresponder a la cámara seleccionada.
- La consulta no debe modificar información almacenada.
- El historial debe permitir identificar las intervenciones realizadas.
- Los mantenimientos deben recuperarse de la base de datos.

**Prioridad:** Alta (P0).

**Estado:** Pendiente.

**Evidencia:** Funcionalidad todavía no implementada ni verificada.

**Ubicación Kanban:** Backlog.

---

### HU-11: Clasificación de mantenimientos preventivos y correctivos

**Historia de usuario**

Como usuario autorizado, quiero identificar cada mantenimiento como preventivo o correctivo, para diferenciar las intervenciones periódicas de aquellas destinadas a corregir fallos.

**Criterios de aceptación**

**CA-11.1: Mantenimiento preventivo**

- **Dado** que el usuario registra un mantenimiento con datos válidos.
- **Cuando** selecciona el tipo preventivo.
- **Entonces** el sistema almacena el mantenimiento identificado como preventivo.

**CA-11.2: Mantenimiento correctivo**

- **Dado** que el usuario registra un mantenimiento con datos válidos.
- **Cuando** selecciona el tipo correctivo.
- **Entonces** el sistema almacena el mantenimiento identificado como correctivo.

**CA-11.3: Tipo inválido**

- **Dado** que se proporciona un tipo de mantenimiento diferente de los permitidos.
- **Cuando** el sistema valida la solicitud.
- **Entonces** rechaza el registro y comunica el error sin almacenar el dato inválido.

**Reglas de negocio propuestas**

- Los tipos de mantenimiento inicialmente permitidos serán preventivo y correctivo.
- Todo mantenimiento deberá tener un tipo definido.
- El tipo seleccionado debe persistir en SQLite.
- La clasificación deberá poder consultarse posteriormente.
- La selección del tipo formará parte del proceso de registro definido en HU-09.

**Prioridad:** Alta (P0).

**Estado:** Pendiente.

**Evidencia:** Funcionalidad todavía no implementada ni verificada.

**Ubicación Kanban:** Backlog.

**Observación técnica**

HU-11 complementa funcionalmente a HU-09. No se requiere una pantalla independiente para clasificar los mantenimientos; la selección del tipo podrá realizarse dentro del formulario de registro.

---

### HU-12: Asociación de mantenimientos con incidencias

**Historia de usuario**

Como usuario autorizado, quiero asociar un mantenimiento con una incidencia registrada, para mantener la trazabilidad entre el fallo detectado y la intervención realizada.

**Criterios de aceptación**

**CA-12.1: Asociación correcta**

- **Dado** que existe una incidencia registrada para una cámara.
- **Cuando** el usuario autorizado registra un mantenimiento relacionado con esa incidencia.
- **Entonces** el sistema almacena la asociación entre el mantenimiento, la incidencia y la cámara.

**CA-12.2: Incidencia inexistente o incompatible**

- **Dado** que se selecciona una incidencia inexistente o perteneciente a otra cámara.
- **Cuando** el usuario intenta guardar la asociación.
- **Entonces** el sistema rechaza la operación y evita relaciones inconsistentes.

**CA-12.3: Consulta de la relación**

- **Dado** que un mantenimiento está asociado a una incidencia.
- **Cuando** el usuario autorizado consulta la intervención.
- **Entonces** el sistema permite identificar la incidencia relacionada.

**Reglas de negocio propuestas**

- La incidencia relacionada debe existir previamente.
- La incidencia asociada debe pertenecer a la misma cámara del mantenimiento.
- La asociación debe persistir en SQLite.
- Los permisos de asociación deben definirse antes de implementar.
- La asociación será opcional, puesto que no todos los mantenimientos estarán relacionados con una incidencia.
- Los mantenimientos preventivos podrán registrarse sin incidencia asociada.

**Prioridad:** Alta (P0).

**Estado:** Pendiente.

**Evidencia:** Funcionalidad todavía no implementada ni verificada.

**Ubicación Kanban:** Backlog.

---

## 5. Alcance funcional del módulo de mantenimientos

La implementación inicial de la épica EP-03 estará limitada a las cuatro historias HU-09, HU-10, HU-11 y HU-12.

### Funcionalidades incluidas

1. Registrar un mantenimiento asociado a una cámara existente.
2. Registrar fecha, descripción, responsable y resultado de la intervención.
3. Identificar el mantenimiento como preventivo o correctivo.
4. Consultar los mantenimientos registrados de una cámara.
5. Asociar opcionalmente un mantenimiento con una incidencia de la misma cámara.
6. Validar los datos obligatorios y las relaciones entre registros.
7. Aplicar autenticación y autorización a las operaciones implementadas.

### Funcionalidades fuera del alcance inicial

- Programación automática de mantenimientos preventivos.
- Generación de notificaciones y recordatorios.
- Gestión de proveedores de mantenimiento.
- Inventario y consumo de repuestos.
- Órdenes de trabajo complejas.
- Asignación automática de técnicos.
- Mantenimiento predictivo mediante inteligencia artificial.
- Generación automática de informes de mantenimiento.

Estas funcionalidades podrán evaluarse en futuras iteraciones, pero no forman parte del compromiso actual de implementación.

---

## 6. Aspectos pendientes de definición del módulo de mantenimientos

Antes de comenzar la programación deberán confirmarse las siguientes decisiones:

1. **Roles autorizados:** definir qué roles pueden registrar y consultar mantenimientos.
2. **Responsable de la intervención:** establecer si el responsable será un usuario registrado o si podrá identificarse mediante otro dato.
3. **Campos obligatorios:** determinar qué información es imprescindible para guardar un mantenimiento.
4. **Resultado de la intervención:** definir si será un texto descriptivo o un conjunto de valores predefinidos.
5. **Relación con incidencias:** confirmar si se permitirá asociar más de un mantenimiento a una incidencia.
6. **Historial de mantenimiento:** definir el orden de presentación y los campos visibles.
7. **Persistencia:** establecer las relaciones y restricciones de integridad correspondientes.
8. **Permisos:** definir qué operaciones estarán disponibles para administrador, coordinador, técnico y auxiliar.

Estas decisiones deberán incorporarse al diseño funcional antes de implementar las historias.

---

## 7. Requisitos no funcionales

Se mantienen cuatro requisitos no funcionales relacionados con rendimiento, seguridad, disponibilidad y usabilidad.

Los valores establecidos corresponden a objetivos de aceptación para el prototipo y no representan resultados medidos ni garantías de operación en producción.

### RNF-01: Rendimiento

**Requisito**

El sistema deberá responder a las consultas del inventario en un tiempo aceptable para su utilización durante la demostración.

**Condición medible**

El percentil 95 de los tiempos de respuesta no deberá superar los 2 segundos en una prueba de 30 consultas consecutivas, utilizando una base de datos de prueba con 100 cámaras.

**Verificación**

Registrar los tiempos de respuesta y calcular el percentil 95.

**Estado:** Pendiente de verificación.

---

### RNF-02: Seguridad

**Requisito**

El sistema deberá impedir el acceso de usuarios no autenticados o sin los permisos requeridos a operaciones protegidas.

**Condición medible**

El 100 % de los casos de acceso no autorizado incluidos en la matriz de pruebas deberá ser rechazado sin modificar los datos protegidos.

**Verificación**

Ejecutar pruebas de autenticación y autorización sobre las operaciones incluidas en el alcance del MVP.

**Estado:** Pendiente de verificación integral del requisito.

**Observación:** Existen pruebas automatizadas satisfactorias de autenticación y autorización.

---

### RNF-03: Disponibilidad local

**Requisito**

El sistema deberá permanecer operativo durante la demostración del prototipo mientras el entorno local de ejecución esté activo.

**Condición medible**

El sistema deberá responder correctamente a 10 solicitudes consecutivas realizadas a una ruta existente, sin errores HTTP 5xx atribuibles a la aplicación.

**Verificación**

Ejecutar las solicitudes, registrar los códigos HTTP y comprobar la ausencia de errores internos.

**Estado:** Pendiente de verificación.

---

### RNF-04: Usabilidad

**Requisito**

Las interfaces implementadas deberán permitir realizar las operaciones básicas mediante controles identificables y mensajes comprensibles.

**Condición medible**

Se deberán completar dos tareas existentes de la interfaz sin errores bloqueantes: inicio de sesión y consulta del inventario, siempre que ambas estén disponibles.

**Verificación**

Ejecutar las dos operaciones y documentar su resultado mediante una lista de comprobación y evidencias visuales.

**Estado:** Pendiente de verificación.

---

## 8. Cambios frente a la IA

Durante la revisión de los requisitos inicialmente generados con asistencia de inteligencia artificial, se identificaron dos condiciones que no se ajustaban adecuadamente al alcance del MVP.

### Cambio 1: Corrección del requisito de disponibilidad

**Propuesta inicial**

Exigir una disponibilidad mínima del 99 % durante una sesión de 60 minutos con 120 solicitudes.

**Corrección realizada**

Se sustituyó esta condición por una verificación de funcionamiento local mediante 10 solicitudes consecutivas sin errores internos atribuibles a la aplicación.

**Justificación**

El proyecto corresponde a un prototipo académico ejecutado en un entorno de desarrollo local.

No se ha establecido un despliegue productivo ni una infraestructura de alta disponibilidad.

Por esta razón, exigir métricas propias de un servicio en producción no resulta representativo del alcance actual del sistema.

La corrección permite evaluar su funcionamiento durante la demostración sin ampliar la infraestructura del proyecto.

---

### Cambio 2: Corrección del requisito de usabilidad

**Propuesta inicial**

Realizar una evaluación con dos usuarios que completaran dos tareas sin asistencia y dentro de un tiempo máximo de cuatro minutos.

**Corrección realizada**

Se reemplazó por una comprobación funcional de dos operaciones disponibles de la interfaz, verificando que puedan completarse sin errores bloqueantes.

**Justificación**

El MVP dispone de un plazo limitado de consolidación y presentación.

La realización de una evaluación formal con participantes externos requiere organización y recursos que no son prioritarios para demostrar el funcionamiento actual del prototipo.

La corrección permite verificar aspectos básicos de usabilidad sin comprometer la entrega de las funcionalidades principales.

---

### Resultado de los cambios

Se mantienen exactamente cuatro requisitos no funcionales relacionados con rendimiento, seguridad, disponibilidad y usabilidad.

Las modificaciones permiten establecer condiciones de aceptación más realistas para el entorno técnico y académico del proyecto.

---

## 9. Estado de implementación

La implementación del sistema se ha verificado parcialmente mediante pruebas automatizadas ejecutadas con el comando:

`php artisan test`

### Resultados de la ejecución documentada

- Pruebas aprobadas: 129.
- Pruebas omitidas: 2.
- Pruebas fallidas: 0.
- Aserciones ejecutadas: 601.
- Tiempo de ejecución: 13,11 segundos.

Las dos pruebas omitidas corresponden a escenarios de registro público de usuarios, funcionalidad deshabilitada por decisión de diseño.

Estos resultados fueron obtenidos antes de incorporar las historias HU-09, HU-10, HU-11 y HU-12 a la documentación.

No representan resultados de pruebas automatizadas del módulo de mantenimientos.

### Estado de las historias de usuario

| Historia | Estado | Evidencia |
|---|---|---|
| HU-01 | Hecha | Pruebas de autenticación aprobadas. |
| HU-02 | Hecha | Pruebas de autorización y roles aprobadas. |
| HU-03 | Hecha | Pruebas de registro, validaciones y persistencia aprobadas. |
| HU-04 | Hecha | Pruebas de consulta mediante API y Livewire aprobadas. |
| HU-05 | Hecha | Pruebas de registro de incidencias aprobadas. |
| HU-06 | Hecha | Pruebas de consulta de incidencias aprobadas. |
| HU-07 | Pendiente | Adaptador y pruebas automatizadas aprobadas; falta verificar una clasificación real exitosa mediante OpenAI API. |
| HU-08 | Hecha | Pruebas de confirmación y corrección humana aprobadas. |
| HU-09 | Pendiente | Registro de mantenimientos todavía no implementado. |
| HU-10 | Pendiente | Consulta del historial de mantenimientos todavía no implementada. |
| HU-11 | Pendiente | Clasificación preventiva y correctiva todavía no implementada. |
| HU-12 | Pendiente | Asociación con incidencias todavía no implementada. |

### Resumen del avance funcional

- **Total de historias de usuario:** 12.
- **Historias completadas:** 7.
- **Historias pendientes:** 5.
- **Historias del módulo de mantenimientos:** 4.
- **Historias de mantenimiento implementadas:** 0.

**Porcentaje de historias completadas:** 58,3 %.

Este porcentaje corresponde a la cantidad de historias marcadas como terminadas respecto al total documentado. No representa una medición del avance global del software ni considera las diferencias de esfuerzo entre historias.

---

## 10. Observaciones de calidad

La ejecución de pruebas automatizadas evidencia un funcionamiento satisfactorio de los componentes evaluados.

Se comprobaron reglas de negocio relacionadas con autenticación, autorización, inventario, incidencias y persistencia.

También se verificaron mecanismos de tratamiento de errores de la API y conservación de decisiones humanas en la clasificación de incidencias.

La clasificación asistida mediante inteligencia artificial dispone de pruebas automatizadas satisfactorias, pero todavía no existe evidencia suficiente de una clasificación real exitosa con OpenAI API.

El módulo de mantenimientos se encuentra en fase de análisis y definición de requisitos.

Las historias HU-09, HU-10, HU-11 y HU-12 todavía no cuentan con implementación ni pruebas automatizadas específicas.

Se deberán establecer casos de prueba positivos y negativos para cada una de estas historias antes de marcarlas como terminadas.

---

## 11. Trazabilidad con GitHub Projects

Las historias de usuario se gestionan mediante Issues vinculados al tablero Kanban del proyecto.

El flujo de trabajo comprende cinco estados:

1. Backlog.
2. Ready.
3. In Progress.
4. In Review.
5. Done.

### Distribución objetivo del tablero

**Done**

- HU-01: Autenticación de usuarios.
- HU-02: Control de acceso basado en roles.
- HU-03: Registro de cámaras de seguridad.
- HU-04: Consulta del inventario de cámaras.
- HU-05: Registro de incidencias.
- HU-06: Consulta de incidencias.
- HU-08: Validación humana de la clasificación.

**In Review**

- HU-07: Clasificación asistida por inteligencia artificial.

**Backlog**

- HU-09: Registro de mantenimientos de cámaras.
- HU-10: Consulta del historial de mantenimientos.
- HU-11: Clasificación de mantenimientos preventivos y correctivos.
- HU-12: Asociación de mantenimientos con incidencias.

Las historias HU-09 a HU-12 deberán crearse como nuevos Issues y vincularse al tablero GitHub Projects.

Su presencia en este documento no acredita que ya hayan sido creadas en GitHub.

---

## 12. Plan de implementación del módulo de mantenimientos

La implementación se organizará por historias, evitando introducir funcionalidades adicionales.

### Fase 1: Análisis y diseño

- Confirmar las reglas de negocio.
- Identificar los campos obligatorios.
- Establecer los roles y permisos.
- Diseñar las relaciones entre cámaras, incidencias y mantenimientos.
- Definir los criterios de prueba.

### Fase 2: Registro y clasificación

**Historias:** HU-09 y HU-11.

- Incorporar la persistencia de mantenimientos.
- Implementar el registro de intervenciones.
- Incorporar los tipos preventivo y correctivo.
- Aplicar validaciones de datos.
- Ejecutar pruebas automatizadas.

### Fase 3: Consulta del historial

**Historia:** HU-10.

- Consultar los registros asociados a una cámara.
- Presentar los datos principales de las intervenciones.
- Informar cuando no existan mantenimientos registrados.
- Verificar los permisos de consulta.

### Fase 4: Asociación con incidencias

**Historia:** HU-12.

- Relacionar mantenimientos con incidencias existentes.
- Validar la correspondencia entre incidencia y cámara.
- Permitir la consulta de la relación.
- Ejecutar pruebas de integridad y autorización.

### Fase 5: Verificación y cierre

- Ejecutar las pruebas específicas del módulo.
- Ejecutar nuevamente la suite de regresión del sistema.
- Documentar los resultados.
- Actualizar los estados de los Issues.
- Consolidar las evidencias de funcionamiento.
- Actualizar el documento de requisitos.

Las fases representan una propuesta de planificación, no actividades ya ejecutadas.

---

## 13. Criterios de cierre del módulo de mantenimientos

El módulo se considerará terminado cuando:

1. Los criterios de aceptación de HU-09, HU-10, HU-11 y HU-12 estén implementados.
2. Las operaciones respeten los permisos institucionales definidos.
3. Los datos registrados persistan correctamente en SQLite.
4. Las relaciones entre cámaras, incidencias y mantenimientos mantengan su integridad.
5. Las validaciones impidan almacenar información inválida.
6. Las pruebas automatizadas específicas del módulo se ejecuten satisfactoriamente.
7. La incorporación del módulo no genere regresiones en las funcionalidades existentes.
8. Las historias estén documentadas con evidencias y actualizadas en GitHub Projects.

La aprobación de estos criterios permitirá mover las historias correspondientes a la columna Done.

---

## 14. Criterio de cierre del MVP

El prototipo estará preparado para su presentación cuando las funcionalidades declaradas como terminadas dispongan de evidencias verificables, se hayan ejecutado las pruebas correspondientes y las limitaciones identificadas estén documentadas.

Se prioriza la consolidación de autenticación, autorización, inventario, incidencias, persistencia y clasificación asistida mediante IA.

La ampliación correspondiente al módulo de mantenimientos se desarrollará de manera controlada, procurando conservar la estabilidad de los componentes ya implementados.

Las historias HU-09, HU-10, HU-11 y HU-12 no deberán presentarse como funcionalidades terminadas hasta cumplir sus criterios de aceptación y las pruebas correspondientes.

La integración de inteligencia artificial se presentará según su funcionamiento realmente comprobado, diferenciando las pruebas automatizadas de las solicitudes reales al proveedor.

La documentación, los Issues y el tablero Kanban deberán reflejar el estado real del sistema durante cada iteración del proyecto.

---

## 15. Control de cambios del documento

| Versión | Descripción | Estado |
|---|---|---|
| 1.0 | Especificación inicial de HU-01 a HU-08 y cuatro requisitos no funcionales. | Documentada. |
| 1.1 | Corrección de los requisitos de disponibilidad y usabilidad. | Incorporada. |
| 1.2 | Actualización de estados a partir de 129 pruebas automatizadas aprobadas. | Documentada. |
| 1.3 | Ampliación del backlog con HU-09 a HU-12 para el módulo de mantenimientos. | Propuesta para commit. |

**Documento:** `docs/requisitos.md`

**Repositorio:** `jecaicedotoro-source/gestion-mantenimiento-camaras`

**Rama de referencia:** `codex/consolidacion-mvp-usc`

**Commit propuesto:** `docs: ampliar requisitos del modulo de mantenimientos`
