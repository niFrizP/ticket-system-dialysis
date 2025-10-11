<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
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
        $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $permitidas) && $_FILES['foto_comentario']['size'] <= 5 * 1024 * 1024) {
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
    $stmt = $db->prepare("SELECT estado, tecnico_asignado_id FROM tickets WHERE id = ?");
    $stmt->execute([$ticket_id]);
    $prev = $stmt->fetch(PDO::FETCH_ASSOC);

    // Actualizar solo el estado del ticket
    $sql = "UPDATE tickets SET estado = ? WHERE id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$estado, $ticket_id]);

    // Insertar en historial
    $sql_hist = "INSERT INTO ticket_historial (ticket_id, usuario, rol, accion, estado_anterior, estado_nuevo, comentario, foto, fecha)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $stmt_hist = $db->prepare($sql_hist);
    $stmt_hist->execute([
        $ticket_id,
        $_SESSION['nombre_tecnico'] ?? 'técnico',
        'tecnico',
        'Actualización de ticket',
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
