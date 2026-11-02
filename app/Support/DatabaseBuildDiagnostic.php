<?php

namespace App\Support;

use Illuminate\Database\QueryException;
use PDOException;
use Throwable;

class DatabaseBuildDiagnostic
{
    public static function describe(Throwable $exception, array $connection = []): string
    {
        $state = 'desconocido';
        $messages = '';
        $driverMessage = '';

        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            $messages .= ' '.strtolower($current->getMessage());

            if ($current instanceof PDOException) {
                $candidate = (string) ($current->errorInfo[0] ?? $current->getCode());
                if (preg_match('/\A[A-Z0-9]{5}\z/', $candidate)) {
                    $state = $candidate;
                }

                if (! $current instanceof QueryException) {
                    $driverMessage = (string) ($current->errorInfo[2] ?? $current->getMessage());
                }
            }
        }

        $hint = match ($state) {
            '28P01', '28000' => 'Autenticación rechazada: revisa el usuario y la contraseña de la conexión copiada de Neon.',
            '3D000' => 'La base indicada no existe: revisa la base seleccionada en Neon Connect.',
            '42501' => 'Permisos insuficientes: el rol necesita permisos para crear y modificar el esquema.',
            '25006' => 'La conexión es de solo lectura: selecciona el compute primario de lectura y escritura.',
            '42P01', '42703', '42P07', '42701', '23502', '23503', '23505', '42830' => 'Conflicto de esquema o datos: revisa la migración indicada. No reinicies la base.',
            default => self::connectionHint($messages),
        };

        $result = 'SQLSTATE='.$state.'. '.$hint;

        // Connection failures need the driver's reason, not Laravel's wrapper
        // (which contains SQL and bindings). Only expose it when the caller has
        // supplied the connection values needed for redaction.
        if (str_starts_with($state, '08') && $connection !== [] && $driverMessage !== '') {
            $result .= ' Motivo del controlador: '.self::redact($driverMessage, $connection);
        }

        return $result;
    }

    private static function redact(string $message, array $connection): string
    {
        $sensitive = [];
        foreach (['url', 'host', 'username', 'password', 'database'] as $key) {
            foreach ((array) ($connection[$key] ?? []) as $value) {
                if (is_string($value) && $value !== '') {
                    $sensitive[] = $value;
                    $sensitive[] = rawurldecode($value);
                }
            }
        }

        if (is_string($connection['url'] ?? null)) {
            $parts = parse_url($connection['url']);
            if (is_array($parts)) {
                foreach (['user', 'pass', 'host', 'path'] as $key) {
                    if (isset($parts[$key]) && $parts[$key] !== '') {
                        $sensitive[] = $parts[$key];
                        $sensitive[] = rawurldecode($parts[$key]);
                    }
                }
            }
        }

        $sensitive = array_unique($sensitive);
        usort($sensitive, fn (string $left, string $right) => strlen($right) <=> strlen($left));
        $message = str_replace($sensitive, '[oculto]', $message);
        $message = preg_replace('~(?:postgres(?:ql)?|pgsql)://[^\s]+~i', '[conexión oculta]', $message);
        $message = preg_replace('/\b(?:password|pgpassword)\s*=\s*(?:\'[^\']*\'|"[^"]*"|[^\s;]+)/i', 'password=[oculto]', $message);
        $message = preg_replace('/(?ims)^\s*(?:DETAIL|CONTEXT|STATEMENT|QUERY):.*$/', '', $message);
        $message = preg_replace('/"[^"\r\n]*"|\'[^\'\r\n]*\'/', '[oculto]', $message);
        $message = preg_replace('/[\x00-\x1F\x7F]+/', ' ', $message);

        return substr(trim($message), 0, 600);
    }

    private static function connectionHint(string $message): string
    {
        if (str_contains($message, 'endpoint id is not specified')) {
            return 'Neon no recibe el identificador del endpoint: revisa la compatibilidad SNI de libpq.';
        }

        if (str_contains($message, 'password authentication failed') || str_contains($message, 'authentication failed')) {
            return 'Autenticación rechazada: vuelve a copiar la conexión desde Neon Connect.';
        }

        if (str_contains($message, 'could not translate host name') || str_contains($message, 'getaddrinfo') || str_contains($message, 'name or service not known')) {
            return 'Resolución DNS fallida: comprueba que has copiado el host completo de Neon.';
        }

        if (str_contains($message, 'ssl') || str_contains($message, 'tls') || str_contains($message, 'certificate')) {
            return 'Problema SSL/TLS: conserva sslmode=require en la URL y revisa la configuración del runtime.';
        }

        if (str_contains($message, 'timed out') || str_contains($message, 'timeout expired')) {
            return 'Tiempo de conexión agotado: revisa el estado de Neon y sus restricciones de acceso de red.';
        }

        if (str_contains($message, 'connection refused')) {
            return 'Conexión rechazada: revisa que el endpoint de Neon esté activo y que el puerto sea correcto.';
        }

        if (str_contains($message, 'endpoint is disabled') || str_contains($message, 'endpoint is not active') || str_contains($message, 'endpoint has been disabled')) {
            return 'El endpoint de Neon no está activo: comprueba su estado en la consola.';
        }

        return 'Error de base de datos no clasificado. Usa la etapa y el SQLSTATE para continuar el diagnóstico.';
    }
}
