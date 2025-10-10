-- Migration: Create ticket_historial table
-- Description: Table to track all relevant modifications to tickets including status changes,
--              comments, photos, technician assignments, etc.
-- Created: 2025-10-10

CREATE TABLE IF NOT EXISTS ticket_historial (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT NOT NULL,
    usuario VARCHAR(255) NOT NULL COMMENT 'Nombre, email o ID del usuario que hizo el cambio',
    rol ENUM('tecnico', 'cliente', 'admin', 'sistema') NOT NULL DEFAULT 'sistema',
    accion VARCHAR(100) NOT NULL COMMENT 'Tipo de acción: Cambio de estado, Nuevo comentario, etc.',
    estado_anterior VARCHAR(50) NULL,
    estado_nuevo VARCHAR(50) NULL,
    tecnico_anterior VARCHAR(255) NULL,
    tecnico_nuevo VARCHAR(255) NULL,
    comentario TEXT NULL,
    foto VARCHAR(255) NULL COMMENT 'Nombre o ruta del archivo de foto',
    fecha TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraint
    CONSTRAINT fk_ticket_historial_ticket
        FOREIGN KEY (ticket_id) 
        REFERENCES tickets(id)
        ON DELETE CASCADE
        ON UPDATE CASCADE,
    
    -- Indexes for better performance
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_fecha (fecha),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
COMMENT='Historial de modificaciones de tickets';
