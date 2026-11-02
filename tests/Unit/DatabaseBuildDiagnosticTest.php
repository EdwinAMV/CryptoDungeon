<?php

namespace Tests\Unit;

use App\Support\DatabaseBuildDiagnostic;
use Illuminate\Database\QueryException;
use PDOException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class DatabaseBuildDiagnosticTest extends TestCase
{
    public static function databaseFailures(): array
    {
        return [
            'authentication' => ['28P01', 'password authentication failed', 'Autenticación'],
            'database' => ['3D000', 'database does not exist', 'base indicada no existe'],
            'permissions' => ['42501', 'permission denied', 'Permisos insuficientes'],
            'migration' => ['42703', 'undefined column', 'Conflicto de esquema'],
            'dns' => ['08006', 'could not translate host name', 'DNS'],
            'ssl' => ['08006', 'SSL certificate verify failed', 'SSL/TLS'],
            'timeout' => ['08006', 'connection timed out', 'Tiempo de conexión'],
            'sni' => ['08006', 'The endpoint ID is not specified', 'compatibilidad SNI'],
            'disabled endpoint' => ['08006', 'The endpoint has been disabled', 'no está activo'],
            'unknown' => ['HY000', 'unrecognized failure', 'no clasificado'],
        ];
    }

    #[DataProvider('databaseFailures')]
    public function test_reports_a_safe_hint_without_exposing_connection_details(string $state, string $message, string $hint): void
    {
        $password = 'private-password-for-test';
        $url = 'postgresql://private-user:'.$password.'@private-host.example/private-db';
        $previous = new PDOException($message.' '.$url);
        $previous->errorInfo = [$state, 7, $message.' '.$url];
        $exception = new QueryException('pgsql', 'SELECT secret FROM private_table WHERE token = ?', [$password], $previous);

        $result = DatabaseBuildDiagnostic::describe($exception);

        $this->assertStringContainsString('SQLSTATE='.$state, $result);
        $this->assertStringContainsString($hint, $result);
        foreach ([$password, $url, 'private-user', 'private-host', 'private-db', 'private_table', 'SELECT'] as $secret) {
            $this->assertStringNotContainsString($secret, $result);
        }
    }

    public function test_invalid_error_codes_are_not_copied_to_logs(): void
    {
        $exception = new PDOException('private-message');
        $exception->errorInfo = ['private-password', 7, 'private-message'];

        $result = DatabaseBuildDiagnostic::describe($exception);

        $this->assertStringContainsString('SQLSTATE=desconocido', $result);
        $this->assertStringNotContainsString('private-password', $result);
        $this->assertStringNotContainsString('private-message', $result);
    }

    public function test_connection_reason_is_preserved_while_credentials_and_sql_are_redacted(): void
    {
        $url = 'postgresql://private-user:private-password@private-host.example/private-db';
        $previous = new PDOException('connection failed');
        $previous->errorInfo = ['08006', 7,
            'connection to server at "private-host.example" failed: ERROR: The endpoint ID is not specified. '.$url."\nDETAIL: secret-row-content",
        ];
        $exception = new QueryException('pgsql', 'SELECT secret FROM private_table', [], $previous);

        $result = DatabaseBuildDiagnostic::describe($exception, [
            'url' => $url, 'host' => 'private-host.example', 'username' => 'private-user',
            'password' => 'private-password', 'database' => 'private-db',
        ]);

        $this->assertStringContainsString('The endpoint ID is not specified', $result);
        foreach (['private-user', 'private-password', 'private-host', 'private-db', 'private_table', 'SELECT', 'secret-row-content'] as $secret) {
            $this->assertStringNotContainsString($secret, $result);
        }
    }

    public function test_an_unknown_driver_reason_remains_visible_after_redaction(): void
    {
        $exception = new PDOException('connection failed');
        $exception->errorInfo = ['08006', 7, 'FATAL: unexpected server response password=private-password'];

        $result = DatabaseBuildDiagnostic::describe($exception, ['password' => 'private-password']);

        $this->assertStringContainsString('unexpected server response', $result);
        $this->assertStringNotContainsString('private-password', $result);
    }
}
