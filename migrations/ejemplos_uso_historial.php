<?php
/**
 * Ejemplos de uso del sistema de historial de tickets
 * Este archivo muestra cómo registrar diferentes tipos de eventos en el historial
 * 
 * NOTA: Este archivo es solo para referencia. No se debe ejecutar directamente.
 * Los ejemplos se deben integrar en las funciones correspondientes del sistema.
 */

// Incluir las funciones de historial
require_once __DIR__ . '/../includes/ticket_historial.php';

// Conectar a la base de datos
require_once __DIR__ . '/../config/database.php';
$db = Database::getInstance()->getConnection();

// ============================================================================
// EJEMPLO 1: Registrar creación de ticket (ya implementado en procesar_ticket.php)
// ============================================================================

$ticket_id = 123; // ID del ticket recién creado

registrar_historial(
    $db,
    $ticket_id,
    'Juan Pérez (juan@example.com)',
    'cliente',
    'Ticket creado',
    [
        'estado_nuevo' => 'pendiente',
        'comentario' => 'Ticket creado por el cliente. Falla: Equipo no enciende'
    ]
);

// ============================================================================
// EJEMPLO 2: Registrar cambio de estado
// ============================================================================

// Cuando un técnico o admin cambia el estado del ticket
registrar_historial(
    $db,
    $ticket_id,
    'María González (Admin)',
    'admin',
    'Cambio de estado',
    [
        'estado_anterior' => 'pendiente',
        'estado_nuevo' => 'en_proceso'
    ]
);

// ============================================================================
// EJEMPLO 3: Registrar asignación de técnico
// ============================================================================

// Cuando se asigna un técnico al ticket
registrar_historial(
    $db,
    $ticket_id,
    'Sistema Automático',
    'sistema',
    'Cambio de técnico',
    [
        'tecnico_anterior' => null,
        'tecnico_nuevo' => 'Carlos Ramírez',
        'comentario' => 'Técnico asignado automáticamente según disponibilidad'
    ]
);

// ============================================================================
// EJEMPLO 4: Registrar comentario del técnico
// ============================================================================

// Cuando un técnico agrega un comentario
registrar_historial(
    $db,
    $ticket_id,
    'Carlos Ramírez',
    'tecnico',
    'Nuevo comentario',
    [
        'comentario' => 'Revisé el equipo. El problema es en la fuente de poder. Se requiere reemplazo.'
    ]
);

// ============================================================================
// EJEMPLO 5: Registrar comentario del cliente
// ============================================================================

// Cuando un cliente agrega un comentario o información adicional
registrar_historial(
    $db,
    $ticket_id,
    'Juan Pérez (juan@example.com)',
    'cliente',
    'Nuevo comentario',
    [
        'comentario' => 'El equipo es crítico para el turno de mañana. Por favor dar prioridad.'
    ]
);

// ============================================================================
// EJEMPLO 6: Registrar adjunto de foto
// ============================================================================

// Cuando se adjunta una foto al ticket
$foto_nombre = 'foto_' . time() . '.jpg';
// ... código para subir la foto ...

registrar_historial(
    $db,
    $ticket_id,
    'Carlos Ramírez',
    'tecnico',
    'Adjunto de foto',
    [
        'foto' => 'uploads/tickets/' . $foto_nombre,
        'comentario' => 'Foto del equipo mostrando el daño en la fuente de poder'
    ]
);

// ============================================================================
// EJEMPLO 7: Registrar cambio de técnico (reasignación)
// ============================================================================

// Cuando se cambia el técnico asignado
registrar_historial(
    $db,
    $ticket_id,
    'María González (Admin)',
    'admin',
    'Cambio de técnico',
    [
        'tecnico_anterior' => 'Carlos Ramírez',
        'tecnico_nuevo' => 'Pedro Soto',
        'comentario' => 'Reasignado por ausencia del técnico original'
    ]
);

// ============================================================================
// EJEMPLO 8: Registrar programación de visita
// ============================================================================

// Cuando se programa una fecha de visita técnica
registrar_historial(
    $db,
    $ticket_id,
    'Pedro Soto',
    'tecnico',
    'Visita programada',
    [
        'comentario' => 'Visita programada para el 15/10/2025 a las 14:00 hrs'
    ]
);

// ============================================================================
// EJEMPLO 9: Registrar completado del ticket
// ============================================================================

// Cuando se marca el ticket como completado
registrar_historial(
    $db,
    $ticket_id,
    'Pedro Soto',
    'tecnico',
    'Cambio de estado',
    [
        'estado_anterior' => 'en_proceso',
        'estado_nuevo' => 'completado',
        'comentario' => 'Se reemplazó la fuente de poder. Equipo funcionando correctamente.'
    ]
);

// ============================================================================
// EJEMPLO 10: Registrar múltiples acciones en un solo proceso
// ============================================================================

// Por ejemplo, al completar un ticket con comentario y foto
$foto_reparacion = 'uploads/tickets/reparacion_' . time() . '.jpg';

// Registrar el comentario de cierre
registrar_historial(
    $db,
    $ticket_id,
    'Pedro Soto',
    'tecnico',
    'Nuevo comentario',
    [
        'comentario' => 'Reparación completada exitosamente. Se adjunta evidencia fotográfica.'
    ]
);

// Registrar la foto
registrar_historial(
    $db,
    $ticket_id,
    'Pedro Soto',
    'tecnico',
    'Adjunto de foto',
    [
        'foto' => $foto_reparacion,
        'comentario' => 'Foto del equipo después de la reparación'
    ]
);

// Cambiar estado a completado
registrar_historial(
    $db,
    $ticket_id,
    'Pedro Soto',
    'tecnico',
    'Cambio de estado',
    [
        'estado_anterior' => 'en_proceso',
        'estado_nuevo' => 'completado'
    ]
);

// ============================================================================
// EJEMPLO DE INTEGRACIÓN EN UN FORMULARIO DE ACTUALIZACIÓN DE TICKET
// ============================================================================

/*
// En un archivo como update_ticket.php o similar:

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ticket_id = (int)$_POST['ticket_id'];
    $nuevo_estado = $_POST['estado'];
    $comentario = $_POST['comentario'] ?? '';
    $usuario = $_SESSION['usuario_nombre'] ?? 'Usuario Desconocido';
    $rol = $_SESSION['usuario_rol'] ?? 'admin';
    
    // Obtener el estado actual del ticket
    $stmt = $db->prepare("SELECT estado FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $ticket = $stmt->fetch();
    $estado_anterior = $ticket['estado'];
    
    // Actualizar el ticket en la BD
    $stmt = $db->prepare("UPDATE tickets SET estado = ?, updated_at = NOW() WHERE id = ?");
    $stmt->execute([$nuevo_estado, $ticket_id]);
    
    // Registrar en el historial
    $data = [
        'estado_anterior' => $estado_anterior,
        'estado_nuevo' => $nuevo_estado
    ];
    
    if (!empty($comentario)) {
        $data['comentario'] = $comentario;
    }
    
    registrar_historial($db, $ticket_id, $usuario, $rol, 'Cambio de estado', $data);
    
    // Redirigir o mostrar mensaje de éxito
    header('Location: ver_ticket.php?ticket=' . $_POST['numero_ticket']);
    exit;
}
*/

// ============================================================================
// OBTENER Y MOSTRAR EL HISTORIAL (ejemplo básico - ya implementado en ver_ticket.php)
// ============================================================================

/*
$ticket_id = 123;
$historial = obtener_historial($db, $ticket_id);

foreach ($historial as $entry) {
    echo "<div class='historial-item'>";
    echo "<strong>" . htmlspecialchars($entry['accion']) . "</strong><br>";
    echo "Por: " . htmlspecialchars($entry['usuario']) . " (" . $entry['rol'] . ")<br>";
    echo "Fecha: " . date('d/m/Y H:i', strtotime($entry['fecha'])) . "<br>";
    
    if (!empty($entry['comentario'])) {
        echo "Comentario: " . nl2br(htmlspecialchars($entry['comentario'])) . "<br>";
    }
    
    if (!empty($entry['foto'])) {
        echo "<img src='" . htmlspecialchars($entry['foto']) . "' width='100'><br>";
    }
    
    echo "</div>";
}
*/
?>
