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


    // ======================================================
    // VERIFICACIÓN CLOUDFLARE TURNSTILE
    // ======================================================

    $turnstileToken = $_POST['turnstile_token'] ?? '';
    if (empty($turnstileToken)) {
        logDebug("Turnstile: token vacío o no enviado");
        throw new Exception('Error en la verificación de seguridad. Por favor recargue la página e inténtelo nuevamente.');
    }

    $turnstileSecret =
        $_ENV['TURNSTILE_SECRET_KEY']        // phpdotenv suele cargar aquí
        ?? $_SERVER['TURNSTILE_SECRET_KEY']  // a veces también aquí
        ?? getenv('TURNSTILE_SECRET_KEY');   // fallback a getenv()
    if (empty($turnstileSecret)) {
        logDebug("Turnstile: secret key no configurada");
        throw new Exception('Error de configuración de seguridad. Contacte al administrador.');
    }


    $remoteIp = $_SERVER['REMOTE_ADDR'] ?? null;

    // Llamada a la API de verificación de Turnstile usando cURL
    $postFields = [
        'secret'   => $turnstileSecret,
        'response' => $turnstileToken,
        'remoteip' => $remoteIp,
    ];

    $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($postFields),
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $verifyResponse = curl_exec($ch);

    if ($verifyResponse === false) {
        $curlError = curl_error($ch);
        logDebug("Turnstile: error CURL: " . $curlError);
        curl_close($ch);
        throw new Exception('No se pudo verificar el captcha. Por favor intente nuevamente más tarde.');
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    logDebug("Turnstile HTTP code: " . $httpCode);

    curl_close($ch);

    $turnstileResult = json_decode($verifyResponse, true);
    logDebug("Turnstile respuesta: " . $verifyResponse);

    if (empty($turnstileResult['success'])) {
        $errorCodes = isset($turnstileResult['error-codes'])
            ? json_encode($turnstileResult['error-codes'])
            : 'sin códigos';
        logDebug("Turnstile: verificación fallida. Errores: " . $errorCodes);
        throw new Exception('Verificación de seguridad fallida. Por favor recargue la página e intente nuevamente.');
    }

    logDebug("Turnstile verificado correctamente");


    // ======================================================
    // FIN VERIFICACIÓN TURNSTILE
    // ======================================================

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

        // Datos para URLs
        $ticketUrl = 'https://llamados.teqmed.cl/' . urlencode($numero_ticket);
        $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($ticketUrl);

        // Preparar datos comunes para plantillas
        $ticketData = [
            'numero_ticket' => $numero_ticket,
            'cliente' => $cliente,
            'nombre_apellido' => $nombre_apellido,
            'email' => $email,
            'telefono' => $telefono,
            'id_numero_equipo' => $id_numero_equipo,
            'modelo_maquina' => $modelo_maquina,
            'falla_presentada' => $falla_presentada,
            'momento_falla' => $momento_falla,
            'acciones_realizadas' => $acciones_realizadas,
            'estado' => 'pendiente',
            'created_at' => date('Y-m-d H:i:s'),
        ];
        $createdAt = date('d/m/Y H:i', strtotime($ticketData['created_at']));

        // Preparar logo embebido
        $logoCid = 'teqmed_logo';
        $logoPath = __DIR__ . '/../assets/images/logo.png';
        $logoUrl = ''; // opción pública si la tuvieras

        if (!empty($logoPath) && file_exists($logoPath)) {
            logDebug("Logo disponible para embedir: $logoPath");
        } else {
            logDebug("Logo no disponible para embedir");
            $logoPath = '';
        }

        // -------------------------
        // Envío al equipo de soporte usando plantilla
        // -------------------------
        try {
            $tplSupport = __DIR__ . '/../includes/nuevo_ticket_soporte.php';
            if (file_exists($tplSupport)) {
                $ticket = $ticketData;
                $ticketUrlLocal = $ticketUrl;
                $qrCodeUrlLocal = $qrCodeUrl;
                $createdAtLocal = $createdAt;
                $logoCidLocal = $logoCid;
                $logoUrlLocal = $logoUrl;
                ob_start();
                include $tplSupport;
                $htmlSupport = ob_get_clean();
            } else {
                // Fallback simple si plantilla no existe
                $htmlSupport = "<p>Nuevo ticket {$numero_ticket} - Cliente: {$cliente}</p><p>Ver: <a href=\"{$ticketUrl}\">{$ticketUrl}</a></p>";
            }

            // Enviar con PHPMailer preferido
            if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                $supportMail = new \PHPMailer\PHPMailer\PHPMailer(true);

                // SMTP config
                $smtpHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST') ?: null;
                $smtpPort = getenv('MAIL_PORT') ?: getenv('SMTP_PORT') ?: 587;
                $smtpUser = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER') ?: null;
                $smtpPass = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS') ?: null;
                $smtpSecure = getenv('MAIL_ENCRYPTION') ?: getenv('SMTP_SECURE') ?: null;

                if (!empty($smtpHost)) {
                    $supportMail->isSMTP();
                    $supportMail->Host = $smtpHost;
                    $supportMail->Port = (int)$smtpPort;
                    if (!empty($smtpUser)) {
                        $supportMail->SMTPAuth = true;
                        $supportMail->Username = $smtpUser;
                        $supportMail->Password = $smtpPass;
                    } else {
                        $supportMail->SMTPAuth = false;
                    }
                    if (!empty($smtpSecure) && in_array(strtolower($smtpSecure), ['ssl', 'tls'])) {
                        $supportMail->SMTPSecure = $smtpSecure;
                    }
                } else {
                    $supportMail->isMail();
                }

                $supportMail->CharSet = 'UTF-8';
                $fromAddr = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
                $fromName = getenv('MAIL_FROM_NAME') ?: 'Sistema de Tickets';
                $supportMail->setFrom($fromAddr, $fromName);

                $to_soporte = 'llamados@teqmed.cl';
                $subject_soporte = "🔧 Nuevo Ticket: {$numero_ticket} - {$cliente}";
                $supportMail->addAddress($to_soporte, 'Llamados TEQMED');

                if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $supportMail->addReplyTo($email, $nombre_apellido);
                }

                // Embedir logo si existe
                if (!empty($logoPath) && file_exists($logoPath)) {
                    try {
                        $supportMail->addEmbeddedImage($logoPath, $logoCid);
                    } catch (Exception $e) {
                        logDebug("No se pudo embedir logo en soporte: " . $e->getMessage());
                    }
                }

                $supportMail->isHTML(true);
                $supportMail->Subject = $subject_soporte;
                $supportMail->Body = $htmlSupport;
                $supportMail->AltBody = "Nuevo ticket {$numero_ticket} - {$cliente}\nVer: {$ticketUrl}";

                // Adjuntar archivos subidos (si hay)
                if (!empty($_FILES)) {
                    foreach ($_FILES as $fieldName => $fileInfo) {
                        if (is_array($fileInfo['name'])) {
                            for ($i = 0; $i < count($fileInfo['name']); $i++) {
                                if ($fileInfo['error'][$i] === UPLOAD_ERR_OK) {
                                    $supportMail->addAttachment($fileInfo['tmp_name'][$i], $fileInfo['name'][$i]);
                                }
                            }
                        } else {
                            if (isset($fileInfo['error']) && $fileInfo['error'] === UPLOAD_ERR_OK) {
                                $supportMail->addAttachment($fileInfo['tmp_name'], $fileInfo['name']);
                            }
                        }
                    }
                }

                $supportMail->send();
                logDebug("Email HTML enviado al equipo de soporte via PHPMailer: $to_soporte");
            } else {
                // Fallback mail()
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: Sistema de Tickets <noreply@teqmed.cl>\r\n";
                $headers .= "Reply-To: " . (!empty($email) ? $email : "soporte@teqmed.cl") . "\r\n";
                if (mail($to_soporte, $subject_soporte, $htmlSupport, $headers)) {
                    logDebug("Email soporte enviado via mail() fallback");
                } else {
                    logDebug("Error al enviar email soporte via mail()");
                }
            }
        } catch (Exception $e) {
            logDebug("Error enviando email al soporte: " . $e->getMessage());
        }

        // -------------------------
        // Envío al cliente usando plantilla
        // -------------------------
        try {
            if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $tplClient = __DIR__ . '/../includes/nuevo_ticket_cliente.php';
                if (file_exists($tplClient)) {
                    $ticket = $ticketData;
                    $createdAt = $createdAt;
                    $logoCid = $logoCid;
                    $logoUrl = $logoUrl;
                    $ticketUrlLocal = $ticketUrl;
                    ob_start();
                    include $tplClient;
                    $htmlClient = ob_get_clean();
                } else {
                    // Fallback corto
                    $htmlClient = "<p>Hola " . htmlspecialchars($nombre_apellido) . ",</p><p>Hemos recibido tu solicitud. Ticket: <strong>{$numero_ticket}</strong></p><p>Ver: <a href=\"{$ticketUrl}\">{$ticketUrl}</a></p>";
                }

                $altClient = "Hola {$nombre_apellido}, hemos recibido tu solicitud. Ticket: {$numero_ticket}. Ver: {$ticketUrl}";

                if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                    $clientMail = new \PHPMailer\PHPMailer\PHPMailer(true);

                    // SMTP config
                    $smtpHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST') ?: null;
                    $smtpPort = getenv('MAIL_PORT') ?: getenv('SMTP_PORT') ?: 587;
                    $smtpUser = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER') ?: null;
                    $smtpPass = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS') ?: null;
                    $smtpSecure = getenv('MAIL_ENCRYPTION') ?: getenv('SMTP_SECURE') ?: null;

                    if (!empty($smtpHost)) {
                        $clientMail->isSMTP();
                        $clientMail->Host = $smtpHost;
                        $clientMail->Port = (int)$smtpPort;
                        if (!empty($smtpUser)) {
                            $clientMail->SMTPAuth = true;
                            $clientMail->Username = $smtpUser;
                            $clientMail->Password = $smtpPass;
                        } else {
                            $clientMail->SMTPAuth = false;
                        }
                        if (!empty($smtpSecure) && in_array(strtolower($smtpSecure), ['ssl', 'tls'])) {
                            $clientMail->SMTPSecure = $smtpSecure;
                        }
                    } else {
                        $clientMail->isMail();
                    }

                    $clientMail->CharSet = 'UTF-8';
                    $fromAddr = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
                    $fromName = getenv('MAIL_FROM_NAME') ?: 'TEQMED';
                    $clientMail->setFrom($fromAddr, $fromName);
                    $clientMail->addAddress($email, $nombre_apellido);

                    // Embedir logo
                    if (!empty($logoPath) && file_exists($logoPath)) {
                        try {
                            $clientMail->addEmbeddedImage($logoPath, $logoCid);
                        } catch (Exception $e) {
                            logDebug("No se pudo embedir logo en mail cliente: " . $e->getMessage());
                        }
                    }

                    $clientMail->isHTML(true);
                    $clientMail->Subject = "✅ Confirmación de Ticket {$numero_ticket} - TEQMED";
                    $clientMail->Body = $htmlClient;
                    $clientMail->AltBody = $altClient;

                    $clientMail->send();
                    logDebug("Email plantilla cliente enviado via PHPMailer: " . $email);
                } else {
                    $headers_cliente = "MIME-Version: 1.0\r\n";
                    $headers_cliente .= "Content-type: text/html; charset=UTF-8\r\n";
                    $headers_cliente .= "From: TEQMED Soporte <soporte@teqmed.cl>\r\n";
                    $headers_cliente .= "Reply-To: llamados@teqmed.cl\r\n";
                    if (mail($email, "Confirmación de Ticket {$numero_ticket} - TEQMED", $htmlClient, $headers_cliente)) {
                        logDebug("Email plantilla cliente enviado via mail()");
                    } else {
                        logDebug("Error al enviar email plantilla cliente via mail()");
                    }
                }
            } else {
                logDebug("No se envió email al cliente: campo email vacío o inválido");
            }
        } catch (Exception $e) {
            logDebug("Error enviando email al cliente: " . $e->getMessage());
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
restore_error_handler();
