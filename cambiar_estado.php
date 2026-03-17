<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

if (
    empty($_POST['csrf_token']) ||
    empty($_SESSION['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
) {
    $_SESSION['form_error'] = 'Sesión inválida. Recarga la página e intenta nuevamente.';
    header('Location: ver_ticket.php?ticket=' . urlencode($_POST['numero_ticket'] ?? ''));
    exit;
}

if (!isset($_POST['ticket_id'])) {
    $_SESSION['form_error'] = 'Ticket ID no especificado.';
    header('Location: index.html');
    exit;
}

$ticket_id = intval($_POST['ticket_id']);
$estado = $_POST['estado'] ?? null;
$comentario = trim($_POST['comentario_tecnico'] ?? '');
$fecha_visita = $_POST['fecha_visita'] ?? null;
$fecha_visita_anterior = null;
$foto_nombre = null;
$foto_ruta = null;

try {
    // Procesar imagen si existe
    if (
        isset($_FILES['foto_comentario']) &&
        $_FILES['foto_comentario']['error'] === UPLOAD_ERR_OK
    ) {
        $tmp = $_FILES['foto_comentario']['tmp_name'];
        $nombre_original = basename($_FILES['foto_comentario']['name']);
        $ext = strtolower(pathinfo($nombre_original, PATHINFO_EXTENSION));
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx', 'xls', 'xlsx'];
        if (in_array($ext, $permitidas) && $_FILES['foto_comentario']['size'] <= 10 * 1024 * 1024) {
            $ruta_destino = __DIR__ . '/uploads/tickets/';
            if (!is_dir($ruta_destino)) {
                mkdir($ruta_destino, 0777, true);
            }
            $foto_nombre = 'ticket_' . $ticket_id . '_' . uniqid() . '.' . $ext;
            move_uploaded_file($tmp, $ruta_destino . $foto_nombre);
            $foto_ruta = 'uploads/tickets/' . $foto_nombre;
        }
    }

    $db = Database::getInstance()->getConnection();

    // Obtener datos anteriores para historial
    $stmt = $db->prepare("SELECT estado, tecnico_asignado_id, fecha_visita FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);
    $fecha_visita_anterior = $prev['fecha_visita'];

    // Si se proporciona fecha de visita, procesarla
    if (!empty($fecha_visita)) {
        // Convertir fecha al formato MySQL
        $fecha_visita_mysql = date('Y-m-d H:i:s', strtotime($fecha_visita));

        // Determinar si es una reprogramación
        $es_reprogramacion = !empty($fecha_visita_anterior) && $fecha_visita_anterior !== $fecha_visita_mysql;

        // Si es una reprogramación y el estado no es ya reagendado, cambiarlo
        if ($es_reprogramacion && $estado !== 'reagendado') {
            $estado = 'reagendado';
        }

        // Actualizar ticket con fecha de visita
        $sql = "UPDATE tickets SET estado = ?, fecha_visita = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$estado, $fecha_visita_mysql, $ticket_id]);
    } else {
        // Actualizar solo el estado del ticket
        $sql = "UPDATE tickets SET estado = ? WHERE id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([$estado, $ticket_id]);
    }

    // Determinar la acción para el historial
    $accion = 'Actualización de ticket';
    if (!empty($fecha_visita)) {
        if (empty($fecha_visita_anterior)) {
            $accion = 'Visita programada';
        } elseif ($fecha_visita_anterior !== date('Y-m-d H:i:s', strtotime($fecha_visita))) {
            $accion = 'Visita reprogramada';
        }
    }

    // Insertar en historial
    $sql_hist = "INSERT INTO ticket_historial (ticket_id, usuario, rol, accion, estado_anterior, estado_nuevo, comentario, foto, fecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_hist = $db->prepare($sql_hist);
    $stmt_hist->execute([
        $ticket_id,
        $_SESSION['nombre_tecnico'] ?? 'técnico',
        'tecnico',
        $accion,
        $prev['estado'] ?? null,
        $estado,
        $comentario,
        $foto_ruta
    ]);

    $_SESSION['form_success'] = "El ticket fue actualizado correctamente.";
    header("Location: ver_ticket.php?ticket=" . urlencode($_POST['numero_ticket']));
    exit;
} catch (Exception $e) {
    error_log("Error al actualizar ticket: " . $e->getMessage());
    $_SESSION['form_error'] = "Error al actualizar el ticket. Intenta nuevamente.";
    header("Location: ver_ticket.php?ticket=" . urlencode($_POST['numero_ticket']));
    exit;
}
