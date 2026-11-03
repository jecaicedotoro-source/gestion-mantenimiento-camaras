# Matriz de los diez entregables del Módulo 3

La revisión del 3 de noviembre de 2026 utiliza el taller original **Actividad Evaluable Módulo 3 Desarrollo Integración Servicios**, los documentos de los Módulos 1 y 2 y las decisiones posteriores del proyecto. El objetivo es demostrar CU-01, registrar una cámara de seguridad, y presentar de forma separada la clasificación asistida de incidencias.

Esta matriz diferencia código preparado, comprobaciones ejecutadas y evidencia académica pendiente. **Apto** significa que el incremento tiene implementación y verificación técnica disponible; no representa una calificación ni sustituye la demostración y la revisión docente. **Parcial** identifica requisitos con trabajo realizado y brechas concretas. **Pendiente** significa que la evidencia mínima todavía no se ha obtenido. La ejecución final, las revisiones GitHub y sus enlaces deben consultarse en [Evidencias verificadas](evidencias.md).

| N.º | Entregable obligatorio y evidencia mínima del taller | Estado actual | Preparado o implementado | Verificado y brecha restante |
| --- | --- | --- | --- | --- |
| 1 | Repositorio colaborativo: enlace, código y contribuciones de ambos integrantes | Parcial | Repositorio existente y cambios organizados sobre su historial; no se sustituyó el proyecto por uno nuevo | El borrador académico declara trabajo individual con permiso docente. No se recuperó la autorización original. Confirmar esa excepción o aportar contribuciones reales de los integrantes; no simular autores ni commits |
| 2 | MVP funcional: al menos un caso de uso completo, ejecutable y demostrado | Parcial | CU-01 incluye formulario, autorización, servicio, validaciones, registro, consulta y API | Hay pruebas automatizadas del flujo y persistencia. Falta la evidencia visual final y el video o demostración verificable descritos en [Guion](demostracion.md) |
| 3 | Backend y base de datos: servicios conectados a persistencia y operaciones verificables | Apto en verificación técnica | Servicios de usuarios, cámaras e incidencias; migraciones SQLite; restricciones y transacciones | Pruebas en base aislada y migraciones comprobadas sobre copia de demostración. El resultado final y la conservación de datos se registran en [Evidencias](evidencias.md). No se migró destructivamente la instalación original |
| 4 | API documentada: rutas, métodos, parámetros, solicitudes, respuestas y códigos reales | Parcial | Endpoints HTTP de cámaras e incidencias; autenticación Sanctum, permisos y validación; documentación y colección preparables desde el contrato real | Pruebas HTTP automatizadas de respuestas, persistencia y errores. Completar y enlazar colección, ejecutar la demostración de peticiones reales en entorno aislado y adjuntar evidencia sin tokens |
| 5 | Control de versiones: commits, rama y Pull Request revisado e integrado | Parcial | Historial base preservado, rescate de los cambios locales existentes y desarrollo incremental | La existencia de una rama o commit no demuestra revisión ni fusión. Registrar URL y estado reales del PR y comprobar la integración. No se declara PR abierto, CI remoto aprobado o merge realizado en este documento |
| 6 | Configuración segura: `.env.example` o equivalente sin credenciales reales | Apto en preparación técnica | `.env.example` con configuración reproducible; exclusión de `.env`; clave OpenAI en servidor; cuentas iniciales sin contraseña predeterminada | Revisar el conjunto exacto de archivos publicados y su historial antes del push. Mantener respaldos y bases SQLite fuera del repositorio. Una búsqueda de secretos no prueba ausencia absoluta de información sensible |
| 7 | Integración de IA cuando aplique: integración funcional coherente con diseño o justificación de no aplicabilidad | Parcial | Adaptador HTTP real OpenAI mediante `IClasificadorIncidencias`, respuesta estructurada validada, minimización y vista previa, límites, trazabilidad, decisión humana y alternativa manual | Pruebas automatizadas con dobles y respuestas HTTP simuladas. No se realizó llamada real a OpenAI: falta clave/modelo configurados y prueba controlada con texto ficticio. En este proyecto la IA sí aplica por decisión posterior expresa; no corresponde justificar su exclusión |
| 8 | Contenedorización: Dockerfile y Compose o equivalentes documentados | Parcial | Dockerfile, Compose, volumen SQLite, inicio con respaldo y migración, comprobación de salud y configuración sin secretos incorporados a la imagen | El equipo de trabajo no dispone de Docker CLI local. Falta construir y ejecutar el contenedor, verificar volumen y reinicio, y conservar resultado real. Un archivo Compose no equivale a una ejecución comprobada |
| 9 | Documentación técnica: README con instalación, ejecución, arquitectura, funcionalidades y decisiones | Parcial | Documentación de trazabilidad, bloques, demostración y matriz; README incremental y documentación de API coordinados con el código | Cotejar todos los comandos y enlaces de la versión final, incorporar pruebas y brechas reales y validar la documentación publicada en GitHub. El README es parte del criterio de cierre de cada bloque |
| 10 | Demostración del MVP: video o demostración verificable | Pendiente de evidencia de entrega | Guion reproducible con resultados esperados, casos de error y criterios de privacidad | Grabar o realizar la demostración, verificar acceso al enlace y producir el PDF único exigido por el taller. Las pruebas automatizadas y capturas del código no sustituyen esa evidencia |

## Rúbrica oficial cotejada con el taller original

| Criterio | Ponderación | Evidencia que concentra la verificación |
| --- | ---: | --- |
| Funcionalidad del MVP y coherencia con el diseño | 30 % | CU-01 completo y [trazabilidad](trazabilidad-diseno.md) |
| Backend, persistencia y API documentada | 20 % | Servicios, consultas, restricciones, respuestas HTTP y colección |
| Git, ramas, commits y trabajo colaborativo | 15 % | Historial real, PR revisado e integrado y aportes o excepción docente acreditada |
| Contenedorización y configuración segura | 15 % | Construcción y ejecución reproducibles, configuración y secretos protegidos |
| Documentación técnica y uso responsable de IA | 10 % | README, decisiones, limitaciones y revisión del código asistido |
| Demostración y sustentación | 10 % | Video o demostración accesible y explicación del autor |
| Total | 100 % | No se calcula una nota estimada |

## PDF que exige el taller

La entrega al aula virtual debe ser **un único PDF** con portada, integrantes o modalidad individual acreditada, nombre del proyecto, descripción del MVP, tecnologías y justificación, enlace al repositorio, evidencias de API y persistencia, enlace al PR, estado real de IA, evidencia de contenedorización y enlace al video o demostración. No es necesario pegar todo el código en el PDF. La fecha es la que establezca la coordinación en el aula; este repositorio no inventa una fecha de entrega.

La ausencia de llamada OpenAI real, ejecución Docker, revisión/fusión de PR, evidencia visual y PDF deben permanecer visibles hasta obtener sus resultados. El registro central es [Evidencias verificadas](evidencias.md).
