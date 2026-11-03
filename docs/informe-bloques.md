# Informe incremental por bloques

El proyecto se continúa sobre su código e historial existentes. Este informe resume qué cambió y qué se verificó en cada bloque, sin presentar como ejecutadas las comprobaciones externas pendientes. El registro final con cifras, comandos, commits y URLs es [Evidencias verificadas](evidencias.md); la valoración frente al taller está en la [Matriz de entregables](matriz-entregables.md).

## Bloque 1 Auditoría y conservación del trabajo

Se comprobó el acceso a GitHub y se localizó la instalación existente en Laravel Herd. Se trabajó sobre una copia separada, conservando los archivos de la instalación original. La revisión encontró trabajo local de CU-01 todavía no reflejado íntegramente en remoto y documentación principal insuficiente. Se rescató el estado local antes de aplicar los nuevos bloques; los identificadores de base y sus diferencias deben quedar registrados con el historial final.

Se revisaron rutas, dependencias, modelos, migraciones, componentes, permisos y pruebas, además del prompt maestro y los documentos de los Módulos 1, 2 y 3. El taller original se pudo leer después de liberar su bloqueo de archivo. Los documentos y bases originales se mantuvieron fuera del repositorio. Se prepararon respaldos y se comprobaron las migraciones sobre una base de demostración, sin `migrate:fresh` sobre la base principal.

**Estado:** auditoría técnica realizada; las decisiones que requieren actualización académica o validación institucional se detallan en [Trazabilidad](trazabilidad-diseno.md).

## Bloque 2 Seguridad y refactorización de usuarios

La gestión se separó hacia `UserManagementService` y `UserPolicy`, manteniendo validación y autorización del lado servidor. La desactivación sustituye el borrado como acción administrativa, preserva identidad e historial y admite reactivación. Se protegen la cuenta propia y la continuidad del último administrador activo, incluido un cambio de rol que dejaría el sistema sin ese perfil.

Se incorporó verificación de estado activo para nuevas autenticaciones y accesos ya iniciados, sesiones, tokens y mecanismos alternativos. La gestión de cuentas no queda protegida únicamente por la visibilidad de un botón. Se revisó también la eliminación desde los ajustes personales para no conservar un flujo paralelo de borrado definitivo.

**Verificación:** pruebas automatizadas de administración, permisos, validación, estado, continuidad administrativa y acceso. Las cifras finales se consultan en [Evidencias](evidencias.md). Esto no constituye una prueba externa de penetración.

## Bloque 3 Renovación visual moderada

Se conservaron Blade, Livewire, Tailwind y Flux. Se aplicó índigo para acciones, marino en navegación y acentos turquesa, coral y ámbar. Se sustituyó la portada de Laravel por una portada propia del proyecto, sin inventar escudos o logotipos oficiales.

La navegación se condiciona a los permisos reales. El panel consulta únicamente los datos autorizados y muestra conteos de registros reales; una cuenta sin rol puede abrirlo sin que se consulten datos restringidos. Se mantuvieron temas claro/oscuro/sistema, distribución adaptable, foco visible, etiquetas y mensajes en español y reducción de movimiento.

**Verificación:** compilación de recursos, análisis de traducciones y compilación de plantillas aprobadas. **Pendiente:** inspección visual verificable de escritorio/móvil, teclado y ambos temas; una compilación satisfactoria no demuestra por sí sola todos los criterios de accesibilidad.

## Bloque 4 CU-01 Registro de cámaras y API

El formulario, API y servicio comparten reglas para datos obligatorios, IP opcional válida, inventario repetible y serie opcional única. Los valores vacíos opcionales se normalizan y la restricción de persistencia protege también frente a conflictos simultáneos. Se conservó el servicio existente y se completó la consulta del registro para demostrar su persistencia.

La API mantiene `/api/cameras` y ofrece registro, consulta individual y listado con búsqueda y paginación. Autenticación Sanctum, cuenta activa y gates protegen el acceso. Los códigos HTTP reales y la colección deben corresponder a las pruebas y documentación de la versión final.

**Verificación:** pruebas de registro, consulta, validación, unicidad, campos vacíos, permisos y API, además de migraciones aisladas. **Pendiente de entrega:** mostrar solicitudes y respuestas reales de la herramienta HTTP elegida y la demostración visual del flujo completo. Consultar el [Guion](demostracion.md).

## Bloque 5 Incidencias y OpenAI con decisión humana

El registro de incidencias persiste antes de depender del proveedor externo. `IncidentManagementService` coordina el contrato `IClasificadorIncidencias`; el adaptador HTTP real comunica con OpenAI. Modelo, clave, tiempos y límites se configuran en servidor. La interfaz permite revisar y aprobar el texto minimizado antes de transmitirlo.

El servidor valida la respuesta estructurada y las categorías del catálogo provisional. Una sugerencia válida queda pendiente y no modifica automáticamente la categoría definitiva. La aceptación, corrección o clasificación manual se registran con responsable, fecha y relación con la sugerencia. Se conserva el historial sin presentar porcentajes de confianza inventados.

**Verificación:** pruebas con clasificador falso y respuestas HTTP simuladas para resultados válidos, inválidos, categoría desconocida, errores, límites, permisos, confirmación, corrección y alternativa manual. No se consumió OpenAI en estas pruebas. **Pendiente:** llamada real controlada con clave y modelo válidos, únicamente con texto ficticio revisado. El código del adaptador es real; la conectividad con una cuenta del proveedor todavía no está verificada.

## Bloque 6 Docker y ejecución reproducible

Se prepararon Dockerfile, Compose, almacenamiento persistente SQLite y script de inicio. El contenedor exige clave de aplicación, crea respaldo antes de migrar, utiliza configuración de producción sin depuración y conserva el volumen entre reinicios. La imagen excluye secretos y datos locales mediante sus reglas de construcción.

**Verificación:** Docker CLI no está instalado localmente, pero la segunda CI remota aprobó construcción real de imagen, arranque con salud, migraciones, peticiones HTTP y comparación idéntica del registro ficticio después de reiniciar. El primer intento detectó una consulta anticipada tras el reinicio; se corrigió esperando al healthcheck. Enlaces y resultados están en las evidencias. Esto no supone un despliegue institucional de producción.

## Bloque 7 Calidad control de versiones y documentación

Las pruebas se ejecutan con SQLite aislado. Se revisan formato, compilación de vistas y recursos y se preparan comprobaciones de integración continua. Se documenta la diferencia entre pruebas automatizadas simuladas y llamadas externas. El registro público continúa deshabilitado por la administración institucional de cuentas; las omisiones de pruebas deben explicarse de forma expresa en el resultado final.

La documentación se integra al cierre de cada bloque y describe estado real, instalación, arquitectura, API, configuración, seguridad y limitaciones. Los cambios se organizan en commits descriptivos y una rama de desarrollo. La publicación, ejecución de CI, revisión y fusión de un PR requieren evidencia independiente y se registran solo cuando ocurran.

**Estado:** rama y commits publicados, [PR #1 abierto en borrador](https://github.com/jecaicedotoro-source/gestion-mantenimiento-camaras/pull/1), documentación versionada y CI final aprobada con 122 pruebas, dos omitidas y 435 aserciones; análisis estático, formato, recursos y Docker correctos. Los enlaces están en las evidencias. **Pendiente:** revisión humana e integración efectiva. No se atribuyen contribuciones a un segundo integrante ni a personas que no hayan realizado esos aportes.

## Bloque 8 Entrega académica y sustentación

Se prepararon matriz de diez entregables, trazabilidad, informe incremental y guion. La rúbrica del taller original se cotejó: funcionalidad 30 %, backend/persistencia/API 20 %, Git/colaboración 15 %, contenedores/configuración 15 %, documentación/IA responsable 10 % y demostración 10 %.

**Pendiente:** PDF único con todos los elementos del taller, evidencia de API y persistencia, enlace al PR, estado real de IA y Docker y video o demostración accesible. La fecha corresponde al aula virtual. La modalidad individual declarada en el borrador requiere acreditar la autorización docente; no se simula trabajo en pareja.

## Uso de IA durante el desarrollo

La IA apoyó auditoría del repositorio, propuestas de refactorización, implementación, pruebas y documentación. Se aceptaron cambios que conservan el stack y el trabajo previo; se corrigieron diferencias entre el diseño conceptual y las reglas posteriores, como inventario repetible y serie opcional única. La validación se realiza mediante revisión del código, pruebas automatizadas y comprobaciones ejecutadas; la responsabilidad de comprender y sustentar la solución permanece en el autor.

La documentación conserva expresamente las brechas que una herramienta de IA no puede resolver por afirmación: aprobación institucional, permiso docente original, llamada real al proveedor sin configuración, Docker sin ejecución, revisión humana del PR y demostración grabada. Los resultados no se presentan como verificados hasta obtener su evidencia.
