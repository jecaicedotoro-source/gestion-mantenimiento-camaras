# Evidencias de despliegue local - Sesión 4

**Proyecto:** Sistema Web de Inventario y Mantenimiento
de Cámaras de Seguridad - USC.

**Fecha:** 17 de noviembre de 2026.

## 1. Objetivo

Verificar el despliegue reproducible de la aplicación
Laravel mediante Docker, su disponibilidad HTTP,
la inicialización de SQLite y la persistencia de datos
tras reiniciar el contenedor.

## 2. Tecnologías

- PHP 8.5 y Laravel 13.
- Docker y Docker Compose.
- SQLite con volumen persistente.
- GitHub Actions.
- OpenAI API y GroqCloud como proveedores de IA.

## 3. Entorno de demostración

- Proyecto Compose: usc-s4-61bc732b
- Imagen: usc-camaras-s4:local
- Puerto local: 127.0.0.1:8097
- Volumen: usc-s4-61bc732b_app-storage
- Entorno aislado de la base SQLite principal.

Durante la demostración se deshabilitaron las
integraciones externas de inteligencia artificial.

Las credenciales no se incorporaron al repositorio
ni a la imagen Docker.

## 4. Construcción y arranque

Se construyó correctamente la imagen Docker.

Docker Compose creó la red y el volumen independiente.

El contenedor alcanzó el estado healthy.

La aplicación respondió HTTP 200 en /up.

## 5. Persistencia y migraciones

Laravel informó 14 migraciones ejecutadas correctamente.

Se creó una tabla de demostración:

s4_persistencia_prueba

Se registró el valor:

USC_S4_PERSISTENCIA_OK

Después de reiniciar el contenedor se consultó
nuevamente la tabla.

Resultado:

PERSISTENCIA SQLITE CONFIRMADA

El contenedor recuperó el estado healthy y
conservó la publicación local en el puerto 8097.

## 6. Integración de inteligencia artificial

El proyecto conserva OpenAI como proveedor
predeterminado y Groq como proveedor alternativo.

La integración Groq se verificó anteriormente
con una clasificación ficticia utilizando
openai/gpt-oss-20b.

El PR #19 fue fusionado tras aprobar los
trabajos ci y docker de GitHub Actions.

## 7. Seguridad

- SQLite principal no utilizado en la demostración.
- Credenciales API excluidas del repositorio.
- Clave de aplicación temporal para el contenedor.
- Sin exposición pública del puerto.
- Sin solicitudes reales a IA durante el despliegue.
- No se eliminaron volúmenes persistentes.

## 8. Limitaciones

Esta prueba acredita un despliegue académico local,
no un servicio institucional en producción.

La persistencia se verificó tras reiniciar el
contenedor, no después de destruirlo y recrearlo.

La clave APP_KEY utilizada fue temporal.
Para recreaciones posteriores debe gestionarse
una clave estable y protegida fuera de Git.

Un entorno institucional necesita HTTPS,
gestión de secretos, respaldos externos,
supervisión, políticas de acceso y
una infraestructura web adecuada.

## 9. Conclusión

Se verificaron satisfactoriamente:

- Construcción y arranque Docker.
- Disponibilidad HTTP 200.
- Ejecución de 14 migraciones.
- Persistencia SQLite tras reinicio.
- Recuperación del estado healthy.
- Aislamiento respecto de la base principal.

El resultado demuestra el funcionamiento del
despliegue local y su persistencia básica.