<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../config/database.php';

// Función para limpiar datos
function limpiar_dato($dato) {
    return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
}

// Función para validar reCAPTCHA
function verificar_recaptcha($token) {
    $secret_key = 'TU_SECRET_KEY_AQUI';
    
    $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret={$secret_key}&response={$token}");
    $response_data = json_decode($response);
    
    return $response_data->success && $response_data->score >= 0.5;
}

try {
    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    // Verificar reCAPTCHA
    if (!isset($_POST['recaptcha_token']) || !verificar_recaptcha($_POST['recaptcha_token'])) {
        throw new Exception('Verificación de seguridad fallida');
    }

    // Validar y limpiar datos
    $cliente = limpiar_dato($_POST['cliente'] ?? '');
    $nombre_apellido = limpiar_dato($_POST['nombre_apellido'] ?? '');
    $telefono = limpiar_dato($_POST['telefono'] ?? '');
    $cargo = limpiar_dato($_POST['cargo'] ?? '');
    $email = limpiar_dato($_POST['email'] ?? '');
    $id_numero_equipo = limpiar_dato($_POST['id_numero_equipo'] ?? '');
    $modelo_maquina = limpiar_dato($_POST['modelo_maquina'] ?? '');
    $falla_presentada = limpiar_dato($_POST['falla_presentada'] ?? '');
    $momento_falla = limpiar_dato($_POST['momento_falla'] ?? '');
    $momento_falla_otras = limpiar_dato($_POST['momento_falla_otras'] ?? '');
    $acciones_realizadas = limpiar_dato($_POST['acciones_realizadas'] ?? '');

    // Validaciones
    if (empty($cliente) || empty($nombre_apellido) || empty($telefono) || 
        empty($cargo) || empty($falla_presentada) || empty($momento_falla)) {
        throw new Exception('Por favor complete todos los campos obligatorios');
    }

    if (strlen($falla_presentada) < 10) {
        throw new Exception('La descripción de la falla debe tener al menos 10 caracteres');
    }

    // Validar email si se proporciona
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El formato del email no es válido');
    }

    // Si seleccionó "Otras" en momento_falla, usar el texto personalizado
    if ($momento_falla === 'Otras' && !empty($momento_falla_otras)) {
        $momento_falla = 'Otras: ' . $momento_falla_otras;
    }

    // Conectar a la base de datos
    $database = new Database();
    $db = $database->getConnection();

    // Generar número de ticket único
    do {
        $numero_ticket = 'TKT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));
        
        $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE numero_ticket = ?");
        $stmt->execute([$numero_ticket]);
        $existe = $stmt->fetchColumn() > 0;
    } while ($existe);

    // Obtener IP y User Agent
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    $user_agent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

    // Preparar consulta
    $query = "INSERT INTO tickets (
        numero_ticket, 
        cliente, 
        nombre_apellido, 
        telefono, 
        cargo, 
        email,
        id_numero_equipo, 
        modelo_maquina, 
        falla_presentada, 
        momento_falla,
        acciones_realizadas, 
        ip_address, 
        user_agent,
        estado,
        created_at
    ) VALUES (
        :numero_ticket, 
        :cliente, 
        :nombre_apellido, 
        :telefono, 
        :cargo, 
        :email,
        :id_numero_equipo, 
        :modelo_maquina, 
        :falla_presentada, 
        :momento_falla,
        :acciones_realizadas, 
        :ip_address, 
        :user_agent,
        'pendiente',
        NOW()
    )";

    $stmt = $db->prepare($query);

    // Vincular parámetros
    $stmt->bindParam(':numero_ticket', $numero_ticket);
    $stmt->bindParam(':cliente', $cliente);
    $stmt->bindParam(':nombre_apellido', $nombre_apellido);
    $stmt->bindParam(':telefono', $telefono);
    $stmt->bindParam(':cargo', $cargo);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':id_numero_equipo', $id_numero_equipo);
    $stmt->bindParam(':modelo_maquina', $modelo_maquina);
    $stmt->bindParam(':falla_presentada', $falla_presentada);
    $stmt->bindParam(':momento_falla', $momento_falla);
    $stmt->bindParam(':acciones_realizadas', $acciones_realizadas);
    $stmt->bindParam(':ip_address', $ip_address);
    $stmt->bindParam(':user_agent', $user_agent);

    // Ejecutar
    if ($stmt->execute()) {
        // Enviar email de notificación
        enviar_notificacion_email($numero_ticket, $cliente, $nombre_apellido, $telefono, $falla_presentada, $email);

        echo json_encode([
            'success' => true,
            'message' => 'Ticket creado exitosamente',
            'ticket_number' => $numero_ticket
        ]);
    } else {
        throw new Exception('Error al guardar el ticket en la base de datos');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Función para enviar email (usando configuración de Laravel)
function enviar_notificacion_email($numero_ticket, $cliente, $nombre_apellido, $telefono, $falla, $email_cliente) {
    $to = 'llamados@teqmed.cl';
    $subject = "Nuevo Ticket de Soporte: {$numero_ticket}";
    
    $message = "
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #003d5c, #00bcd4); color: white; padding: 20px; text-align: center; }
            .content { background: #f9f9f9; padding: 20px; }
            .ticket-info { background: white; padding: 15px; margin: 10px 0; border-left: 4px solid #003d5c; }
            .label { font-weight: bold; color: #003d5c; }
            .footer { text-align: center; padding: 20px; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Nuevo Ticket de Soporte</h1>
                <h2>{$numero_ticket}</h2>
            </div>
            <div class='content'>
                <div class='ticket-info'>
                    <p><span class='label'>Cliente:</span> {$cliente}</p>
                    <p><span class='label'>Nombre:</span> {$nombre_apellido}</p>
                    <p><span class='label'>Teléfono:</span> {$telefono}</p>
                    " . (!empty($email_cliente) ? "<p><span class='label'>Email:</span> {$email_cliente}</p>" : "") . "
                </div>
                <div class='ticket-info'>
                    <p><span class='label'>Falla Reportada:</span></p>
                    <p>{$falla}</p>
                </div>
                <p style='text-align: center; margin-top: 20px;'>
                    <a href='https://intranet.teqmed.cl/tickets/{$numero_ticket}' 
                       style='background: #003d5c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>
                        Ver Ticket Completo
                    </a>
                </p>
            </div>
            <div class='footer'>
                <p>Este es un mensaje automático del sistema de tickets TEQMED</p>
                <p>&copy; " . date('Y') . " TEQMED SpA - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ";

    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8\r\n";
    $headers .= "From: Intranet TEQMED <info@intranet.teqmed.cl>\r\n";
    $headers .= "Reply-To: info@intranet.teqmed.cl\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    return mail($to, $subject, $message, $headers);
}
?>