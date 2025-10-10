<?php
// bootstrap.php
// Carga Composer, variables de entorno y Sentry lo antes posible para capturar errores durante el arranque
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';

    // Cargar .env en desarrollo si existe
    if (file_exists(__DIR__ . '/.env') && class_exists('\Dotenv\Dotenv')) {
        try {
            \Dotenv\Dotenv::createImmutable(__DIR__)->load();
        } catch (Exception $e) {
            // No interrumpir si .env falla
        }
    }
}

// Inicializar Sentry si está configurado
if (file_exists(__DIR__ . '/config/sentry.php')) {
    // El propio config/sentry.php verifica SENTRY_DSN y no lanza errores si no está definido
    require_once __DIR__ . '/config/sentry.php';
}

// Opcional: configurar manejo de errores para que Sentry capture errores fatales
// Sentry se encarga de registrar excepciones no manejadas automáticamente tras init
