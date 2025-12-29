<?php

/**
 * Configuration Loader - Carga variables de entorno de forma segura
 */
class Config
{
    private static $loaded = false;

    /**
     * Carga las variables de entorno desde el archivo .env
     */
    public static function load()
    {
        if (self::$loaded) {
            return;
        }

        $envFile = __DIR__ . '/../.env';

        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

            foreach ($lines as $line) {
                // Ignorar comentarios
                if (strpos(trim($line), '#') === 0) {
                    continue;
                }

                // Parsear variable
                if (strpos($line, '=') !== false) {
                    list($key, $value) = explode('=', $line, 2);
                    $key = trim($key);
                    $value = trim($value);

                    // Remover comillas si existen
                    $value = trim($value, '"\'');

                    // Establecer variable de entorno
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }

        self::$loaded = true;
    }

    /**
     * Obtiene una variable de entorno
     */
    public static function get($key, $default = null)
    {
        self::load();
        return $_ENV[$key] ?? $default;
    }
}
