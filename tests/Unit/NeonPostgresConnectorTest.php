<?php

namespace Tests\Unit;

use App\Database\NeonPostgresConnector;
use Illuminate\Database\Connectors\PostgresConnector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class NeonPostgresConnectorTest extends TestCase
{
    private function dsn(array $config, bool $neon = true): string
    {
        $connector = $neon ? new NeonPostgresConnector : new PostgresConnector;
        $method = new \ReflectionMethod($connector, 'getDsn');

        return $method->invoke($connector, array_merge([
            'database' => 'neondb', 'port' => 5432, 'charset' => 'utf8', 'sslmode' => 'require',
        ], $config));
    }

    public static function neonHosts(): array
    {
        return [
            'direct' => ['ep-cool-darkness-123456.us-east-2.aws.neon.tech'],
            'pooled' => ['ep-cool-darkness-123456-pooler.us-east-2.aws.neon.tech'],
            'uppercase' => ['EP-COOL-DARKNESS-123456.eu-central-1.aws.neon.tech'],
        ];
    }

    #[DataProvider('neonHosts')]
    public function test_sends_the_endpoint_explicitly_without_changing_ssl_or_the_pooler_host(string $host): void
    {
        $dsn = $this->dsn(['host' => $host]);

        $this->assertStringContainsString('host='.$host.';', $dsn);
        $this->assertStringContainsString("options='endpoint=ep-cool-darkness-123456'", $dsn);
        $this->assertStringContainsString('sslmode=require', $dsn);
        $this->assertStringNotContainsString('endpoint=ep-cool-darkness-123456-pooler', $dsn);
        $this->assertStringNotContainsString('-c endpoint', $dsn);
    }

    public function test_existing_server_options_are_preserved_in_one_options_parameter(): void
    {
        $dsn = $this->dsn([
            'host' => 'ep-cool-darkness-123456.eu-central-1.aws.neon.tech',
            'server_options' => ['statement_timeout' => '15000'],
        ]);

        $this->assertStringContainsString("options='endpoint=ep-cool-darkness-123456 -c statement_timeout=15000'", $dsn);
        $this->assertSame(1, substr_count($dsn, ';options='));
    }

    public function test_other_postgres_hosts_keep_laravels_original_dsn(): void
    {
        foreach (['127.0.0.1', 'postgres.example.com', 'ep-test.neon.tech.attacker.example', 'invalid_endpoint.neon.tech'] as $host) {
            $config = ['host' => $host, 'server_options' => ['statement_timeout' => '15000']];
            $this->assertSame($this->dsn($config, neon: false), $this->dsn($config));
        }
    }

    public function test_password_is_not_added_to_the_dsn_or_modified_for_routing(): void
    {
        $dsn = $this->dsn([
            'host' => 'ep-cool-darkness-123456.us-east-2.aws.neon.tech',
            'username' => 'neondb_owner', 'password' => 'private-test-password',
        ]);

        $this->assertStringNotContainsString('private-test-password', $dsn);
        $this->assertStringNotContainsString('password=', $dsn);
    }
}
