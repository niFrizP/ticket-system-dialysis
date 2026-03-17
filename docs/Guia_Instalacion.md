# Guia de Instalacion

## 1. Objetivo
Esta guia describe la instalacion local o en servidor del sistema de tickets TEQMED.

## 2. Prerrequisitos
- PHP 7.4 o superior.
- MySQL/MariaDB.
- Composer 2.x.
- Servidor web (Apache/Nginx) con acceso al directorio del proyecto.

## 3. Clonar o copiar el proyecto
Copiar el codigo fuente en el directorio de despliegue, por ejemplo:
- Windows (XAMPP): `C:/xampp/htdocs/ticket-system-dialysis`
- Linux: `/var/www/ticket-system-dialysis`

## 4. Instalar dependencias PHP
En la raiz del proyecto:

```bash
composer install --no-dev --optimize-autoloader
```

Para entorno local de desarrollo:

```bash
composer install
```

## 5. Configurar variables de entorno (.env)
Crear un archivo `.env` en la raiz del proyecto.

Ejemplo minimo:

```env
APP_ENV=production

DB_HOST=localhost
DB_NAME=nombre_bd
DB_USER=usuario_bd
DB_PASSWORD=clave_bd
DB_CHARSET=utf8mb4

TURNSTILE_SITE_KEY=
TURNSTILE_SECRET=

SENTRY_DSN=
SENTRY_DEBUG=0

MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=no-reply@dominio.com
MAIL_FROM_NAME=Sistema de Tickets
```

## 6. Crear base de datos y tablas
1. Crear la base de datos objetivo.
2. Ejecutar los scripts SQL requeridos por el proyecto (incluyendo tablas base del sistema).
3. Ejecutar la migracion de historial:

```bash
php migrations/apply_migration.php
```

Si no se usa el script, ejecutar manualmente:
- `migrations/001_create_ticket_historial.sql`

## 7. Permisos de carpetas
Asegurar permisos de escritura para:
- `logs/`
- `uploads/tickets/` (si aplica)

## 8. Verificacion basica
- Abrir el formulario principal en navegador.
- Crear un ticket de prueba.
- Verificar insercion en base de datos.
- Verificar visualizacion en `ver_ticket.php?ticket=TKT-XXXXXX`.
- Revisar logs en caso de error.

## 9. Problemas comunes
- Error de conexion a BD: revisar variables `DB_*`.
- No llegan correos: validar `MAIL_*` y salida SMTP.
- Turnstile falla: revisar `TURNSTILE_SITE_KEY` y `TURNSTILE_SECRET`.
- Errores no reportados: validar `SENTRY_DSN` y red saliente.
