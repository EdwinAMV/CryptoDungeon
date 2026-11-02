<?php

namespace App\Support;

use PDOException;
use Throwable;

class DatabaseBuildDiagnostic
{
    public static function describe(Throwable $exception): string
    {
        $state = 'desconocido';
        $messages = '';

        for ($current = $exception; $current !== null; $current = $current->getPrevious()) {
            $messages .= ' '.strtolower($current->getMessage());

            if ($current instanceof PDOException) {
                $candidate = (string) ($current->errorInfo[0] ?? $current->getCode());
                if (preg_match('/\A[A-Z0-9]{5}\z/', $candidate)) {
                    $state = $candidate;
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

        // Only whitelisted codes and fixed hints are returned. Never interpolate
        // exception messages, SQL, connection strings, hostnames or passwords.
        return 'SQLSTATE='.$state.'. '.$hint;
    }

    private static function connectionHint(string $message): string
    {
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

        if (str_contains($message, 'endpoint is disabled') || str_contains($message, 'endpoint is not active')) {
            return 'El endpoint de Neon no está activo: comprueba su estado en la consola.';
        }

        return 'Error de base de datos no clasificado. Usa la etapa y el SQLSTATE para continuar el diagnóstico.';
    }
}
