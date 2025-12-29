# Guía Rápida: Sistema de Historial de Tickets

## 📋 Aplicar la Migración (Solo una vez)

### Opción 1: Script PHP
```bash
php migrations/apply_migration.php
```

### Opción 2: MySQL CLI
```bash
mysql -u teqmedcl_intranet -p teqmedcl_intranet < migrations/001_create_ticket_historial.sql
```

### Opción 3: phpMyAdmin
Copiar y pegar el contenido de `migrations/001_create_ticket_historial.sql`

---

## 🔧 Cómo Usar en Código

### 1. Incluir las funciones
```php
require_once __DIR__ . '/includes/ticket_historial.php';
```

### 2. Registrar eventos

#### Cambio de Estado
```php
registrar_historial($db, $ticket_id, $usuario, $rol, 'Cambio de estado', [
    'estado_anterior' => 'Pendiente',
    'estado_nuevo' => 'En proceso'
]);
```

#### Asignar Técnico
```php
registrar_historial($db, $ticket_id, $usuario, 'admin', 'Cambio de técnico', [
    'tecnico_nuevo' => 'Juan Pérez'
]);
```

#### Reasignar Técnico
```php
registrar_historial($db, $ticket_id, $usuario, 'admin', 'Cambio de técnico', [
    'tecnico_anterior' => 'Juan Pérez',
    'tecnico_nuevo' => 'María González'
]);
```

#### Agregar Comentario
```php
registrar_historial($db, $ticket_id, $usuario, $rol, 'Nuevo comentario', [
    'comentario' => 'El equipo ha sido revisado...'
]);
```

#### Adjuntar Foto
```php
registrar_historial($db, $ticket_id, $usuario, 'tecnico', 'Adjunto de foto', [
    'foto' => 'uploads/tickets/foto_123.jpg',
    'comentario' => 'Evidencia de la reparación'
]);
```

### 3. Obtener Historial
```php
$historial = obtener_historial($db, $ticket_id);

foreach ($historial as $entry) {
    echo $entry['accion'] . ' - ' . $entry['fecha'];
}
```

---

## 📊 Parámetros de registrar_historial()

```php
registrar_historial(
    PDO $db,              // Conexión a BD
    int $ticket_id,       // ID del ticket
    string $usuario,      // Ej: "Juan Pérez" o "juan@email.com"
    string $rol,          // 'tecnico', 'cliente', 'admin', 'sistema'
    string $accion,       // Ej: "Cambio de estado", "Nuevo comentario"
    array $data = []      // Datos adicionales (ver abajo)
)
```

### Datos Opcionales ($data)
- `estado_anterior` - Estado previo del ticket
- `estado_nuevo` - Estado nuevo del ticket
- `tecnico_anterior` - Nombre del técnico anterior
- `tecnico_nuevo` - Nombre del técnico nuevo
- `comentario` - Texto del comentario
- `foto` - Ruta de la foto adjunta

---

## 🎨 Tipos de Acciones Recomendadas

- `"Ticket creado"`
- `"Cambio de estado"`
- `"Cambio de técnico"`
- `"Nuevo comentario"`
- `"Adjunto de foto"`
- `"Visita programada"`
- `"Ticket completado"`
- `"Ticket reabierto"`

---

## 🎯 Ejemplo Completo de Actualización de Ticket

```php
<?php
// Incluir dependencias
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/ticket_historial.php';

$db = Database::getInstance()->getConnection();

// Datos del formulario
$ticket_id = $_POST['ticket_id'];
$nuevo_estado = $_POST['estado'];
$comentario = $_POST['comentario'];
$usuario = $_SESSION['usuario_nombre'];
$rol = $_SESSION['usuario_rol'];

// Obtener estado actual
$stmt = $db->prepare("SELECT estado FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
$ticket = $stmt->fetch();
$estado_anterior = $ticket['estado'];

// Actualizar ticket
$stmt = $db->prepare("
    UPDATE tickets 
    SET estado = ?, updated_at = NOW() 
    WHERE id = ?
");
$stmt->execute([$nuevo_estado, $ticket_id]);

// Registrar en historial
$data = [
    'estado_anterior' => $estado_anterior,
    'estado_nuevo' => $nuevo_estado
];

if (!empty($comentario)) {
    $data['comentario'] = $comentario;
}

registrar_historial($db, $ticket_id, $usuario, $rol, 'Cambio de estado', $data);

// Redirigir
header('Location: ver_ticket.php?ticket=' . $_POST['numero_ticket']);
?>
```

---

## 📁 Archivos del Sistema

### Archivos Principales
- `migrations/001_create_ticket_historial.sql` - Migración SQL
- `includes/ticket_historial.php` - Funciones de historial
- `process/procesar_ticket.php` - ✅ Ya modificado
- `ver_ticket.php` - ✅ Ya modificado

### Documentación
- `migrations/README.md` - Instrucciones de migración
- `migrations/ejemplos_uso_historial.php` - 10+ ejemplos de uso
- `migrations/VISUAL_EXAMPLE.md` - Ejemplos visuales
- `HISTORIAL_IMPLEMENTATION.md` - Resumen completo
- `QUICK_REFERENCE.md` - Esta guía

### Utilidades
- `migrations/apply_migration.php` - Script para aplicar migración

---

## ✅ Checklist de Implementación

- [ ] Aplicar migración SQL
- [ ] Verificar que tabla se creó: `SHOW TABLES LIKE 'ticket_historial';`
- [ ] Crear un ticket de prueba
- [ ] Verificar que se registró en historial: `SELECT * FROM ticket_historial;`
- [ ] Ver ticket en ver_ticket.php
- [ ] Confirmar que se muestra el historial
- [ ] Implementar actualización de estado en panel admin
- [ ] Implementar asignación de técnico
- [ ] Implementar comentarios
- [ ] Implementar adjuntos de fotos

---

## 🐛 Troubleshooting

### No se crea la tabla
```sql
-- Verificar errores de sintaxis
-- Asegurar que existe la tabla tickets
SHOW TABLES LIKE 'tickets';
```

### No se registra el historial
```php
// Verificar errores en logs
error_log("Error al registrar historial: " . $e->getMessage());

// Verificar que existe el ticket_id
$stmt = $db->prepare("SELECT id FROM tickets WHERE id = ?");
$stmt->execute([$ticket_id]);
var_dump($stmt->fetch());
```

### No se muestra el historial
```php
// Verificar que hay registros
$historial = obtener_historial($db, $ticket_id);
var_dump($historial);

// Verificar que $historial no es null
if (!isset($historial)) {
    $historial = [];
}
```

---

## 📚 Más Información

- Ver `migrations/ejemplos_uso_historial.php` para ejemplos detallados
- Ver `HISTORIAL_IMPLEMENTATION.md` para la implementación completa
- Ver `migrations/VISUAL_EXAMPLE.md` para ejemplos visuales del timeline
