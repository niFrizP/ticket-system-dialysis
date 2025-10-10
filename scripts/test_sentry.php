<?php
// scripts/test_sentry.php
// Script de prueba para enviar una excepción a Sentry

require_once __DIR__ . '/../bootstrap.php';

echo "Iniciando prueba Sentry...\n";

try {
    throw new Exception('Sentry test exception from scripts/test_sentry.php');
} catch (Throwable $e) {
    if (function_exists('Sentry\captureException')) {
        \Sentry\captureException($e);
        echo "Excepción enviada a Sentry (si SENTRY_DSN está configurado).\n";

        // Mostrar lastEventId si está disponible (útil para verificar envío localmente)
        if (function_exists('Sentry\\lastEventId')) {
            $last = \Sentry\lastEventId();
            echo "Sentry lastEventId: " . ($last ?: '<none>') . "\n";
        }
    } else {
        echo "Sentry no está disponible o no inicializado; comprueba SENTRY_DSN y vendor/autoload.php.\n";
    }
}

echo "Script finalizado.\n";
