<?php

namespace Tests\Feature;

use App\Models\Participant;
use App\Models\Team;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class ChatComposerTest extends TestCase
{
    public function test_enter_composition_emojis_and_pending_sends_preserve_the_draft(): void
    {
        View::share('errors', new ViewErrorBag);
        $player = (new Participant)->forceFill(['id' => 1])->setRelation('team', (new Team)->forceFill(['name' => 'Azul']));
        $html = view('livewire.player.team-chat', ['participant' => $player, 'messages' => collect(), 'cooldownUntil' => 0, 'emojis' => ['💡' => 'Idea']])->render();
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $expression = (new DOMXPath($document))->query('//*[@x-data]')->item(0)->getAttribute('x-data');
        preg_match('/@keydown.enter="([^"]*)"/', $html, $match);
        $keyboard = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $process = new Process(['node', '--input-type=module', '-e', <<<'JS'
import assert from 'node:assert/strict';
let source = '';
for await (const chunk of process.stdin) source += chunk;
const { expression, keyboard } = JSON.parse(source);
const chat = Function('return (' + expression + ')')();
const field = {
    value: 'Hola mundo', selectionStart: 5, selectionEnd: 10, maxLength: 500,
    setRangeText(text, start, end) { this.value = this.value.slice(0, start) + text + this.value.slice(end); },
    dispatchEvent(event) { assert.equal(event.type, 'input'); this.updated = true; },
    focus() { this.focused = true; }
};
chat.$refs = { composer: field, cooldown: { dataset: { until: '0' } } };
chat.addEmoji('👍');
assert.equal(field.value, 'Hola 👍');
assert.equal(field.updated, true);
assert.equal(field.focused, true);
field.value = 'a'.repeat(500); field.selectionStart = 500; field.selectionEnd = 500;
chat.addEmoji('👍');
assert.equal(field.value.length, 500);
let calls = 0, resolve;
chat.$wire = { send() { calls++; return new Promise(done => { resolve = done; }); } };
field.value = '   ';
await chat.sendMessage(); assert.equal(calls, 0);
field.value = 'Borrador';
chat.$refs.cooldown.dataset.until = String(Math.floor(chat.now / 1000) + 4);
await chat.sendMessage(); assert.equal(calls, 0);
chat.$refs.cooldown.dataset.until = '0';
const key = Function('$event', 'with(this) {' + keyboard + '}').bind(chat);
let prevented = 0;
const event = { shiftKey: false, isComposing: false, keyCode: 13, repeat: false, preventDefault() { prevented++; } };
key({ ...event, shiftKey: true }); key({ ...event, isComposing: true }); key({ ...event, keyCode: 229 });
assert.equal(calls, 0); assert.equal(prevented, 0);
key(event); key(event); key({ ...event, repeat: true });
assert.equal(calls, 1); assert.equal(chat.busy, true);
chat.addEmoji('💡'); assert.equal(field.value, 'Borrador');
resolve(); await Promise.resolve(); await Promise.resolve();
assert.equal(chat.busy, false);
assert.equal(field.value, 'Borrador');
JS]);
        $process->setInput(json_encode(compact('expression', 'keyboard'), JSON_THROW_ON_ERROR))->mustRun();
        $this->assertTrue($process->isSuccessful());
    }
}
