<?php

// This bootstrap is used only by the Vercel entry point and its build hook.
// public/index.php, artisan and Herd retain Laravel's normal local settings.
$temporaryPath = sys_get_temp_dir().'/cryptodungeon';

$defaults = [
    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'APP_LOCALE' => 'es',
    'DB_CONNECTION' => 'pgsql',
    'DB_SSLMODE' => 'require',
    'SESSION_DRIVER' => 'database',
    'SESSION_SECURE_COOKIE' => 'true',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'sync',
    'LOG_CHANNEL' => 'stderr',
    'VERCEL_PHP_DOCROOT' => 'public',
];

foreach ($defaults as $name => $value) {
    if (getenv($name) === false && ! isset($_ENV[$name]) && ! isset($_SERVER[$name])) {
        putenv($name.'='.$value);
        $_ENV[$name] = $_SERVER[$name] = $value;
    }
}

// Always keep writable framework paths outside the read-only application.
foreach ([
    'LARAVEL_STORAGE_PATH' => $temporaryPath.'/storage',
    'VIEW_COMPILED_PATH' => $temporaryPath.'/storage/framework/views',
    'APP_CONFIG_CACHE' => $temporaryPath.'/bootstrap/config.php',
    'APP_EVENTS_CACHE' => $temporaryPath.'/bootstrap/events.php',
    'APP_PACKAGES_CACHE' => $temporaryPath.'/bootstrap/packages.php',
    'APP_ROUTES_CACHE' => $temporaryPath.'/bootstrap/routes.php',
    'APP_SERVICES_CACHE' => $temporaryPath.'/bootstrap/services.php',
] as $name => $value) {
    putenv($name.'='.$value);
    $_ENV[$name] = $_SERVER[$name] = $value;
}

foreach (['bootstrap', 'storage/framework/views', 'storage/framework/cache/data', 'storage/logs'] as $directory) {
    $path = $temporaryPath.'/'.$directory;

    if (! is_dir($path) && ! mkdir($path, 0700, true) && ! is_dir($path)) {
        throw new RuntimeException('No se pudo preparar el almacenamiento temporal de Laravel.');
    }
}
