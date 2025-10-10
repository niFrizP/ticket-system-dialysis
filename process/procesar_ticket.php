<?php
// Cargar bootstrap (autoload, dotenv, sentry) lo antes posible para capturar errores durante el arranque
require_once __DIR__ . '/../bootstrap.php';

// Capturar todos los errores y convertirlos en JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Buffer de salida para capturar errores
ob_start();

// Manejador de errores personalizado
set_error_handler(function ($errno, $errstr, $errfile, $errline) {
    throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
});

try {
    header('Content-Type: application/json; charset=utf-8');

    // Archivo de log
    $logFile = __DIR__ . '/debug.log';

    function logDebug($message)
    {
        global $logFile;
        file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $message . "\n", FILE_APPEND);
    }

    logDebug("=== INICIO PROCESO ===");
    
    // Cargar funciones de historial
    require_once __DIR__ . '/../includes/ticket_historial.php';

    session_start();

    // Verificar que sea POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido');
    }

    logDebug("POST recibido");

    // Función para limpiar datos
    function limpiar_dato($dato)
    {
        return htmlspecialchars(strip_tags(trim($dato)), ENT_QUOTES, 'UTF-8');
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

    logDebug("Datos recibidos - Cliente: $cliente");

    // Validaciones
    if (
        empty($cliente) || empty($nombre_apellido) || empty($telefono) ||
        empty($cargo) || empty($falla_presentada) || empty($momento_falla)
    ) {
        throw new Exception('Por favor complete todos los campos obligatorios');
    }

    if (strlen($falla_presentada) < 10) {
        throw new Exception('La descripción de la falla debe tener al menos 10 caracteres');
    }

    // Validar email si se proporciona
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('El formato del email no es válido');
    }

    logDebug("Validaciones OK");

    // Si seleccionó "Otras", usar el texto personalizado
    if ($momento_falla === 'Otras' && !empty($momento_falla_otras)) {
        $momento_falla = $momento_falla_otras;
    }

    // Conectar a la base de datos DIRECTAMENTE (sin archivo externo)
    logDebug("Intentando conectar a BD");

    $host = 'localhost';
    $db_name = 'teqmedcl_intranet';
    $username = 'teqmedcl_intranet';
    $password = 'KSzZhsYHE#xK';

    $db = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $username,
        $password,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        )
    );

    logDebug("Conexión BD exitosa");

    // Generar número de ticket único
    do {
        $numero_ticket = 'TKT-' . strtoupper(substr(md5(uniqid(mt_rand(), true)), 0, 6));

        $stmt = $db->prepare("SELECT COUNT(*) FROM tickets WHERE numero_ticket = ?");
        $stmt->execute([$numero_ticket]);
        $existe = $stmt->fetchColumn() > 0;
    } while ($existe);

    logDebug("Ticket generado: $numero_ticket");

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
        logDebug("Ticket insertado en BD");
        
        // Obtener el ID del ticket recién insertado
        $ticket_id = $db->lastInsertId();
        
        // Registrar en el historial la creación del ticket
        try {
            registrar_historial(
                $db,
                $ticket_id,
                $nombre_apellido . ' (' . $email . ')',
                'cliente',
                'Ticket creado',
                [
                    'estado_nuevo' => 'pendiente',
                    'comentario' => 'Ticket creado por el cliente. Falla: ' . substr($falla_presentada, 0, 100)
                ]
            );
            logDebug("Historial de creación registrado");
        } catch (Exception $e) {
            logDebug("Error al registrar historial: " . $e->getMessage());
            // No interrumpimos el flujo si falla el historial
        }

        // Intentar enviar emails
        try {
            $ticketUrl = 'https://llamados.teqmed.cl/' . urlencode($numero_ticket);
            $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($ticketUrl);

            // ========================================
            // EMAIL PARA EL EQUIPO DE SOPORTE
            // ========================================

            $to_soporte = 'llamados@teqmed.cl';
            $subject_soporte = "🔧 Nuevo Ticket: {$numero_ticket} - {$cliente}";

            $message_soporte = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body { 
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                line-height: 1.6; 
                color: #333; 
                margin: 0;
                padding: 0;
                background-color: #f5f5f5;
            }
            .email-wrapper {
                max-width: 600px;
                margin: 20px auto;
                background-color: #ffffff;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            }
            .header {
                background: linear-gradient(135deg, #003d5c 0%, #00516e 50%, #00bcd4 100%);
                color: white;
                padding: 30px 20px;
                text-align: center;
            }
            .header h1 {
                margin: 0 0 10px 0;
                font-size: 24px;
                font-weight: 600;
            }
            .ticket-number {
                background: rgba(255, 255, 255, 0.2);
                backdrop-filter: blur(10px);
                padding: 12px 24px;
                border-radius: 8px;
                display: inline-block;
                font-size: 28px;
                font-weight: bold;
                letter-spacing: 2px;
                margin-top: 10px;
            }
            .content {
                padding: 30px 20px;
            }
            .section {
                background: #f9fafb;
                border-left: 4px solid #003d5c;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 0 8px 8px 0;
            }
            .section-title {
                color: #003d5c;
                font-size: 16px;
                font-weight: 700;
                margin: 0 0 15px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .info-row {
                display: flex;
                padding: 8px 0;
                border-bottom: 1px solid #e5e7eb;
            }
            .info-row:last-child {
                border-bottom: none;
            }
            .label {
                font-weight: 600;
                color: #003d5c;
                min-width: 140px;
                flex-shrink: 0;
            }
            .value {
                color: #374151;
                flex: 1;
            }
            .falla-box {
                background: #fff;
                border: 2px solid #e5e7eb;
                border-radius: 8px;
                padding: 15px;
                margin-top: 10px;
                white-space: pre-wrap;
                word-wrap: break-word;
            }
            .priority-badge {
                display: inline-block;
                padding: 6px 12px;
                background: #ef4444;
                color: white;
                border-radius: 6px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }
            .footer {
                background: #f9fafb;
                text-align: center;
                padding: 20px;
                color: #6b7280;
                font-size: 12px;
                border-top: 1px solid #e5e7eb;
            }
            .footer p {
                margin: 5px 0;
            }
            .action-button {
                display: inline-block;
                margin: 20px 0;
                padding: 12px 30px;
                background: #003d5c;
                color: white !important;
                text-decoration: none;
                border-radius: 8px;
                font-weight: 600;
                transition: background 0.3s;
            }
            .action-button:hover {
                background: #00516e;
            }
            .timestamp {
                background: #fef3c7;
                border: 1px solid #fbbf24;
                padding: 10px;
                border-radius: 6px;
                margin: 15px 0;
                text-align: center;
                color: #92400e;
                font-weight: 600;
            }
        </style>
    </head>
    <body>
        <div class="email-wrapper">
            <div class="header">
                <h1>🔧 Nuevo Ticket de Soporte</h1>
                <div class="ticket-number">' . htmlspecialchars($numero_ticket) . '</div>
            </div>

            <div class="content">
                <div class="timestamp">
                    📅 Recibido el ' . date('d/m/Y') . ' a las ' . date('H:i:s') . ' hrs
                </div>

                <!-- Datos del Cliente -->
                <div class="section">
                    <h2 class="section-title">👤 Datos del Cliente</h2>
                    <div class="info-row">
                        <span class="label">Cliente:</span>
                        <span class="value">' . htmlspecialchars($cliente) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Nombre:</span>
                        <span class="value">' . htmlspecialchars($nombre_apellido) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Cargo:</span>
                        <span class="value">' . htmlspecialchars($cargo) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="label">Teléfono:</span>
                        <span class="value"><a href="tel:' . htmlspecialchars($telefono) . '" style="color: #003d5c; text-decoration: none; font-weight: 600;">📞 ' . htmlspecialchars($telefono) . '</a></span>
                    </div>
                    ' . (!empty($email) ? '
                    <div class="info-row">
                        <span class="label">Email:</span>
                        <span class="value"><a href="mailto:' . htmlspecialchars($email) . '" style="color: #003d5c; text-decoration: none;">✉️ ' . htmlspecialchars($email) . '</a></span>
                    </div>
                    ' : '') . '
                </div>

                <!-- Datos del Equipo -->
                ' . (!empty($id_numero_equipo) || !empty($modelo_maquina) ? '
                <div class="section">
                    <h2 class="section-title">🏥 Datos del Equipo</h2>
                    ' . (!empty($id_numero_equipo) ? '
                    <div class="info-row">
                        <span class="label">ID/Número Equipo:</span>
                        <span class="value">' . htmlspecialchars($id_numero_equipo) . '</span>
                    </div>
                    ' : '') . '
                    ' . (!empty($modelo_maquina) ? '
                    <div class="info-row">
                        <span class="label">Modelo:</span>
                        <span class="value">' . htmlspecialchars($modelo_maquina) . '</span>
                    </div>
                    ' : '') . '
                </div>
                ' : '') . '

                <!-- Descripción de la Falla -->
                <div class="section">
                    <h2 class="section-title">⚠️ Descripción de la Falla</h2>
                    <div class="falla-box">
                        ' . nl2br(htmlspecialchars($falla_presentada)) . '
                    </div>
                    <div class="info-row" style="margin-top: 15px;">
                        <span class="label">Momento:</span>
                        <span class="value"><strong>' . htmlspecialchars($momento_falla) . '</strong></span>
                    </div>
                </div>

                <!-- Acciones Realizadas -->
                ' . (!empty($acciones_realizadas) ? '
                <div class="section">
                    <h2 class="section-title">✅ Acciones Realizadas</h2>
                    <div class="falla-box">
                        ' . nl2br(htmlspecialchars($acciones_realizadas)) . '
                    </div>
                </div>
                ' : '') . '

                <!-- Estado -->
                <div class="section">
                    <h2 class="section-title">ℹ️ Estado del Ticket</h2>
                    <div class="info-row">
                        <span class="label">Estado:</span>
                        <span class="value"><span class="priority-badge">Pendiente</span></span>
                    </div>
                </div>

                <!-- Botón de acción -->
                <div style="text-align: center;">
                    <a href="' . $ticketUrl . '" class="action-button">
                        Ver Ticket Completo →
                    </a>
                </div>
                
                <!-- QR Code -->
                <div style="text-align: center; margin: 20px 0; padding: 20px; background: linear-gradient(135deg, #003d5c, #00bcd4); border-radius: 12px;">
                    <p style="color: white; font-size: 14px; margin-bottom: 10px; font-weight: 600;">📱 Escanea para ver el ticket</p>
                    <div style="background: white; display: inline-block; padding: 15px; border-radius: 8px;">
                        <img src="' . $qrCodeUrl . '" 
                             alt="QR Code" 
                             style="display: block; width: 180px; height: 180px;">
                    </div>
                </div>
            </div>

            <div class="footer">
                <p><strong>Sistema de Tickets TEQMED SpA</strong></p>
                <p>Este es un mensaje automático. Por favor no responder a este correo.</p>
                <p>Para más información, contacte a <a href="mailto:soporte@teqmed.cl" style="color: #003d5c;">soporte@teqmed.cl</a></p>
                <p style="margin-top: 15px; color: #9ca3af;">© ' . date('Y') . ' TEQMED SpA - Todos los derechos reservados</p>
            </div>
        </div>
    </body>
    </html>
    ';

            $headers_soporte = "MIME-Version: 1.0\r\n";
            $headers_soporte .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers_soporte .= "From: Sistema de Tickets TEQMED <noreply@teqmed.cl>\r\n";
            $headers_soporte .= "Reply-To: " . (!empty($email) ? $email : "soporte@teqmed.cl") . "\r\n";
            $headers_soporte .= "X-Mailer: PHP/" . phpversion() . "\r\n";
            $headers_soporte .= "X-Priority: 1\r\n";

            if (mail($to_soporte, $subject_soporte, $message_soporte, $headers_soporte)) {
                logDebug("Email enviado exitosamente al equipo de soporte");
            } else {
                logDebug("Error al enviar email al equipo de soporte");
            }

            // ========================================
            // EMAIL PARA EL CLIENTE (si proporcionó email)
            // ========================================

            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $to_cliente = $email;
                $subject_cliente = "✅ Confirmación de Ticket {$numero_ticket} - TEQMED";

                $message_cliente = '
        <!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <style>
                body { 
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                    line-height: 1.6; 
                    color: #333; 
                    margin: 0;
                    padding: 0;
                    background-color: #f5f5f5;
                }
                .email-wrapper {
                    max-width: 600px;
                    margin: 20px auto;
                    background-color: #ffffff;
                    border-radius: 12px;
                    overflow: hidden;
                    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
                }
                .header {
                    background: linear-gradient(135deg, #003d5c 0%, #00516e 50%, #00bcd4 100%);
                    color: white;
                    padding: 40px 20px;
                    text-align: center;
                }
                .header h1 {
                    margin: 0 0 10px 0;
                    font-size: 28px;
                    font-weight: 600;
                }
                .ticket-number {
                    background: rgba(255, 255, 255, 0.2);
                    backdrop-filter: blur(10px);
                    padding: 15px 30px;
                    border-radius: 10px;
                    display: inline-block;
                    font-size: 32px;
                    font-weight: bold;
                    letter-spacing: 3px;
                    margin-top: 15px;
                }
                .content {
                    padding: 30px 20px;
                }
                .success-message {
                    background: #d1fae5;
                    border: 2px solid #10b981;
                    border-radius: 8px;
                    padding: 20px;
                    text-align: center;
                    margin-bottom: 25px;
                }
                .success-message h2 {
                    color: #065f46;
                    margin: 0 0 10px 0;
                    font-size: 20px;
                }
                .success-message p {
                    color: #047857;
                    margin: 0;
                    font-size: 14px;
                }
                .info-box {
                    background: #f9fafb;
                    border-left: 4px solid #00bcd4;
                    padding: 20px;
                    margin: 20px 0;
                    border-radius: 0 8px 8px 0;
                }
                .info-row {
                    display: flex;
                    padding: 8px 0;
                    border-bottom: 1px solid #e5e7eb;
                }
                .info-row:last-child {
                    border-bottom: none;
                }
                .label {
                    font-weight: 600;
                    color: #003d5c;
                    min-width: 120px;
                }
                .value {
                    color: #374151;
                    flex: 1;
                }
                .falla-box {
                    background: #fff3cd;
                    border: 2px solid #ffc107;
                    border-radius: 8px;
                    padding: 15px;
                    margin: 15px 0;
                    white-space: pre-wrap;
                    word-wrap: break-word;
                }
                .action-button {
                    display: inline-block;
                    margin: 25px 0;
                    padding: 15px 40px;
                    background: #003d5c;
                    color: white !important;
                    text-decoration: none;
                    border-radius: 10px;
                    font-weight: 700;
                    font-size: 16px;
                    transition: background 0.3s;
                }
                .action-button:hover {
                    background: #00516e;
                }
                .footer {
                    background: #f9fafb;
                    text-align: center;
                    padding: 25px;
                    color: #6b7280;
                    font-size: 12px;
                    border-top: 1px solid #e5e7eb;
                }
                .contact-info {
                    background: #003d5c;
                    color: white;
                    padding: 20px;
                    border-radius: 8px;
                    margin: 20px 0;
                    text-align: center;
                }
                .contact-info h3 {
                    margin: 0 0 15px 0;
                    font-size: 18px;
                }
                .contact-info a {
                    color: #00bcd4;
                    text-decoration: none;
                    font-weight: 600;
                }
            </style>
        </head>
        <body>
            <div class="email-wrapper">
                <div class="header">
                    <h1>¡Ticket Recibido Exitosamente!</h1>
                    <div class="ticket-number">' . htmlspecialchars($numero_ticket) . '</div>
                </div>

                <div class="content">
                    <div class="success-message">
                        <h2>✅ Hemos recibido su solicitud</h2>
                        <p>Su ticket ha sido registrado y será atendido a la brevedad posible</p>
                    </div>

                    <p style="font-size: 16px; color: #374151;">
                        Estimado/a <strong>' . htmlspecialchars($nombre_apellido) . '</strong>,
                    </p>
                    
                    <p style="color: #6b7280;">
                        Gracias por contactarnos. Hemos recibido su reporte de falla y nuestro equipo técnico 
                        lo revisará en breve. A continuación encontrará un resumen de su solicitud:
                    </p>

                    <!-- Resumen del Ticket -->
                    <div class="info-box">
                        <div class="info-row">
                            <span class="label">Cliente:</span>
                            <span class="value">' . htmlspecialchars($cliente) . '</span>
                        </div>
                        <div class="info-row">
                            <span class="label">Teléfono:</span>
                            <span class="value">' . htmlspecialchars($telefono) . '</span>
                        </div>
                        ' . (!empty($id_numero_equipo) ? '
                        <div class="info-row">
                            <span class="label">ID Equipo:</span>
                            <span class="value">' . htmlspecialchars($id_numero_equipo) . '</span>
                        </div>
                        ' : '') . '
                        ' . (!empty($modelo_maquina) ? '
                        <div class="info-row">
                            <span class="label">Modelo:</span>
                            <span class="value">' . htmlspecialchars($modelo_maquina) . '</span>
                        </div>
                        ' : '') . '
                        <div class="info-row">
                            <span class="label">Fecha:</span>
                            <span class="value">' . date('d/m/Y H:i') . ' hrs</span>
                        </div>
                    </div>

                    <!-- Falla Reportada -->
                    <h3 style="color: #003d5c; margin-top: 25px;">Falla Reportada:</h3>
                    <div class="falla-box">
                        ' . nl2br(htmlspecialchars($falla_presentada)) . '
                    </div>
                    <p style="color: #6b7280; font-size: 14px;">
                        <strong>Momento:</strong> ' . htmlspecialchars($momento_falla) . '
                    </p>

                    <!-- Botón para ver ticket -->
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="' . $ticketUrl . '" class="action-button">
                            🔍 Ver Estado del Ticket
                        </a>
                        <p style="color: #6b7280; font-size: 13px; margin-top: 10px;">
                            Puede consultar el estado de su ticket en cualquier momento
                        </p>
                    </div>

                    <!-- QR Code -->
                    <div style="text-align: center; margin: 25px 0; padding: 25px; background: linear-gradient(135deg, #003d5c, #00bcd4); border-radius: 12px;">
                        <p style="color: white; font-size: 16px; margin-bottom: 15px; font-weight: 700;">📱 Acceso Rápido</p>
                        <p style="color: white; font-size: 13px; margin-bottom: 15px; opacity: 0.9;">Escanea este código QR con tu celular para ver tu ticket</p>
                        <div style="background: white; display: inline-block; padding: 15px; border-radius: 8px;">
                            <img src="' . $qrCodeUrl . '" 
                                 alt="QR Code" 
                                 style="display: block; width: 180px; height: 180px;">
                        </div>
                    </div>

                    <!-- Información de contacto -->
                    <div class="contact-info">
                        <h3>¿Necesita más información?</h3>
                        <p style="margin: 10px 0; font-size: 14px; opacity: 0.9;">
                            Nuestro equipo está disponible para ayudarle
                        </p>
                        <p style="margin: 8px 0;">
                            📞 Teléfono: <a href="tel:(41) 213 7355">(41) 213 7355</a>
                        </p>
                        <p style="margin: 8px 0;">
                            ✉️ Email: <a href="mailto:llamados@teqmed.cl">llamados@teqmed.cl</a>
                        </p>
                    </div>

                    <p style="color: #6b7280; font-size: 13px; font-style: italic; margin-top: 25px; padding: 15px; background: #f0f9ff; border-radius: 6px;">
                        💡 <strong>Consejo:</strong> Guarde este correo o el número de ticket para futuras consultas.
                    </p>
                </div>

                <div class="footer">
                    <p><strong>TEQMED SpA - Sistema de Tickets</strong></p>
                    <p>Este es un mensaje automático. Por favor no responder a este correo.</p>
                    <p>Para consultas, contacte a <a href="mailto:soporte@teqmed.cl" style="color: #003d5c;">soporte@teqmed.cl</a></p>
                    <p style="margin-top: 15px; color: #9ca3af;">© ' . date('Y') . ' TEQMED SpA - Todos los derechos reservados</p>
                </div>
            </div>
        </body>
        </html>
        ';

                $headers_cliente = "MIME-Version: 1.0\r\n";
                $headers_cliente .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers_cliente .= "From: TEQMED Soporte <soporte@teqmed.cl>\r\n";
                $headers_cliente .= "Reply-To: llamados@teqmed.cl\r\n";
                $headers_cliente .= "X-Mailer: PHP/" . phpversion() . "\r\n";

                if (mail($to_cliente, $subject_cliente, $message_cliente, $headers_cliente)) {
                    logDebug("Email de confirmación enviado al cliente: $email");
                } else {
                    logDebug("Error al enviar email al cliente");
                }
            }
        } catch (Exception $e) {
            logDebug("Excepción al enviar emails: " . $e->getMessage());
        }

        ob_end_clean(); // Limpiar cualquier salida previa

        echo json_encode([
            'success' => true,
            'message' => 'Ticket creado exitosamente',
            'ticket_number' => $numero_ticket
        ]);

        logDebug("=== FIN EXITOSO ===");
    } else {
        throw new Exception('Error al guardar el ticket');
    }
} catch (PDOException $e) {
    logDebug("ERROR PDO: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error de base de datos: ' . $e->getMessage()
    ]);
} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage());
    ob_end_clean();
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (Throwable $e) {
    logDebug("ERROR FATAL: " . $e->getMessage());
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error del servidor: ' . $e->getMessage()
    ]);
}
