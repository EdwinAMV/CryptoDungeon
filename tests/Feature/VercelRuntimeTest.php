<?php

namespace Tests\Feature;

use App\Database\NeonPostgresConnector;
use App\Livewire\Host\SessionCreate;
use App\Livewire\Host\SessionLobby;
use App\Models\GameSession;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\Connectors\SQLiteConnector;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
#[PreserveGlobalState(false)]
class VercelRuntimeTest extends TestCase
{
    use RefreshDatabase;

    public function createApplication()
    {
        putenv('VERCEL=1');
        require __DIR__.'/../../bootstrap/vercel.php';

        return parent::createApplication();
    }

    public function test_framework_writes_to_temporary_directories_instead_of_the_application_bundle(): void
    {
        $temporaryPath = sys_get_temp_dir().'/cryptodungeon';

        $this->assertSame($temporaryPath.'/storage', storage_path());
        $this->assertSame($temporaryPath.'/storage/framework/views', config('view.compiled'));
        $this->assertSame($temporaryPath.'/bootstrap/packages.php', app()->getCachedPackagesPath());
        $this->assertSame($temporaryPath.'/bootstrap/services.php', app()->getCachedServicesPath());
        $this->get(route('rooms.create'))->assertOk()->assertSee('Crear lobby');
        $this->assertDirectoryExists(config('view.compiled'));
    }

    public function test_vercel_uses_the_neon_connector_only_for_postgres(): void
    {
        $factory = app(ConnectionFactory::class);

        $this->assertInstanceOf(NeonPostgresConnector::class, $factory->createConnector(['driver' => 'pgsql']));
        $this->assertInstanceOf(SQLiteConnector::class, $factory->createConnector(['driver' => 'sqlite']));
    }

    public function test_room_creation_and_group_management_work_with_the_vercel_bootstrap(): void
    {
        Livewire::test(SessionCreate::class)->call('save')->assertHasNoErrors();
        $game = GameSession::sole();

        $this->get(route('rooms.lobby', $game))->assertOk();
        Livewire::test(SessionLobby::class, ['gameSession' => $game])
            ->set('teamName', 'Exploradores')->call('addTeam')->assertHasNoErrors();

        $this->assertDatabaseHas('teams', ['game_session_id' => $game->id, 'name' => 'Exploradores']);
        $this->assertGuest();
    }

    public function test_https_and_client_ip_are_read_from_the_vercel_proxy(): void
    {
        Route::get('/runtime-test', fn (Request $request) => [
            'secure' => $request->secure(), 'ip' => $request->ip(), 'host' => $request->getHost(),
        ]);

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.1'])
            ->withHeaders([
                'Host' => 'cryptodungeon.vercel.app',
                'X-Forwarded-Proto' => 'https',
                'X-Forwarded-Port' => '443',
                'X-Forwarded-For' => '203.0.113.7',
                'X-Forwarded-Host' => 'untrusted.example',
            ])->get('http://cryptodungeon.vercel.app/runtime-test')->assertOk()->assertExactJson([
                'secure' => true, 'ip' => '203.0.113.7', 'host' => 'cryptodungeon.vercel.app',
            ]);
    }
}
