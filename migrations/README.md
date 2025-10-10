# Migraciones de Base de Datos

Este directorio contiene las migraciones SQL para el sistema de tickets.

## Cómo aplicar las migraciones

### Opción 1: Ejecutar directamente en MySQL

```bash
mysql -u teqmedcl_intranet -p teqmedcl_intranet < migrations/001_create_ticket_historial.sql
```

### Opción 2: Usar phpMyAdmin

1. Acceder a phpMyAdmin
2. Seleccionar la base de datos `teqmedcl_intranet`
3. Ir a la pestaña "SQL"
4. Copiar y pegar el contenido del archivo `001_create_ticket_historial.sql`
5. Hacer clic en "Ejecutar"

### Opción 3: Usar un cliente MySQL

Abrir el archivo `001_create_ticket_historial.sql` y ejecutar el contenido en su cliente MySQL preferido.

## Migraciones disponibles

### 001_create_ticket_historial.sql

Crea la tabla `ticket_historial` para registrar todas las modificaciones de tickets:

- Cambios de estado
- Asignación de técnicos
- Comentarios
- Fotos adjuntas
- Fecha y usuario de cada acción

**Importante:** Esta migración usa `CREATE TABLE IF NOT EXISTS`, por lo que es seguro ejecutarla múltiples veces.

## Verificar que la migración se aplicó correctamente

Después de ejecutar la migración, puede verificar que la tabla se creó correctamente:

```sql
SHOW TABLES LIKE 'ticket_historial';
DESCRIBE ticket_historial;
```

O consultar los primeros registros (después de crear algunos tickets):

```sql
SELECT * FROM ticket_historial ORDER BY fecha DESC LIMIT 10;
```
