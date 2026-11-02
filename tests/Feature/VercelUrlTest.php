<?php

namespace Tests\Feature;

use Symfony\Component\Process\Process;
use Tests\TestCase;

class VercelUrlTest extends TestCase
{
    private function detectedUrl(array $environment): string
    {
        $process = new Process([
            PHP_BINARY, '-r', 'require "bootstrap/vercel.php"; echo getenv("APP_URL") ?: "";',
        ], base_path(), array_merge([
            'APP_URL' => false,
            'VERCEL_ENV' => false,
            'VERCEL_PROJECT_PRODUCTION_URL' => false,
            'VERCEL_URL' => false,
        ], $environment));
        $process->mustRun();

        return $process->getOutput();
    }

    public function test_production_uses_the_stable_domain_instead_of_the_temporary_deployment_url(): void
    {
        $this->assertSame('https://cryptodungeon.vercel.app', $this->detectedUrl([
            'VERCEL_ENV' => 'production',
            'VERCEL_PROJECT_PRODUCTION_URL' => 'cryptodungeon.vercel.app',
            'VERCEL_URL' => 'cryptodungeon-abc123.vercel.app',
        ]));
    }

    public function test_preview_uses_its_own_url_even_when_a_production_domain_is_available(): void
    {
        $this->assertSame('https://cryptodungeon-preview.vercel.app', $this->detectedUrl([
            'VERCEL_ENV' => 'preview',
            'VERCEL_PROJECT_PRODUCTION_URL' => 'cryptodungeon.vercel.app',
            'VERCEL_URL' => 'cryptodungeon-preview.vercel.app',
        ]));
    }

    public function test_production_falls_back_to_the_deployment_url_if_needed(): void
    {
        $this->assertSame('https://cryptodungeon-abc123.vercel.app', $this->detectedUrl([
            'VERCEL_ENV' => 'production', 'VERCEL_URL' => 'cryptodungeon-abc123.vercel.app',
        ]));
    }

    public function test_explicit_app_url_is_preserved(): void
    {
        $this->assertSame('https://aula.example.com', $this->detectedUrl([
            'APP_URL' => 'https://aula.example.com',
            'VERCEL_ENV' => 'production', 'VERCEL_URL' => 'cryptodungeon.vercel.app',
        ]));
    }

    public function test_empty_app_url_does_not_prevent_automatic_detection(): void
    {
        $this->assertSame('https://cryptodungeon.vercel.app', $this->detectedUrl([
            'APP_URL' => '', 'VERCEL_ENV' => 'production', 'VERCEL_URL' => 'cryptodungeon.vercel.app',
        ]));
    }

    public function test_missing_system_variables_do_not_replace_the_local_configuration(): void
    {
        $this->assertSame('', $this->detectedUrl([]));
    }

    public function test_invalid_system_host_is_not_used_as_an_application_url(): void
    {
        $this->assertSame('', $this->detectedUrl([
            'VERCEL_ENV' => 'preview', 'VERCEL_URL' => 'example.com/unsafe-path',
        ]));
    }
}
