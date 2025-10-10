<?php

/**
 * Vista de Ticket - Sistema TEQMED
 * Muestra información detallada de un ticket
 */

// Cargar bootstrap (autoload, dotenv, sentry) lo antes posible
require_once __DIR__ . '/bootstrap.php';

// Configuración de seguridad
ini_set('display_errors', 0); // Ocultar errores en producción
error_reporting(E_ALL);

// Iniciar sesión segura
session_start();

// Lógica de verificación
$ticket_key = 'ticket_verified_' . $ticket['numero_ticket'];
$is_verified = isset($_SESSION[$ticket_key]) ? $_SESSION[$ticket_key] : false;
$user_role = isset($_SESSION[$ticket_key . '_role']) ? $_SESSION[$ticket_key . '_role'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verificacion'])) {
    $input = strtolower(trim($_POST['verificacion']));
    $nombre = strtolower($ticket['nombre_apellido']);
    $email = strtolower($ticket['email']);
    $tecnico = isset($ticket['tecnico_nombre']) ? strtolower($ticket['tecnico_nombre']) : '';
    $tecnico_email = isset($ticket['tecnico_email']) ? strtolower($ticket['tecnico_email']) : '';

    if ($input === $nombre || $input === $email) {
        $_SESSION[$ticket_key] = true;
        $_SESSION[$ticket_key . '_role'] = 'cliente';
        $is_verified = true;
        $user_role = 'cliente';
    } elseif ($input === $tecnico || ($tecnico_email && $input === $tecnico_email)) {
        $_SESSION[$ticket_key] = true;
        $_SESSION[$ticket_key . '_role'] = 'tecnico';
        $is_verified = true;
        $user_role = 'tecnico';
    } else {
        $verif_error = "Dato de verificación incorrecto. Intenta con el correo o nombre registrado.";
    }
}

// Headers de seguridad
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: SAMEORIGIN");
header("X-XSS-Protection: 1; mode=block");
header("Referrer-Policy: strict-origin-when-cross-origin");

// Función de sanitización
function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Obtener y validar número de ticket
$numero_ticket = '';
$ticket = null;
$error = null;
$historial = []; // Inicializar array de historial

if (isset($_GET['ticket'])) {
    $numero_ticket = sanitize($_GET['ticket']);

    // Validar formato del ticket (TKT-XXXXXX)
    if (!preg_match('/^TKT-[A-Z0-9]{6}$/i', $numero_ticket)) {
        $error = "Formato de ticket inválido";
        $numero_ticket = '';
    }
} else {
    $error = "Número de ticket no especificado";
}

// Si el ticket es válido, buscar en BD
if ($numero_ticket && !$error) {
    try {
        require_once 'config/database.php';

        $db = Database::getInstance()->getConnection();

        // Preparar consulta con JOIN para obtener el nombre del técnico
        $stmt = $db->prepare("
            SELECT 
                t.id,
                t.numero_ticket,
                t.cliente,
                t.nombre_apellido,
                t.telefono,
                t.cargo,
                t.email,
                t.id_numero_equipo,
                t.modelo_maquina,
                t.falla_presentada,
                t.momento_falla,
                t.momento_falla_otras,
                t.acciones_realizadas,
                t.estado,
                t.tecnico_asignado_id,
                t.fecha_visita,
                t.llamado_id,
                t.created_at,
                t.updated_at,
                u.name as tecnico_nombre
            FROM tickets t
            LEFT JOIN users u ON t.tecnico_asignado_id = u.id
            WHERE t.numero_ticket = :ticket 
            AND t.deleted_at IS NULL
            LIMIT 1
        ");

        $stmt->bindParam(':ticket', $numero_ticket, PDO::PARAM_STR);
        $stmt->execute();

        $ticket = $stmt->fetch();

        if (!$ticket) {
            $error = "Ticket no encontrado";
        } else {
            // Cargar el historial del ticket
            require_once __DIR__ . '/includes/ticket_historial.php';
            $historial = obtener_historial($db, $ticket['id']);
        }

        // Limpiar statement
        $stmt = null;
    } catch (PDOException $e) {
        // Log del error (no mostrar al usuario)
        error_log("Error en ver_ticket.php: " . $e->getMessage());
        $error = "Error al consultar la base de datos. Por favor, intente más tarde.";
    }
}

// Configuración de estados
$estados = [
    'pendiente' => [
        'color' => '#f59e0b',
        'texto' => 'Pendiente',
        'icono' => '⏳',
        'descripcion' => 'Su ticket está en espera de revisión',
        'color_timeline' => '#10b981' // Verde
    ],
    'en_proceso' => [
        'color' => '#3b82f6',
        'texto' => 'En Proceso',
        'icono' => '🔧',
        'descripcion' => 'Nuestro equipo está trabajando en su solicitud',
        'color_timeline' => '#3b82f6' // Azul
    ],
    'completado' => [
        'color' => '#10b981',
        'texto' => 'Completado',
        'icono' => '✅',
        'descripcion' => 'Su ticket ha sido resuelto exitosamente',
        'color_timeline' => '#ef4444' // Rojo
    ]
];

// Función para escapar salida (prevenir XSS)
function e($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title><?php echo $ticket ? 'Ticket ' . e($ticket['numero_ticket']) : 'Ticket'; ?> - TEQMED</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="assets/images/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="assets/images/apple-touch-icon.png">

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Inter', sans-serif;
        }

        .status-badge {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: .8;
            }
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 32px;
            height: calc(100% - 32px);
            width: 2px;
            background: #e5e7eb;
        }

        .timeline-item:last-child::before {
            display: none;
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'teqmed-blue': '#003d5c',
                        'teqmed-cyan': '#00bcd4',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50">

    <!-- Modal de verificación -->
    <div
        x-data="{ open: <?php echo $is_verified ? 'false' : 'true'; ?> }"
        x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50"
        style="display: <?php echo $is_verified ? 'none' : 'flex'; ?>;">
        <div class="bg-white dark:bg-zinc-900 rounded-lg p-8 max-w-sm w-full shadow-lg">
            <h2 class="text-xl font-bold mb-4 text-[#003d5c] dark:text-cyan-400">Identifíquese para más información</h2>
            <p class="mb-4 text-zinc-600 dark:text-zinc-300">Ingrese su <b>correo</b> o <b>nombre</b> registrado<br>para acceder a la información del ticket.</p>
            <?php if (isset($verif_error)): ?>
                <div class="bg-red-100 text-red-700 rounded p-2 mb-3"><?php echo htmlspecialchars($verif_error); ?></div>
            <?php endif; ?>
            <form method="POST" autocomplete="off">
                <input type="text" name="verificacion" required autofocus
                    class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-700 rounded mb-2 bg-zinc-50 dark:bg-zinc-800 text-zinc-900 dark:text-zinc-100"
                    placeholder="Correo o nombre completo">
                <button type="submit"
                    class="w-full bg-[#003d5c] dark:bg-cyan-500 text-white font-semibold px-5 py-2 rounded hover:bg-cyan-500 dark:hover:bg-[#003d5c] transition mt-2">
                    Verificar
                </button>
            </form>
        </div>
    </div>

    <!-- Header -->
    <div class="bg-gradient-to-r from-teqmed-blue to-teqmed-cyan text-white py-6 no-print">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-4">
                    <img src="assets/images/logo.svg" alt="TEQMED Logo" class="h-12 w-12 bg-white rounded-full p-2">
                    <div>
                        <h1 class="text-2xl font-bold">Sistema de Tickets</h1>
                        <p class="text-sm opacity-90">TEQMED SpA</p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <button onclick="window.print()" class="bg-white text-teqmed-blue px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                        🖨️ Imprimir
                    </button>
                    <a href="index.html" class="bg-white text-teqmed-blue px-4 py-2 rounded-lg font-medium hover:bg-gray-100 transition">
                        ➕ Nuevo Ticket
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-8 max-w-5xl">
        <?php if ($error): ?>
            <!-- Error -->
            <div class="bg-red-50 border-l-4 border-red-500 p-6 rounded-lg shadow-sm">
                <div class="flex items-center">
                    <svg class="w-8 h-8 text-red-500 mr-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-semibold text-red-800">Error</h3>
                        <p class="text-red-600"><?php echo e($error); ?></p>
                        <?php if ($numero_ticket): ?>
                            <p class="text-sm text-red-500 mt-1">Ticket: "<?php echo e($numero_ticket); ?>"</p>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="index.html" class="mt-4 inline-block bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    ← Volver al inicio
                </a>
            </div>
        <?php else: ?>
            <!-- Ticket encontrado -->
            <div class="grid lg:grid-cols-3 gap-6">

                <!-- Columna principal -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- Header del ticket -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <div class="flex items-start justify-between mb-4 flex-wrap gap-4">
                            <div>
                                <h2 class="text-3xl font-bold text-gray-800"><?php echo e($ticket['numero_ticket']); ?></h2>
                                <p class="text-gray-500 mt-1">
                                    Creado el <?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?>
                                    a las <?php echo date('H:i', strtotime($ticket['created_at'])); ?> hrs
                                </p>
                                <?php if (!empty($ticket['llamado_id'])): ?>
                                    <p class="text-sm text-teqmed-blue mt-1 font-medium">
                                        📋 Llamado #<?php echo str_pad($ticket['llamado_id'], 6, '0', STR_PAD_LEFT); ?>
                                    </p>
                                <?php endif; ?>
                            </div>
                            <div>
                                <span class="status-badge px-4 py-2 rounded-full text-white font-semibold flex items-center space-x-2 shadow-md"
                                    style="background-color: <?php echo $estados[$ticket['estado']]['color']; ?>">
                                    <span><?php echo $estados[$ticket['estado']]['icono']; ?></span>
                                    <span><?php echo $estados[$ticket['estado']]['texto']; ?></span>
                                </span>
                                <p class="text-xs text-gray-500 mt-2 text-center">
                                    <?php echo $estados[$ticket['estado']]['descripcion']; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Información del cliente -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-teqmed-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Información del Cliente
                        </h3>
                        <div class="grid md:grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-500">Cliente</p>
                                <p class="font-semibold text-gray-800"><?php echo e($ticket['cliente']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Nombre</p>
                                <p class="font-semibold text-gray-800"><?php echo e($ticket['nombre_apellido']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Cargo</p>
                                <p class="font-semibold text-gray-800"><?php echo e($ticket['cargo']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-500">Teléfono</p>
                                <p class="font-semibold text-gray-800">
                                    <a href="tel:<?php echo e($ticket['telefono']); ?>" class="text-teqmed-blue hover:underline">
                                        <?php echo e($ticket['telefono']); ?>
                                    </a>
                                </p>
                            </div>
                            <?php if (!empty($ticket['email'])): ?>
                                <div class="md:col-span-2">
                                    <p class="text-sm text-gray-500">Email</p>
                                    <p class="font-semibold text-gray-800">
                                        <a href="mailto:<?php echo e($ticket['email']); ?>" class="text-teqmed-blue hover:underline">
                                            <?php echo e($ticket['email']); ?>
                                        </a>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Asignación y Programación (NUEVA SECCIÓN) -->
                    <?php if (!empty($ticket['tecnico_asignado_id']) || !empty($ticket['fecha_visita'])): ?>
                        <div class="bg-gradient-to-r from-blue-50 to-cyan-50 rounded-lg shadow-sm p-6 border-l-4 border-teqmed-blue">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-teqmed-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Asignación y Programación
                            </h3>
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php if (!empty($ticket['tecnico_asignado_id']) && !empty($ticket['tecnico_nombre'])): ?>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <p class="text-sm text-gray-500 mb-1">Técnico Asignado</p>
                                        <p class="font-semibold text-gray-800 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-teqmed-cyan" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                            </svg>
                                            <?php echo e($ticket['tecnico_nombre']); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <?php if (!empty($ticket['fecha_visita'])): ?>
                                    <div class="bg-white rounded-lg p-4 shadow-sm">
                                        <p class="text-sm text-gray-500 mb-1">Fecha de Visita Programada</p>
                                        <p class="font-semibold text-gray-800 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                            </svg>
                                            <?php
                                            $fecha_visita = new DateTime($ticket['fecha_visita']);
                                            echo $fecha_visita->format('d/m/Y H:i');
                                            ?> hrs
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <?php if ($ticket['estado'] == 'en_proceso'): ?>
                                <div class="mt-4 bg-blue-100 border border-blue-300 rounded-lg p-3">
                                    <p class="text-sm text-blue-800 flex items-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Nuestro equipo técnico está trabajando en su solicitud
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <!-- Detalles del equipo -->
                    <?php if (!empty($ticket['id_numero_equipo']) || !empty($ticket['modelo_maquina'])): ?>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-teqmed-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                                Información del Equipo
                            </h3>
                            <div class="grid md:grid-cols-2 gap-4">
                                <?php if (!empty($ticket['id_numero_equipo'])): ?>
                                    <div>
                                        <p class="text-sm text-gray-500">ID / Número de Equipo</p>
                                        <p class="font-semibold text-gray-800"><?php echo e($ticket['id_numero_equipo']); ?></p>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($ticket['modelo_maquina'])): ?>
                                    <div>
                                        <p class="text-sm text-gray-500">Modelo de Máquina</p>
                                        <p class="font-semibold text-gray-800"><?php echo e($ticket['modelo_maquina']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Descripción de la falla -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                            </svg>
                            Descripción de la Falla
                        </h3>
                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                            <p class="text-gray-800 whitespace-pre-wrap break-words"><?php echo e($ticket['falla_presentada']); ?></p>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-200">
                            <p class="text-sm text-gray-500">Momento en que se presentó</p>
                            <p class="font-semibold text-gray-800 mt-1">⏰ <?php echo e($ticket['momento_falla']); ?></p>
                        </div>
                    </div>

                    <!-- Acciones realizadas -->
                    <?php if (!empty($ticket['acciones_realizadas'])): ?>
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                                <svg class="w-6 h-6 mr-2 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Acciones Realizadas
                            </h3>
                            <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                                <p class="text-gray-800 whitespace-pre-wrap break-words"><?php echo e($ticket['acciones_realizadas']); ?></p>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>

                <!-- Sidebar -->
                <div class="space-y-6">

                    <!-- Timeline del ticket (ACTUALIZADO CON COLORES Y ROJO EN COMPLETADO) -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">📅 Historial del Ticket</h3>
                        <div class="space-y-4">
                            <?php if (!empty($historial)): ?>
                                <?php foreach ($historial as $entry): ?>
                                    <div class="timeline-item relative pl-8">
                                        <?php
                                        // Determinar el color del punto según la acción
                                        $color = '#6b7280'; // Gris por defecto
                                        $icono = '📝';
                                        
                                        if (stripos($entry['accion'], 'creado') !== false) {
                                            $color = '#10b981'; // Verde
                                            $icono = '🎫';
                                        } elseif (stripos($entry['accion'], 'estado') !== false) {
                                            if ($entry['estado_nuevo'] == 'completado') {
                                                $color = '#ef4444'; // Rojo
                                                $icono = '✅';
                                            } elseif ($entry['estado_nuevo'] == 'en_proceso') {
                                                $color = '#3b82f6'; // Azul
                                                $icono = '🔧';
                                            } else {
                                                $color = '#f59e0b'; // Naranja
                                                $icono = '⏳';
                                            }
                                        } elseif (stripos($entry['accion'], 'técnico') !== false || stripos($entry['accion'], 'tecnico') !== false) {
                                            $color = '#8b5cf6'; // Púrpura
                                            $icono = '👤';
                                        } elseif (stripos($entry['accion'], 'comentario') !== false) {
                                            $color = '#06b6d4'; // Cyan
                                            $icono = '💬';
                                        } elseif (stripos($entry['accion'], 'foto') !== false || !empty($entry['foto'])) {
                                            $color = '#ec4899'; // Rosa
                                            $icono = '📷';
                                        }
                                        ?>
                                        <div class="absolute left-0 top-0 w-4 h-4 rounded-full shadow"
                                            style="background-color: <?php echo $color; ?>"></div>
                                        
                                        <div class="mb-1">
                                            <p class="text-sm font-semibold text-gray-800">
                                                <?php echo $icono; ?> <?php echo e($entry['accion']); ?>
                                            </p>
                                            
                                            <?php if (!empty($entry['estado_anterior']) && !empty($entry['estado_nuevo'])): ?>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    <span class="font-medium"><?php echo e($entry['estado_anterior']); ?></span>
                                                    →
                                                    <span class="font-medium"><?php echo e($entry['estado_nuevo']); ?></span>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($entry['tecnico_anterior']) && !empty($entry['tecnico_nuevo'])): ?>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    <span class="font-medium"><?php echo e($entry['tecnico_anterior']); ?></span>
                                                    →
                                                    <span class="font-medium"><?php echo e($entry['tecnico_nuevo']); ?></span>
                                                </p>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($entry['comentario'])): ?>
                                                <div class="mt-2 p-2 bg-gray-50 rounded text-xs text-gray-700 border-l-2 border-gray-300">
                                                    <?php echo nl2br(e($entry['comentario'])); ?>
                                                </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($entry['foto'])): ?>
                                                <div class="mt-2">
                                                    <img src="<?php echo e($entry['foto']); ?>" 
                                                         alt="Foto adjunta" 
                                                         class="w-20 h-20 object-cover rounded border border-gray-300 cursor-pointer hover:opacity-80"
                                                         onclick="window.open(this.src, '_blank')">
                                                </div>
                                            <?php endif; ?>
                                            
                                            <p class="text-xs text-gray-500 mt-1">
                                                <?php echo date('d/m/Y H:i', strtotime($entry['fecha'])); ?>
                                                <?php if (!empty($entry['usuario'])): ?>
                                                    - <?php echo e($entry['usuario']); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Si no hay historial en BD, mostrar timeline básico -->
                                <!-- Ticket Creado -->
                                <div class="timeline-item relative pl-8">
                                    <div class="absolute left-0 top-0 w-4 h-4 rounded-full shadow"
                                        style="background-color: <?php echo $estados['pendiente']['color_timeline']; ?>"></div>
                                    <p class="text-sm font-semibold text-gray-800">Ticket Creado</p>
                                    <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($ticket['created_at'])); ?></p>
                                </div>

                                <!-- Técnico Asignado -->
                                <?php if (!empty($ticket['tecnico_asignado_id'])): ?>
                                    <div class="timeline-item relative pl-8">
                                        <div class="absolute left-0 top-0 w-4 h-4 bg-purple-500 rounded-full shadow"></div>
                                        <p class="text-sm font-semibold text-gray-800">Técnico Asignado</p>
                                        <p class="text-xs text-gray-600"><?php echo e($ticket['tecnico_nombre']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Fecha de Visita Programada -->
                                <?php if (!empty($ticket['fecha_visita'])): ?>
                                    <div class="timeline-item relative pl-8">
                                        <div class="absolute left-0 top-0 w-4 h-4 bg-yellow-500 rounded-full shadow"></div>
                                        <p class="text-sm font-semibold text-gray-800">Visita Programada</p>
                                        <p class="text-xs text-gray-500">
                                            <?php
                                            $fecha_visita = new DateTime($ticket['fecha_visita']);
                                            echo $fecha_visita->format('d/m/Y H:i');
                                            ?> hrs
                                        </p>
                                    </div>
                                <?php endif; ?>

                                <!-- En Proceso -->
                                <?php if ($ticket['estado'] == 'en_proceso' || $ticket['estado'] == 'completado'): ?>
                                    <div class="timeline-item relative pl-8">
                                        <div class="absolute left-0 top-0 w-4 h-4 rounded-full shadow"
                                            style="background-color: <?php echo $estados['en_proceso']['color_timeline']; ?>"></div>
                                        <p class="text-sm font-semibold text-gray-800">En Proceso</p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Completado (EN ROJO CON ANIMACIÓN) -->
                                <?php if ($ticket['estado'] == 'completado'): ?>
                                    <div class="timeline-item relative pl-8">
                                        <div class="absolute left-0 top-0 w-4 h-4 rounded-full shadow animate-pulse"
                                            style="background-color: <?php echo $estados['completado']['color_timeline']; ?>"></div>
                                        <p class="text-sm font-semibold text-red-600">✅ Ticket Completado</p>
                                        <p class="text-xs text-gray-500"><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información adicional (CON QR CODE) -->
                    <div class="bg-white rounded-lg shadow-sm p-6">
                        <h3 class="text-lg font-bold text-gray-800 mb-4">ℹ️ Info Adicional</h3>
                        <div class="space-y-3 text-sm">
                            <div>
                                <p class="text-gray-500">ID del Ticket</p>
                                <p class="font-mono text-gray-800">#<?php echo str_pad($ticket['id'], 6, '0', STR_PAD_LEFT); ?></p>
                            </div>
                            <?php if (!empty($ticket['llamado_id'])): ?>
                                <div>
                                    <p class="text-gray-500">ID de Llamado</p>
                                    <p class="font-mono text-gray-800">#<?php echo str_pad($ticket['llamado_id'], 6, '0', STR_PAD_LEFT); ?></p>
                                </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-gray-500">Última actualización</p>
                                <p class="text-gray-800"><?php echo date('d/m/Y H:i', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-500">URL del Ticket</p>
                                <p class="text-xs break-all mb-2">
                                    <a href="<?php echo e($ticket['numero_ticket']); ?>" class="text-teqmed-blue hover:underline">
                                        llamados.teqmed.cl/<?php echo e($ticket['numero_ticket']); ?>
                                    </a>
                                </p>

                                <!-- Código QR -->
                                <div class="bg-gradient-to-br from-teqmed-blue to-teqmed-cyan p-4 rounded-lg mt-3">
                                    <p class="text-xs text-white text-center mb-2 font-semibold">📱 Escanea para abrir</p>
                                    <div class="flex justify-center bg-white p-3 rounded-lg">
                                        <?php
                                        $ticketUrl = 'https://llamados.teqmed.cl/' . urlencode($ticket['numero_ticket']);
                                        $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . urlencode($ticketUrl);
                                        ?>
                                        <img src="<?php echo $qrApiUrl; ?>"
                                            alt="QR Code del Ticket"
                                            class="w-36 h-36">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="bg-teqmed-blue text-white rounded-lg shadow-sm p-6 no-print">
                        <h3 class="text-lg font-bold mb-3">🆘 ¿Necesita ayuda?</h3>
                        <p class="text-sm opacity-90 mb-4">Si tiene dudas sobre su ticket, contáctenos:</p>
                        <a href="tel:(41) 213 7355" class="block bg-white text-teqmed-blue text-center py-2 rounded-lg font-semibold hover:bg-gray-100 transition mb-2">
                            📞 Llamar a Soporte
                        </a>
                        <a href="mailto:llamados@teqmed.cl?subject=Consulta%20Ticket%20<?php echo urlencode($ticket['numero_ticket']); ?>"
                            class="block bg-teqmed-cyan text-white text-center py-2 rounded-lg font-semibold hover:bg-opacity-90 transition">
                            ✉️ Enviar Email
                        </a>
                    </div>

                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-gray-800 text-white py-6 mt-12 no-print">
        <div class="container mx-auto px-4 text-center">
            <p class="text-sm">&copy; <?php echo date('Y'); ?> TEQMED SpA - Todos los derechos reservados</p>
            <p class="text-xs text-gray-400 mt-2">Sistema de Tickets v1.0 | Soporte: contacto@teqmed.cl</p>
        </div>
    </footer>

    <script>
        // Actualizar página automáticamente cada 60 segundos (opcional)
        // setTimeout(() => location.reload(), 60000);
    </script>

</body>

</html>