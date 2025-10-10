<?php
// config/sentry.php
require_once __DIR__ . '/../vendor/autoload.php';

$dsn = getenv('SENTRY_DSN') ?: null;
$env = getenv('APP_ENV') ?: 'production';

if ($dsn) {
    \Sentry\init([
        'dsn' => $dsn,
        'environment' => $env,
        'traces_sample_rate' => 0.0,
    ]);
    return true;
}

return false;
