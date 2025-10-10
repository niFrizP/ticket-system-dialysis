<?php
/**
 * Funciones para el manejo del historial de tickets
 * Sistema TEQMED
 */

/**
 * Registra un evento en el historial del ticket
 * 
 * @param PDO $db Conexión a la base de datos
 * @param int $ticket_id ID del ticket
 * @param string $usuario Nombre, email o ID del usuario que realiza la acción
 * @param string $rol Rol del usuario: 'tecnico', 'cliente', 'admin', 'sistema'
 * @param string $accion Descripción breve de la acción realizada
 * @param array $data Array asociativo con los campos opcionales:
 *                    - estado_anterior: Estado anterior del ticket
 *                    - estado_nuevo: Estado nuevo del ticket
 *                    - tecnico_anterior: Nombre del técnico anterior
 *                    - tecnico_nuevo: Nombre del técnico nuevo
 *                    - comentario: Comentario asociado al evento
 *                    - foto: Nombre o ruta del archivo de foto
 * @return bool True si se registró correctamente, false en caso contrario
 */
function registrar_historial($db, $ticket_id, $usuario, $rol, $accion, $data = []) {
    try {
        $query = "INSERT INTO ticket_historial (
            ticket_id,
            usuario,
            rol,
            accion,
            estado_anterior,
            estado_nuevo,
            tecnico_anterior,
            tecnico_nuevo,
            comentario,
            foto,
            fecha
        ) VALUES (
            :ticket_id,
            :usuario,
            :rol,
            :accion,
            :estado_anterior,
            :estado_nuevo,
            :tecnico_anterior,
            :tecnico_nuevo,
            :comentario,
            :foto,
            NOW()
        )";

        $stmt = $db->prepare($query);
        
        $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmt->bindParam(':usuario', $usuario, PDO::PARAM_STR);
        $stmt->bindParam(':rol', $rol, PDO::PARAM_STR);
        $stmt->bindParam(':accion', $accion, PDO::PARAM_STR);
        
        // Campos opcionales
        $estado_anterior = $data['estado_anterior'] ?? null;
        $estado_nuevo = $data['estado_nuevo'] ?? null;
        $tecnico_anterior = $data['tecnico_anterior'] ?? null;
        $tecnico_nuevo = $data['tecnico_nuevo'] ?? null;
        $comentario = $data['comentario'] ?? null;
        $foto = $data['foto'] ?? null;
        
        $stmt->bindParam(':estado_anterior', $estado_anterior);
        $stmt->bindParam(':estado_nuevo', $estado_nuevo);
        $stmt->bindParam(':tecnico_anterior', $tecnico_anterior);
        $stmt->bindParam(':tecnico_nuevo', $tecnico_nuevo);
        $stmt->bindParam(':comentario', $comentario);
        $stmt->bindParam(':foto', $foto);
        
        return $stmt->execute();
        
    } catch (PDOException $e) {
        error_log("Error al registrar historial: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene el historial completo de un ticket
 * 
 * @param PDO $db Conexión a la base de datos
 * @param int $ticket_id ID del ticket
 * @return array Array con los registros del historial ordenados por fecha descendente
 */
function obtener_historial($db, $ticket_id) {
    try {
        $query = "SELECT 
            id,
            ticket_id,
            usuario,
            rol,
            accion,
            estado_anterior,
            estado_nuevo,
            tecnico_anterior,
            tecnico_nuevo,
            comentario,
            foto,
            fecha
        FROM ticket_historial
        WHERE ticket_id = :ticket_id
        ORDER BY fecha DESC";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':ticket_id', $ticket_id, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        error_log("Error al obtener historial: " . $e->getMessage());
        return [];
    }
}

/**
 * Formatea la acción del historial para mostrar al usuario
 * 
 * @param array $entry Entrada del historial
 * @return string Texto formateado de la acción
 */
function formatear_accion_historial($entry) {
    $texto = htmlspecialchars($entry['accion'], ENT_QUOTES, 'UTF-8');
    
    // Agregar detalles según el tipo de acción
    if (!empty($entry['estado_anterior']) && !empty($entry['estado_nuevo'])) {
        $texto .= ': ' . htmlspecialchars($entry['estado_anterior']) . ' → ' . htmlspecialchars($entry['estado_nuevo']);
    }
    
    if (!empty($entry['tecnico_anterior']) && !empty($entry['tecnico_nuevo'])) {
        $texto .= ': ' . htmlspecialchars($entry['tecnico_anterior']) . ' → ' . htmlspecialchars($entry['tecnico_nuevo']);
    }
    
    return $texto;
}
?>
