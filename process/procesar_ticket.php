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

        // --- ENVÍO DE EMAILS AL CLIENTE USANDO PLANTILLA CON LOGO EMBEBIDO ---
        try {
            if (!empty($email)) {
                // Construir array $ticket para plantilla
                $ticketData = [
                    'numero_ticket' => $numero_ticket,
                    'cliente' => $cliente,
                    'nombre_apellido' => $nombre_apellido,
                    'email' => $email,
                    'id_numero_equipo' => $id_numero_equipo,
                    'modelo_maquina' => $modelo_maquina,
                    'falla_presentada' => $falla_presentada,
                    'estado' => 'pendiente',
                    'fecha_visita' => null,
                    'created_at' => date('Y-m-d H:i:s'),
                ];

                // Formatear fecha para plantilla
                $createdAt = date('d/m/Y H:i', strtotime($ticketData['created_at']));

                // --- Selección simplificada del logo (ya subiste 600x684 logo.png) ---
                $logoCid = 'teqmed_logo';
                $logoPath = __DIR__ . '/../assets/images/logo.png';
                if (!file_exists($logoPath)) {
                    // fallbacks si por alguna razón no está el PNG
                    if (file_exists(__DIR__ . '/../assets/images/logo_mail.png')) {
                        $logoPath = __DIR__ . '/../assets/images/logo_mail.png';
                    } elseif (file_exists(__DIR__ . '/../assets/images/logo.svg')) {
                        $logoPath = __DIR__ . '/../assets/images/logo.svg';
                    } else {
                        $logoPath = '';
                    }
                }

                if (!empty($logoPath)) {
                    logDebug("Logo seleccionado para embedir: $logoPath (CID: $logoCid)");
                } else {
                    logDebug("No se encontró logo para embedir.");
                }

                // Renderizar plantilla a HTML
                $tpl = __DIR__ . '/../includes/nuevo_ticket_cliente.php';
                if (file_exists($tpl)) {
                    // variables que la plantilla espera
                    $ticket = $ticketData;
                    $createdAt = $createdAt;
                    // plantilla usa $logoCid y $logoUrl
                    $logoCid = $logoCid;
                    $logoUrl = ''; // no usado cuando embebido
                    ob_start();
                    include $tpl;
                    $htmlBody = ob_get_clean();
                } else {
                    // Si no existe la plantilla, construir HTML simple como fallback
                    $htmlBody = "<p>Hola " . htmlspecialchars($nombre_apellido) . ",</p>"
                        . "<p>Hemos recibido tu solicitud. Tu número de ticket es <strong>{$numero_ticket}</strong>.</p>"
                        . "<p>Resumen de la falla:</p>"
                        . "<p>" . nl2br(htmlspecialchars(substr($falla_presentada, 0, 1000))) . "</p>";
                }

                $altBody = "Hola {$nombre_apellido}, hemos recibido tu solicitud. Ticket: {$numero_ticket}. Ver: https://llamados.teqmed.cl/{$numero_ticket}";

                // Enviar con PHPMailer si está disponible
                if (class_exists('\PHPMailer\PHPMailer\PHPMailer')) {
                    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

                    // Configurar SMTP desde variables MAIL_* o SMTP_*
                    $smtpHost = getenv('MAIL_HOST') ?: getenv('SMTP_HOST') ?: ($_ENV['MAIL_HOST'] ?? null);
                    $smtpPort = getenv('MAIL_PORT') ?: getenv('SMTP_PORT') ?: ($_ENV['MAIL_PORT'] ?? 587);
                    $smtpUser = getenv('MAIL_USERNAME') ?: getenv('SMTP_USER') ?: ($_ENV['MAIL_USERNAME'] ?? null);
                    $smtpPass = getenv('MAIL_PASSWORD') ?: getenv('SMTP_PASS') ?: ($_ENV['MAIL_PASSWORD'] ?? null);
                    $smtpSecure = getenv('MAIL_ENCRYPTION') ?: getenv('SMTP_SECURE') ?: ($_ENV['MAIL_ENCRYPTION'] ?? 'tls');

                    if (!empty($smtpHost)) {
                        $mail->isSMTP();
                        $mail->Host = $smtpHost;
                        $mail->Port = (int)$smtpPort;
                        if (!empty($smtpUser)) {
                            $mail->SMTPAuth = true;
                            $mail->Username = $smtpUser;
                            $mail->Password = $smtpPass;
                        } else {
                            $mail->SMTPAuth = false;
                        }
                        if (!empty($smtpSecure) && in_array(strtolower($smtpSecure), ['ssl', 'tls'])) {
                            $mail->SMTPSecure = $smtpSecure;
                        }
                    } else {
                        $mail->isMail();
                    }

                    $mail->CharSet = 'UTF-8';
                    $fromAddr = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
                    $fromName = getenv('MAIL_FROM_NAME') ?: getenv('MAIL_FROM_NAME') ?: 'TEQMED';
                    $mail->setFrom($fromAddr, $fromName);
                    $mail->addAddress($ticketData['email'], $ticketData['nombre_apellido'] ?: $ticketData['cliente']);

                    // Embedir logo si está disponible
                    if (!empty($logoPath) && file_exists($logoPath)) {
                        try {
                            $mail->addEmbeddedImage($logoPath, $logoCid);
                            logDebug("Logo embebido agregado: $logoPath as $logoCid");
                        } catch (Exception $e) {
                            logDebug("No se pudo embedir logo: " . $e->getMessage());
                        }
                    } else {
                        logDebug("Logo no encontrado para embedir: $logoPath");
                    }

                    $mail->isHTML(true);
                    $mail->Subject = "Confirmación de ticket {$ticketData['numero_ticket']} - TEQMED";
                    $mail->Body = $htmlBody;
                    $mail->AltBody = $altBody;

                    $mail->send();
                    logDebug("Email plantilla cliente enviado via PHPMailer: " . $ticketData['email']);
                } else {
                    // Fallback a mail()
                    $fromAddr = getenv('MAIL_FROM_ADDRESS') ?: getenv('MAIL_FROM') ?: 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'example.com');
                    $fromName = getenv('MAIL_FROM_NAME') ?: 'TEQMED';
                    $headers = "From: {$fromName} <{$fromAddr}>\r\n";
                    $headers .= "MIME-Version: 1.0\r\n";
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $mailResult = mail($ticketData['email'], "Confirmación de ticket {$ticketData['numero_ticket']} - TEQMED", $htmlBody, $headers);
                    if ($mailResult) {
                        logDebug("Email plantilla cliente enviado via mail(): " . $ticketData['email']);
                    } else {
                        logDebug("Falló mail() al enviar plantilla cliente a: " . $ticketData['email']);
                    }
                }
            } else {
                logDebug("No se envió email al cliente: campo email vacío");
            }
        } catch (Exception $e) {
            logDebug("Error enviando email al cliente: " . $e->getMessage());
            // No interrumpimos el flujo principal
        }
        // --- FIN ENVÍO EMAILS ---

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
