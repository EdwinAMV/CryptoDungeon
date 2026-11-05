@props(['hints', 'roomId', 'teams' => false])
<div wire:key="letter-hints-{{ $roomId }}" class="my-4" x-data="{
    open: false, selected: null, quote: null, cost: null, busy: false,
    clearSelection() { this.selected = null; this.quote = null; this.cost = null; },
    select(position) {
        if (this.busy) return;
        this.selected = position;
        this.quote = Number(this.$refs.trigger.dataset.nextNumber);
        this.cost = Number(this.$refs.trigger.dataset.nextPenalty);
    },
    close() { if (!this.busy) { this.open = false; this.clearSelection(); } },
    async confirm() {
        if (this.selected === null || this.busy) return;
        this.busy = true;
        try { await this.$wire.requestHint({{ $roomId }}, this.selected, this.quote); }
        finally { this.busy = false; }
    }
}" x-effect="if (quote !== null && quote !== {{ $hints['next_number'] }}) clearSelection()" @keydown.escape.window="if (open) close()" @hint-letter-revealed.window="if ($event.detail.roomId === {{ $roomId }}) clearSelection()">
    <div class="flex flex-wrap items-center gap-3">
        <button type="button" x-ref="trigger" @click="clearSelection(); open = true"
            data-next-number="{{ $hints['next_number'] }}" data-next-penalty="{{ $hints['next_penalty'] }}"
            @class(['hint-orb', 'hint-orb-ready' => $hints['available']])
            aria-label="Abrir comodín para revelar una letra" aria-haspopup="dialog" :aria-expanded="open" aria-controls="hint-dialog-{{ $roomId }}">?</button>
        <div class="text-xs text-slate-400" aria-live="polite">
            @if ($hints['complete'])
                <p>Todas las letras están reveladas.</p>
            @elseif ($hints['available'])
                <p class="text-amber-200">Comodín disponible · Próxima letra: −{{ $hints['next_penalty'] }} puntos</p>
            @else
                <p>Próximo comodín en {{ $hints['remaining'] }} s · −{{ $hints['next_penalty'] }} puntos</p>
            @endif
            @if ($hints['revealed_count'])<p class="mt-1">Letras reveladas: {{ $hints['revealed_count'] }}. Pulsa «?» para consultarlas.</p>@endif
        </div>
    </div>
    <template x-teleport="body">
        <div x-show="open" x-cloak x-transition.opacity class="hint-modal-backdrop" @click.self="close()">
            <section id="hint-dialog-{{ $roomId }}" role="dialog" aria-modal="true" aria-labelledby="hint-title-{{ $roomId }}"
                class="hint-modal" x-trap.inert.noscroll="open" tabindex="-1">
                <header class="border-b border-slate-700 px-5 py-4">
                    <h3 id="hint-title-{{ $roomId }}" class="text-xl font-bold text-amber-100">¿Quieres usar el comodín?</h3>
                    <p class="mt-2 text-sm text-slate-300">Selecciona la letra cifrada que quieres revelar. Las casillas verdes ya están descifradas y no se pueden volver a elegir.</p>
                    @if ($teams)<p class="mt-2 text-xs text-slate-400">Tu equipo comparte las letras reveladas y paga cada posición una sola vez.</p>@endif
                </header>
                <div class="hint-modal-content">
                    <div class="ciphertext-no-copy flex flex-wrap gap-x-4 gap-y-3" aria-label="Mensaje cifrado con letras seleccionables" oncopy="return false" oncontextmenu="return false" ondragstart="return false">
                        @foreach ($hints['words'] as $word)
                            <span class="inline-flex max-w-full flex-wrap gap-1">
                                @foreach ($word as $letter)
                                    @if ($letter['is_letter'])
                                        <button type="button" wire:key="hint-letter-{{ $roomId }}-{{ $letter['position'] }}"
                                            @if (! $letter['revealed']) @click="select({{ $letter['position'] }})" @endif
                                            @disabled($letter['revealed'])
                                            :disabled="busy || {{ $letter['revealed'] ? 'true' : 'false' }}"
                                            @class(['hint-letter', 'hint-letter-revealed' => $letter['revealed']])
                                            :class="{ 'hint-letter-selected': selected === {{ $letter['position'] }} && {{ $letter['revealed'] ? 'false' : 'true' }} }"
                                            :aria-pressed="selected === {{ $letter['position'] }}"
                                            aria-label="{{ $letter['revealed'] ? 'Letra revelada: '.$letter['plain'] : 'Letra cifrada: '.$letter['cipher'] }}, posición {{ $letter['position'] + 1 }}">{{ $letter['revealed'] ? $letter['plain'] : $letter['cipher'] }}</button>
                                    @else
                                        <span class="hint-letter-static">{{ $letter['cipher'] }}</span>
                                    @endif
                                @endforeach
                            </span>
                        @endforeach
                    </div>
                    <p class="mt-4 text-xs text-slate-400">Cada casilla revela solo esa posición, aunque la letra se repita. El coste se resta del premio de esta habitación al resolverla.</p>
                    @error('hint')<p role="alert" class="mt-4 rounded-lg bg-red-950 p-3 text-sm text-red-200">{{ $message }}</p>@enderror
                    @if ($hints['complete'])
                        <p class="mt-4 text-sm text-emerald-300">Ya puedes consultar el mensaje completo y escribir tu respuesta.</p>
                    @elseif (! $hints['available'])
                        <p class="mt-4 text-sm text-amber-200">El siguiente comodín estará disponible en {{ $hints['remaining'] }} s.</p>
                    @endif
                </div>
                <footer class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-700 px-5 py-4">
                    <p class="text-sm text-amber-200" x-text="selected === null ? 'Elige una casilla para continuar.' : 'Revelar esta letra resta ' + cost + ' puntos.'">Elige una casilla para continuar.</p>
                    <div class="flex gap-3">
                        <button type="button" autofocus @click="close()" :disabled="busy" class="btn-secondary">Cancelar</button>
                        <button type="button" @click="confirm()"
                            :disabled="busy || selected === null || {{ $hints['available'] ? 'false' : 'true' }}" disabled
                            class="btn-primary" x-text="busy ? 'Revelando…' : 'Confirmar'">Confirmar</button>
                    </div>
                </footer>
            </section>
        </div>
    </template>
</div>
