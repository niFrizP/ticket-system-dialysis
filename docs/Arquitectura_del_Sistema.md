# Arquitectura del Sistema

## 1. Vision general
Aplicacion monolitica en PHP con renderizado del lado servidor y endpoints HTTP para operaciones asincronas del frontend.

## 2. Componentes
- Capa de presentacion:
  - `index.php` (formulario de alta de tickets)
  - `ver_ticket.php` (consulta de estado y historial)
- Capa de aplicacion:
  - `process/procesar_ticket.php` (logica principal de alta)
  - `cambiar_estado.php` (actualizaciones por tecnico)
  - `process/buscar_clientes.php` y `process/buscar_equipos.php` (autocompletado)
- Capa de acceso a datos:
  - `config/database.php` (PDO + Singleton)
- Capa de configuracion y observabilidad:
  - `bootstrap.php`
  - `config/config.php`
  - `config/sentry.php`
- Capa de soporte:
  - `includes/` (plantillas, historial, componentes reutilizables)

## 3. Flujo principal de alta de ticket
1. Usuario completa formulario en `index.php`.
2. Frontend envia POST a `process/procesar_ticket.php`.
3. Se valida payload y (opcional) token Turnstile.
4. Se inserta ticket en BD.
5. Se registra evento inicial en `ticket_historial`.
6. Se generan y envian notificaciones por correo.
7. Se responde JSON para confirmar creacion.

## 4. Flujo de seguimiento
1. Usuario accede a `ver_ticket.php?ticket=TKT-XXXXXX`.
2. Se valida formato y existencia del ticket.
3. Se verifica identidad (correo o nombre, segun disponibilidad).
4. Se muestran datos del ticket e historial.

## 5. Modelo de datos (resumen)
- `tickets`: entidad principal de incidentes.
- `ticket_historial`: bitacora de cambios de estado y comentarios.
- `centros_medicos`, `clientes`, `equipos`, `users`: catalogos y relaciones operativas.

## 6. Seguridad aplicada
- Sanitizacion de entradas.
- Consultas preparadas PDO.
- CSRF para actualizacion tecnica.
- Cabeceras de seguridad en vista de ticket.
- Secretos fuera de codigo mediante `.env`.

## 7. Consideraciones de escalabilidad
- Estado en sesiones PHP (escalar requiere sesion compartida).
- Logs en archivo local (considerar centralizacion en produccion).
- Monolito apto para carga media; para alta carga, separar API y cola de correo.
