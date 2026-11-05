<?php

namespace Tests\Feature;

use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class HintModalTest extends TestCase
{
    public function test_cancel_never_purchases_and_confirmation_cannot_double_send_or_change_selection(): void
    {
        View::share('errors', new ViewErrorBag);
        $state = [
            'next_number' => 1, 'next_penalty' => 10, 'remaining' => 0, 'available' => true,
            'complete' => false, 'revealed_count' => 0, 'words' => [],
        ];
        $html = Blade::render('<x-puzzle-hints :hints="$hints" :room-id="42" />', ['hints' => $state]);
        $document = new DOMDocument;
        @$document->loadHTML($html);
        $expression = (new DOMXPath($document))->query('//*[@x-data]')->item(0)->getAttribute('x-data');
        $process = new Process(['node', '--input-type=module', '-e', <<<'JS'
import assert from 'node:assert/strict';
let source = '';
for await (const chunk of process.stdin) source += chunk;
const modal = Function('return (' + source + ')')();
modal.$refs = { trigger: { dataset: { nextNumber: '1', nextPenalty: '10' } } };
let complete;
const calls = [];
modal.$wire = { requestHint(...args) { calls.push(args); return new Promise(resolve => { complete = resolve; }); } };
await modal.confirm();
assert.equal(calls.length, 0);
modal.open = true;
modal.select(3);
assert.equal(modal.cost, 10);
modal.close();
assert.equal(modal.selected, null);
assert.equal(calls.length, 0);
modal.open = true;
modal.select(3);
const pending = modal.confirm();
assert.equal(modal.busy, true);
await modal.confirm();
modal.select(4);
modal.close();
assert.equal(modal.open, true);
assert.equal(modal.selected, 3);
assert.deepEqual(calls, [[42, 3, 1]]);
modal.clearSelection();
complete();
await pending;
assert.equal(modal.busy, false);
assert.equal(modal.selected, null);
modal.close();
assert.equal(modal.open, false);
JS]);
        $process->setInput($expression)->mustRun();
        $this->assertTrue($process->isSuccessful());
    }
}
