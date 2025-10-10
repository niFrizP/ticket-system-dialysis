# Sistema de Historial de Tickets - Resumen de Implementación

## Descripción General

Se ha implementado un sistema completo de historial de tickets que registra todas las modificaciones relevantes realizadas en un ticket, incluyendo:

- ✅ Cambios de estado
- ✅ Asignación/cambio de técnicos
- ✅ Comentarios de técnicos, clientes y administradores
- ✅ Fotos adjuntas
- ✅ Fecha y usuario responsable de cada acción

## Archivos Creados

### 1. Migración de Base de Datos
**`migrations/001_create_ticket_historial.sql`**
- Crea la tabla `ticket_historial` con todos los campos requeridos
- Incluye foreign key a la tabla `tickets`
- Incluye índices para optimizar las consultas
- Usa `CREATE TABLE IF NOT EXISTS` para seguridad

### 2. Funciones Auxiliares
**`includes/ticket_historial.php`**
- `registrar_historial()`: Función para insertar eventos en el historial
- `obtener_historial()`: Función para recuperar el historial de un ticket
- `formatear_accion_historial()`: Función para formatear las acciones para mostrar al usuario

### 3. Documentación
**`migrations/README.md`**
- Instrucciones para aplicar la migración
- Ejemplos de uso desde MySQL CLI, phpMyAdmin o cliente MySQL
- Comandos de verificación

**`migrations/ejemplos_uso_historial.php`**
- 10+ ejemplos completos de cómo usar el sistema de historial
- Casos de uso para diferentes escenarios:
  - Creación de ticket
  - Cambio de estado
  - Asignación de técnico
  - Comentarios de técnico/cliente
  - Adjunto de fotos
  - Reasignación de técnico
  - Programación de visitas
  - Completado de ticket
  - Múltiples acciones simultáneas

### 4. Script de Aplicación
**`migrations/apply_migration.php`**
- Script PHP para aplicar la migración automáticamente
- Verifica la creación correcta de la tabla
- Muestra la estructura de la tabla creada
- Incluye manejo de errores

## Archivos Modificados

### 1. process/procesar_ticket.php
**Cambios realizados:**
- Se agregó `require_once` para cargar las funciones de historial
- Después de insertar un ticket, se obtiene el ID con `lastInsertId()`
- Se registra automáticamente el evento "Ticket creado" en el historial
- Incluye manejo de errores para que el proceso continúe aunque falle el registro del historial

**Líneas modificadas:** ~35-36, ~175-198

### 2. ver_ticket.php
**Cambios realizados:**
- Se inicializa el array `$historial = []` al inicio
- Al cargar un ticket, se llama a `obtener_historial()` para recuperar todos los eventos
- Se reemplazó la timeline estática con una dinámica que itera sobre el historial
- Cada evento muestra:
  - Icono y color según el tipo de acción
  - Nombre de la acción
  - Cambios de estado (anterior → nuevo)
  - Cambios de técnico (anterior → nuevo)
  - Comentarios (en caja con borde)
  - Fotos (como miniaturas clicables)
  - Fecha y usuario responsable
- Mantiene compatibilidad con tickets existentes que no tienen historial (muestra timeline básico)

**Líneas modificadas:** ~33-34, ~90-96, ~447-630

## Estructura de la Tabla ticket_historial

```sql
CREATE TABLE ticket_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario VARCHAR(255) NOT NULL,
    rol ENUM('tecnico', 'cliente', 'admin', 'sistema') NOT NULL DEFAULT 'sistema',
    accion VARCHAR(100) NOT NULL,
    estado_anterior VARCHAR(50) NULL,
    estado_nuevo VARCHAR(50) NULL,
    tecnico_anterior VARCHAR(255) NULL,
    tecnico_nuevo VARCHAR(255) NULL,
    comentario TEXT NULL,
    foto VARCHAR(255) NULL,
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_fecha (fecha),
    INDEX idx_accion (accion)
)
```

## Flujo de Registro de Historial

### Creación de Ticket
```
Cliente llena formulario → procesar_ticket.php
                         ↓
                 INSERT INTO tickets
                         ↓
                 lastInsertId()
                         ↓
              registrar_historial(..., 'Ticket creado')
                         ↓
                  Email enviado
```

### Visualización de Historial
```
Usuario accede a ver_ticket.php?ticket=TKT-XXXXXX
                         ↓
         SELECT ticket data FROM tickets
                         ↓
   SELECT * FROM ticket_historial WHERE ticket_id = ?
                         ↓
        Renderizar timeline dinámico con:
        - Iconos y colores
        - Cambios de estado/técnico
        - Comentarios
        - Fotos
        - Fecha y usuario
```

## Características Principales

### 1. Registro Automático
- Los nuevos tickets se registran automáticamente en el historial
- No requiere cambios adicionales para tickets nuevos

### 2. Compatibilidad Retroactiva
- Los tickets existentes sin historial muestran una timeline básica
- El historial comienza a registrarse desde el momento de la implementación

### 3. Display Inteligente
- Colores diferenciados según tipo de acción:
  - 🎫 Verde: Ticket creado
  - ⏳ Naranja: Pendiente
  - 🔧 Azul: En proceso
  - ✅ Rojo: Completado
  - 👤 Púrpura: Cambio de técnico
  - 💬 Cyan: Comentario
  - 📷 Rosa: Foto adjunta
  
### 4. Información Completa
- Usuario responsable de cada acción
- Rol del usuario (cliente, técnico, admin, sistema)
- Fecha y hora exacta
- Detalles específicos según el tipo de evento

### 5. Fotos como Miniaturas
- Las fotos se muestran como miniaturas de 80x80px
- Click en la foto abre la imagen en nueva pestaña
- Preserva la proporción de la imagen original

## Próximos Pasos para Implementación Completa

### 1. Aplicar la Migración
```bash
# Opción 1: Desde línea de comandos
php migrations/apply_migration.php

# Opción 2: Desde phpMyAdmin
# Copiar el contenido de 001_create_ticket_historial.sql y ejecutar
```

### 2. Integrar en Paneles de Admin/Técnico
Cuando se creen los paneles para actualizar tickets, usar:

```php
// Al cambiar estado
registrar_historial($db, $ticket_id, $usuario, $rol, 'Cambio de estado', [
    'estado_anterior' => 'pendiente',
    'estado_nuevo' => 'en_proceso'
]);

// Al asignar técnico
registrar_historial($db, $ticket_id, $usuario, 'admin', 'Cambio de técnico', [
    'tecnico_nuevo' => 'Juan Pérez'
]);

// Al agregar comentario
registrar_historial($db, $ticket_id, $usuario, $rol, 'Nuevo comentario', [
    'comentario' => 'El equipo ha sido reparado...'
]);

// Al adjuntar foto
registrar_historial($db, $ticket_id, $usuario, $rol, 'Adjunto de foto', [
    'foto' => 'uploads/tickets/foto_123.jpg',
    'comentario' => 'Evidencia de la reparación'
]);
```

### 3. Testing
- Crear un ticket de prueba
- Verificar que aparece en ticket_historial
- Ver el ticket en ver_ticket.php
- Confirmar que se muestra el historial

## Ventajas del Sistema

1. **Trazabilidad Completa**: Cada acción queda registrada permanentemente
2. **Auditoría**: Se puede ver quién hizo qué y cuándo
3. **Transparencia**: Clientes pueden ver el progreso de su ticket
4. **Historial Visual**: Timeline intuitivo y fácil de entender
5. **Escalable**: Fácil agregar nuevos tipos de eventos
6. **Performance**: Índices optimizados para consultas rápidas
7. **Mantenible**: Código bien documentado y ejemplos abundantes

## Notas de Seguridad

- Todas las salidas usan la función `e()` para prevenir XSS
- Las consultas usan prepared statements para prevenir SQL injection
- El sistema continúa funcionando aunque falle el registro del historial
- Las fotos son opcionales y solo se muestran si existen
- Los comentarios son sanitizados antes de mostrarlos

## Soporte y Mantenimiento

Para agregar nuevos tipos de eventos al historial, simplemente llamar a `registrar_historial()` con los parámetros apropiados. Ver `migrations/ejemplos_uso_historial.php` para referencias completas.
