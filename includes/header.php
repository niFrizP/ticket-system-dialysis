<?php
// Cargar variables de entorno desde .env en entornos de desarrollo si phpdotenv está instalado
// No falla si no existe .env ni phpdotenv; en producción se deben usar variables de entorno del servidor
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';

    // Cargar .env si existe y phpdotenv está disponible
    if (file_exists(__DIR__ . '/../.env') && class_exists('\Dotenv\Dotenv')) {
        try {
            \Dotenv\Dotenv::createImmutable(__DIR__ . '/..')->load();
        } catch (Exception $e) {
            // No debería interrumpir la app en caso de problemas con .env
        }
    }
}

// Inicializar Sentry (si está configurado en config/sentry.php)
// Se requiere aquí para que Sentry capture errores en todas las páginas
require_once __DIR__ . '/../config/sentry.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket de Llamado - TEQMED SpA</title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <!-- Cloudflare Turnstile -->
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>

    <!-- Preconnect to Cloudflare for performance -->
    <link rel="preconnect" href="https://challenges.cloudflare.com">

    <script type="text/javascript">
        (function(c, l, a, r, i, t, y) {
            c[a] = c[a] || function() {
                (c[a].q = c[a].q || []).push(arguments)
            };
            t = l.createElement(r);
            t.async = 1;
            t.src = "https://www.clarity.ms/tag/" + i;
            y = l.getElementsByTagName(r)[0];
            y.parentNode.insertBefore(t, y);
        })(window, document, "clarity", "script", "u5zst6os2n");
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/custom.css">

    <style>
        body {
            font-family: 'Inter', sans-serif;
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