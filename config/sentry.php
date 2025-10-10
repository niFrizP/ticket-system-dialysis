<?php
// config/sentry.php
// Inicializa Sentry de forma segura. Emite trazas a error_log para diagnóstico en hosting compartido.

// vendor/autoload.php suele cargarse desde bootstrap; intentamos no forzar doble carga.
if (!class_exists('\Sentry\\init') && file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

$dsn = getenv('SENTRY_DSN') ?: null;
// Fallback: phpdotenv puede poblar $_ENV/$_SERVER en funciones de configuración de PHP
if (empty($dsn)) {
    if (!empty($_ENV['SENTRY_DSN'])) {
        $dsn = $_ENV['SENTRY_DSN'];
    } elseif (!empty($_SERVER['SENTRY_DSN'])) {
        $dsn = $_SERVER['SENTRY_DSN'];
    }
}
// Normalizar: quitar comillas o espacios accidentales
if (!empty($dsn)) {
    $dsn = trim($dsn, " \t\n\r\0\x0B'\"");
}
$env = getenv('APP_ENV') ?: 'production';
$debug = getenv('SENTRY_DEBUG') ?: false; // Si se define a 1, habilita logs adicionales

if ($dsn) {
    try {
        \Sentry\init([
            'dsn' => $dsn,
            'environment' => $env,
            'traces_sample_rate' => 0.0,
        ]);

        // Registrar en error_log para diagnóstico (seguro en hosting compartido)
        error_log("[Sentry] initialized (env={$env})");

        // Si SENTRY_DEBUG=1, registrar el último event id al shutdown para verificar envíos
        if ($debug) {
            register_shutdown_function(function () {
                try {
                    // Usar la API del Hub si está disponible (más robusto que llamar a la función helper)
                    // Intentar varias formas de obtener el último event id según la versión de SDK
                    try {
                        // Preferir Sdk::getCurrentHub() si está disponible
                        if (class_exists('Sentry\\Sdk') && is_callable(['Sentry\\Sdk', 'getCurrentHub'])) {
                            $hub = call_user_func(['Sentry\\Sdk', 'getCurrentHub']);
                        } elseif (class_exists('Sentry\\State\\Hub') && is_callable(['Sentry\\State\\Hub', 'getCurrent'])) {
                            $hub = call_user_func(['Sentry\\State\\Hub', 'getCurrent']);
                        } else {
                            $hub = null;
                        }

                        if ($hub !== null && method_exists($hub, 'getLastEventId')) {
                            $id = $hub->getLastEventId();
                            error_log('[Sentry] lastEventId: ' . ($id ?: '<none>'));
                            return;
                        }
                    } catch (Throwable $e) {
                        // Ignorar y proceder al fallback
                    }
                    // Fallback: la función helper puede no estar disponible
                    error_log('[Sentry] lastEventId: not available');
                } catch (Throwable $e) {
                    error_log('[Sentry] lastEventId error: ' . $e->getMessage());
                }
            });
        }

        return true;
    } catch (Throwable $e) {
        // No interrumpir la app; registrar el error para diagnosis
        error_log('[Sentry] init error: ' . $e->getMessage());
        return false;
    }
}

// No hay DSN configurado
if ($debug) {
    error_log('[Sentry] not initialized: SENTRY_DSN not set');
}

return false;
