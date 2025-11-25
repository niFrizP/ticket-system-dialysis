<?php
// bootstrap.php
// Carga Composer, variables de entorno y Sentry lo antes posible para capturar errores durante el arranque

// Si hay autoload de Composer, lo cargamos (puede contener phpdotenv y otras deps)
$composerAutoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Ruta al .env en la raíz del proyecto
$envPath = __DIR__ . '/.env';

// Intentar usar phpdotenv si está disponible
$dotenvLoaded = false;
if (file_exists($composerAutoload) && class_exists('\Dotenv\Dotenv')) {
    try {
        \Dotenv\Dotenv::createImmutable(__DIR__)->load();
        $dotenvLoaded = true;
    } catch (Exception $e) {
        // No interrumpir la aplicación; tratamos fallback abajo
        $dotenvLoaded = false;
    }
}

// Fallback ligero: si no cargó phpdotenv pero existe .env, parsearlo manualmente
if (!$dotenvLoaded && file_exists($envPath) && is_readable($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
            continue;
        }
        // Separar key=value (solo primer '=')
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        // eliminar comillas envolventes simples o dobles
        if (strlen($value) >= 2) {
            if (($value[0] === '"' && $value[strlen($value) - 1] === '"') ||
                ($value[0] === "'" && $value[strlen($value) - 1] === "'")
            ) {
                $value = substr($value, 1, -1);
            }
        }

        // Normalizar valores vacíos a cadena vacía
        if ($value === 'null' || $value === 'NULL') {
            $value = '';
        }

        // Establecer en getenv/$_ENV/$_SERVER si no están ya
        if (getenv($key) === false) {
            putenv("$key=$value");
        }
        if (!isset($_ENV[$key])) {
            $_ENV[$key] = $value;
        }
        if (!isset($_SERVER[$key])) {
            $_SERVER[$key] = $value;
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