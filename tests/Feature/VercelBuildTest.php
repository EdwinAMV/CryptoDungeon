<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class VercelBuildTest extends TestCase
{
    public function test_build_hook_refuses_to_run_in_the_local_environment(): void
    {
        $process = new Process([PHP_BINARY, base_path('scripts/vercel-build.php')], base_path(), ['VERCEL' => false]);
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('no modifica la base local', $process->getErrorOutput());
    }

    public function test_missing_production_key_stops_the_build_before_connecting_to_a_database(): void
    {
        $process = new Process([PHP_BINARY, base_path('scripts/vercel-build.php')], base_path(), [
            'VERCEL' => '1', 'APP_ENV' => 'production', 'APP_DEBUG' => 'false', 'APP_KEY' => '',
            'DB_CONNECTION' => 'pgsql', 'DB_URL' => '', 'DATABASE_URL' => '',
        ]);
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('APP_KEY', $process->getErrorOutput());
        $this->assertStringNotContainsString('SQLSTATE', $process->getOutput().$process->getErrorOutput());
    }

    public function test_missing_database_url_stops_the_build_without_falling_back_to_sqlite(): void
    {
        $process = new Process([PHP_BINARY, base_path('scripts/vercel-build.php')], base_path(), [
            'VERCEL' => '1', 'APP_ENV' => 'production', 'APP_DEBUG' => 'false',
            'APP_KEY' => 'base64:'.base64_encode(random_bytes(32)),
            'APP_URL' => 'https://cryptodungeon.vercel.app',
            'DB_CONNECTION' => 'pgsql', 'DB_URL' => '', 'DATABASE_URL' => '',
            'SESSION_DRIVER' => 'database', 'CACHE_STORE' => 'database',
        ]);
        $process->run();

        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('DB_URL', $process->getErrorOutput());
        $this->assertStringNotContainsString('SQLSTATE', $process->getOutput().$process->getErrorOutput());
    }
}
