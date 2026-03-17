# Guia de Despliegue

## 1. Estrategia recomendada
Despliegue por version con validacion previa en entorno de staging.

## 2. Preparacion
- Verificar que el entorno objetivo cumpla requisitos de PHP/MySQL.
- Respaldar base de datos y archivos de aplicacion.
- Confirmar archivo `.env` del entorno destino.

## 3. Pasos de despliegue
1. Publicar codigo en servidor destino.
2. Instalar dependencias:

```bash
composer install --no-dev --optimize-autoloader
```

3. Ejecutar migraciones pendientes:

```bash
php migrations/apply_migration.php
```

4. Ajustar permisos de carpetas con escritura:
- `logs/`
- `uploads/tickets/`

5. Reiniciar servicio PHP-FPM/Apache si corresponde.

## 4. Checklist post-despliegue
- Carga de pagina principal.
- Creacion de ticket de prueba.
- Consulta de ticket creado.
- Cambio de estado tecnico.
- Generacion de registros en logs.
- Confirmacion de envio de correo (si aplica).
- Verificacion de eventos en Sentry (si esta activo).

## 5. Rollback
Si el despliegue falla:
1. Restaurar codigo de la version estable anterior.
2. Restaurar backup de BD si hubo cambios incompatibles.
3. Revisar logs de aplicacion y servidor.

## 6. Buenas practicas
- No editar `.env` en tiempo de release sin control de cambios.
- Mantener ventana de mantenimiento definida.
- Registrar version, fecha y responsable de cada despliegue.
