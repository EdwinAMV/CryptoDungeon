<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;

// The community runtime invokes this script after installing Composer packages.
// Refuse to run against the developer's local .env by accident.
if (getenv('VERCEL') !== '1') {
    fwrite(STDERR, "Este comando es exclusivo del build de Vercel; no modifica la base local.\n");
    exit(1);
}

require __DIR__.'/../bootstrap/vercel.php';
require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

try {
    $key = config('app.key');
    $decodedKey = is_string($key) && str_starts_with($key, 'base64:')
        ? base64_decode(substr($key, 7), true) : false;

    if (! is_string($decodedKey) || strlen($decodedKey) !== 32) {
        throw new RuntimeException('Configura una APP_KEY de producción válida antes del despliegue.');
    }

    if (config('app.env') !== 'production' || config('app.debug') !== false) {
        throw new RuntimeException('Vercel requiere APP_ENV=production y APP_DEBUG=false.');
    }

    if (! filter_var(config('app.url'), FILTER_VALIDATE_URL) || ! str_starts_with(config('app.url'), 'https://')) {
        throw new RuntimeException('Configura APP_URL con el dominio HTTPS de este entorno.');
    }

    if (config('database.default') !== 'pgsql' || ! config('database.connections.pgsql.url')) {
        throw new RuntimeException('Configura DB_CONNECTION=pgsql y DB_URL (o DATABASE_URL) con la conexión de Neon.');
    }

    if (! extension_loaded('pdo_pgsql')) {
        throw new RuntimeException('El runtime PHP necesita la extensión pdo_pgsql para conectar con Neon.');
    }

    if (config('session.driver') !== 'database' || config('cache.default') !== 'database') {
        throw new RuntimeException('Usa SESSION_DRIVER=database y CACHE_STORE=database para conservar el estado entre instancias.');
    }

    if (! is_file($app->publicPath('build/manifest.json'))) {
        throw new RuntimeException('Falta public/build/manifest.json. Ejecuta el build frontend antes de empaquetar PHP.');
    }

    foreach (['package:discover', 'migrate'] as $command) {
        $arguments = $command === 'migrate' ? ['--force' => true, '--no-interaction' => true] : [];
        $exitCode = $kernel->call($command, $arguments);
        fwrite(STDOUT, $kernel->output());

        if ($exitCode !== 0) {
            throw new RuntimeException('Ha fallado '.$command.'. El despliegue se detiene.');
        }
    }
} catch (Throwable $exception) {
    // Do not print database exceptions: they can contain credentials or SQL data.
    if ($exception instanceof RuntimeException && ! $exception instanceof PDOException && ! $exception instanceof QueryException) {
        fwrite(STDERR, $exception->getMessage()."\n");
    } else {
        fwrite(STDERR, 'No se pudo preparar la base de datos de producción ('.get_class($exception)."). Revisa la conexión de Neon.\n");
    }
    exit(1);
}
