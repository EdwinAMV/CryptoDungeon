<?php

namespace Tests\Feature;

use App\View\Components\BackgroundMusic;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class BackgroundMusicTest extends TestCase
{
    public function test_playlist_discovers_audio_files_and_encodes_their_names(): void
    {
        $original = public_path();
        $temporary = sys_get_temp_dir().'/cryptodungeon-audio-'.bin2hex(random_bytes(8));
        File::makeDirectory($temporary.'/audio', recursive: true);
        try {
            $this->app->usePublicPath($temporary);
            File::put($temporary.'/audio/una pista.MP3', 'test');
            File::put($temporary.'/audio/otra.m4a', 'test');
            File::put($temporary.'/audio/README.md', 'not audio');
            File::makeDirectory($temporary.'/audio/directory.mp3');
            $tracks = (new BackgroundMusic)->tracks;
            $this->assertCount(2, $tracks);
            $this->assertContains(asset('audio/una%20pista.MP3'), $tracks);
            $this->assertContains(asset('audio/otra.m4a'), $tracks);
            File::deleteDirectory($temporary.'/audio');
            $this->assertSame([], (new BackgroundMusic)->tracks);
        } finally {
            $this->app->usePublicPath($original);
            File::deleteDirectory($temporary);
        }
    }

    public function test_rendered_player_chooses_tracks_without_repeating_and_resumes_without_reloading(): void
    {
        $html = Blade::render('<x-background-music />');
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $expression = (new DOMXPath($document))->query('//*[@x-data]')->item(0)->getAttribute('x-data');
        // Exercise the actual Alpine expression emitted by Blade, without downloading a JS framework.
        $process = new Process(['node', '--input-type=module', '-e', <<<'JS'
import assert from 'node:assert/strict';
let expression = '';
for await (const chunk of process.stdin) expression += chunk;
const player = Function('return (' + expression + ')')();
player.tracks = ['first.mp3', 'second.mp3', 'third.mp3'];
let loads = 0;
const audio = {
    paused: true, src: '', volume: 1,
    load() { loads++; },
    async play() { this.paused = false; },
    pause() { this.paused = true; },
};
player.$refs = { audio };
Math.random = () => 0;
assert.equal(loads, 0);
await player.startAutomatically();
assert.equal(audio.src, 'first.mp3');
assert.equal(audio.volume, .15);
await player.toggle();
assert.equal(audio.paused, true);
await player.toggle();
assert.equal(audio.src, 'first.mp3');
assert.equal(loads, 1);
await player.playNext();
assert.equal(audio.src, 'second.mp3');
await player.playNext();
assert.equal(audio.src, 'first.mp3');
player.tracks = ['only.mp3'];
await player.playNext();
await player.playNext();
assert.equal(audio.src, 'only.mp3');
const priorLoads = loads;
player.tracks = [];
await player.playNext();
assert.equal(loads, priorLoads);
player.tracks = ['broken.mp3'];
audio.play = async () => { throw new Error('unavailable'); };
await player.playNext();
assert.equal(player.failed, true);
// A browser autoplay restriction retries only after a real interaction, on the same track.
player.index = null;
player.tracks = ['restricted.mp3'];
audio.paused = true;
audio.play = async () => { throw Object.assign(new Error('gesture required'), { name: 'NotAllowedError' }); };
await player.startAutomatically();
assert.equal(player.autoplayBlocked, true);
assert.equal(player.failed, false);
const restrictedLoads = loads;
player.$el = { contains: () => false };
audio.play = async () => { audio.paused = false; };
await player.resumeAutomatically({ isTrusted: false, target: {} });
assert.equal(audio.paused, true);
await player.resumeAutomatically({ isTrusted: true, target: {} });
assert.equal(audio.paused, false);
assert.equal(player.autoplayBlocked, false);
assert.equal(loads, restrictedLoads);
await player.toggle();
await player.resumeAutomatically({ isTrusted: true, target: {} });
assert.equal(audio.paused, true);
JS]);
        $process->setInput($expression)->mustRun();
        $this->assertTrue($process->isSuccessful());
    }
}
