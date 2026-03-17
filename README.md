# Ticket System Dialysis - TEQMED

## Overview
Sistema web de gestion de tickets para soporte tecnico en equipos de dialisis de TEQMED.
Permite registrar incidencias desde un formulario publico, consultar su estado por numero de ticket, y mantener un historial de cambios para trazabilidad operativa.

## Caracteristicas Principales
- Creacion de tickets desde formulario web en dos pasos.
- Busqueda asistida de centros medicos y equipos asociados.
- Validacion anti-bot con Cloudflare Turnstile (opcional segun configuracion).
- Seguimiento de ticket por URL con verificacion de identidad.
- Actualizacion de estado por tecnico con comentario y adjuntos.
- Registro de historial de cambios por ticket (auditoria basica).
- Integracion de notificaciones por correo (PHPMailer / SMTP).
- Integracion con Sentry para monitoreo de errores (opcional).
- Carga de configuracion mediante variables de entorno (.env).

## Stack Tecnologico
- Backend: PHP >= 7.4
- Base de datos: MySQL / MariaDB (via PDO)
- Frontend: HTML + Tailwind CSS + JavaScript vanilla
- Dependencias PHP (Composer):
  - sentry/sentry
  - phpmailer/phpmailer
  - vlucas/phpdotenv
  - guzzlehttp/guzzle
- Observabilidad: Sentry

## Estructura del Proyecto
```text
.
|-- README.md
|-- bootstrap.php
|-- index.php
|-- ver_ticket.php
|-- cambiar_estado.php
|-- process/
|   |-- procesar_ticket.php
|   |-- buscar_clientes.php
|   |-- buscar_equipos.php
|-- config/
|   |-- config.php
|   |-- database.php
|   |-- sentry.php
|-- includes/
|   |-- header.php
|   |-- footer.php
|   |-- ticket_historial.php
|   |-- nuevo_ticket_cliente.php
|   |-- nuevo_ticket_soporte.php
|-- migrations/
|   |-- 001_create_ticket_historial.sql
|   |-- apply_migration.php
|-- assets/
|   |-- css/
|   |-- js/
|   |-- images/
|-- logs/
`-- docs/
```

## Modulos Principales
- Captura de tickets:
  - `index.php`: interfaz principal y formulario.
  - `process/procesar_ticket.php`: validacion, persistencia, historial y notificaciones.
- Consulta de tickets:
  - `ver_ticket.php`: vista de detalle, estado, historial y validacion de acceso.
- Gestion de estado tecnico:
  - `cambiar_estado.php`: cambio de estado, comentario tecnico, fecha de visita y adjuntos.
- Busquedas dinamicas:
  - `process/buscar_clientes.php`: autocompletado de centros medicos.
  - `process/buscar_equipos.php`: autocompletado de equipos por centro.
- Configuracion y bootstrap:
  - `bootstrap.php`: inicializacion de autoload, .env y Sentry.
  - `config/database.php`: conexion PDO segura (Singleton).
- Historial de cambios:
  - `includes/ticket_historial.php` + `migrations/001_create_ticket_historial.sql`.

## Requisitos del Sistema
- PHP 7.4 o superior.
- Extensiones PHP recomendadas: pdo_mysql, mbstring, json, curl, openssl.
- Servidor web (Apache o Nginx) con soporte para PHP.
- MySQL/MariaDB.
- Composer 2.x para instalar dependencias.
- Permisos de escritura en:
  - `logs/`
  - `uploads/tickets/` (si se usan adjuntos)

## Documentacion
Toda la documentacion operativa y tecnica se encuentra en la carpeta `docs/`.

- `docs/Guia_Instalacion.md`
- `docs/Arquitectura_del_Sistema.md`
- `docs/Estandares_de_Codigo.md`
- `docs/Guia_de_Despliegue.md`
- `docs/Guia_para_Contribuidores.md`
- `docs/Configuracion_de_OneDrive.md`

Adicionalmente:
- `docs/SECURITY.md`
- `docs/QUICK_REFERENCE.md`
- `docs/migrations_README.md`

## Licencia
Este proyecto es de uso interno de TEQMED, salvo indicacion contraria.
Si necesitas publicar o reutilizar parte del codigo, define una licencia explicita antes de distribuir.
