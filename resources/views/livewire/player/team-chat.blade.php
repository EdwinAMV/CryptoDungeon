<div wire:poll.3s class="team-chat-widget" x-data="{
    open: false, emojisOpen: false, busy: false, now: Date.now(), timer: null,
    init() { this.timer = setInterval(() => { this.now = Date.now(); }, 1000); },
    destroy() { clearInterval(this.timer); },
    get remaining() { return Math.max(0, Number(this.$refs.cooldown?.dataset.until || 0) - Math.floor(this.now / 1000)); },
    addEmoji(emoji) {
        if (this.busy) return;
        const field = this.$refs.composer;
        const start = field.selectionStart, end = field.selectionEnd;
        if (field.value.length - (end - start) + emoji.length > field.maxLength) return;
        field.setRangeText(emoji, start, end, 'end');
        field.dispatchEvent(new Event('input', { bubbles: true }));
        this.emojisOpen = false;
        field.focus();
    },
    async sendMessage() {
        if (this.busy || this.remaining > 0 || !this.$refs.composer.value.trim()) return;
        this.busy = true;
        try { await this.$wire.send(); }
        finally { this.busy = false; }
    }
}" @keydown.escape.window="if (open) { if (emojisOpen) { emojisOpen = false; $refs.emojiToggle.focus(); } else { open = false; $refs.toggle.focus(); } }">
    @if ($participant)
        <section id="team-chat-panel" x-show="open" x-cloak x-transition.opacity class="team-chat-panel" aria-labelledby="team-chat-title">
            <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-700 px-5 py-4">
                <div class="min-w-0">
                    <h2 id="team-chat-title" class="truncate text-lg font-bold">Chat del equipo</h2>
                    <p class="truncate text-xs text-slate-400">{{ $participant->team->name }} · Supervisado por el anfitrión</p>
                </div>
                <button type="button" @click="emojisOpen = false; open = false; $refs.toggle.focus()" class="rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-amber-300" aria-label="Cerrar chat">✕</button>
            </header>
            <div class="flex min-h-0 flex-1 flex-col-reverse overflow-y-auto p-4" tabindex="0" role="log" aria-label="Mensajes del equipo" aria-live="polite" aria-relevant="additions">
                <ol class="space-y-4">
                    @forelse ($messages as $entry)
                        <li wire:key="chat-message-{{ $entry->id }}" class="text-sm">
                            <div class="flex items-center gap-2">
                                <span class="chat-player-avatar" style="--player-color: {{ $entry->participant->displayColor() }}" aria-hidden="true">{{ mb_strtoupper(mb_substr($entry->participant->display_name, 0, 1)) }}</span>
                                <strong class="min-w-0 break-words" style="color: {{ $entry->participant->displayColor() }}">{{ $entry->participant->display_name }}{{ $entry->participant_id === $participant->id ? ' (tú)' : '' }}</strong>
                                <time datetime="{{ $entry->created_at->toIso8601String() }}" class="ml-auto shrink-0 text-xs text-slate-500">{{ $entry->created_at->format('H:i') }}</time>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap break-words rounded-xl border-l-2 bg-slate-800/70 px-3 py-2 text-slate-200" style="border-left-color: {{ $entry->participant->displayColor() }}">{{ $entry->body }}</p>
                        </li>
                    @empty
                        <li class="py-6 text-center text-sm text-slate-400">Todavía no hay mensajes. Comparte tus ideas con el equipo.</li>
                    @endforelse
                </ol>
            </div>
            <form @submit.prevent="sendMessage()" class="shrink-0 space-y-2 border-t border-slate-700 px-4 py-3">
                <label for="team-chat-message" class="flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $participant->displayColor() }}"></span>Tu mensaje al equipo</label>
                <textarea id="team-chat-message" x-ref="composer" wire:model="message" :readonly="busy" @keydown.enter="if (!$event.shiftKey &amp;&amp; !$event.isComposing &amp;&amp; $event.keyCode !== 229) { $event.preventDefault(); if (!$event.repeat) sendMessage(); }" rows="2" maxlength="500" required class="field resize-none text-sm" placeholder="Escribe al equipo…"></textarea>
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="chat-emoji-anchor" @click.outside="emojisOpen = false">
                        <button type="button" x-ref="emojiToggle" @click="emojisOpen = !emojisOpen" :disabled="busy" :aria-expanded="emojisOpen" aria-controls="team-chat-emojis" aria-label="Elegir emoji" class="rounded-lg px-2 py-1 text-lg hover:bg-slate-800">☺</button>
                        <section id="team-chat-emojis" x-show="emojisOpen" x-cloak x-transition.opacity class="chat-emoji-popover" aria-label="Emojis disponibles">
                            <header class="flex items-center justify-between gap-2 px-3 py-2">
                                <h3 class="text-xs font-semibold text-slate-300">Elige un emoji</h3>
                                <button type="button" @click="emojisOpen = false; $refs.emojiToggle.focus()" aria-label="Cerrar selector de emojis" class="rounded px-2 py-1 text-xs text-slate-400 hover:bg-slate-700 hover:text-white">✕</button>
                            </header>
                            <div class="chat-emoji-grid" tabindex="0" aria-label="Lista de emojis desplazable">
                                @foreach ($emojis as $emoji => $label)
                                    <button type="button" @click="addEmoji(@js($emoji))" :disabled="busy" aria-label="{{ $label }}" title="{{ $label }}" class="chat-emoji-option">{{ $emoji }}</button>
                                @endforeach
                            </div>
                        </section>
                    </div>
                    <span class="text-[10px] text-slate-500">Enter envía · Shift+Enter añade una línea</span>
                </div>
                @error('message')<p role="alert" class="text-xs text-red-300">{{ $message }}</p>@enderror
                <div class="flex items-center justify-between gap-2">
                    <span x-ref="cooldown" data-until="{{ $cooldownUntil }}" class="text-xs text-slate-400" role="status" x-text="remaining > 0 ? 'Espera ' + remaining + ' s' : 'Máximo 500 caracteres'"></span>
                    <button type="submit" class="btn-primary" :disabled="busy || remaining > 0" x-text="busy ? 'Enviando…' : 'Enviar'">Enviar</button>
                </div>
            </form>
        </section>
        <button type="button" x-ref="toggle" @click="emojisOpen = false; open = !open; if (open) $nextTick(() => $refs.composer.focus())" :aria-expanded="open" aria-expanded="false" aria-controls="team-chat-panel" class="team-chat-toggle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linejoin="round" d="M20 11.5a8 8 0 0 1-8 8H5l-4 3v-11a8 8 0 0 1 8-8h3a8 8 0 0 1 8 8Z" transform="translate(1 -1)"/><path stroke-linecap="round" d="M7 9h8M7 13h5"/></svg>
            <span>Chat del equipo</span>
        </button>
    @else
        <p class="sr-only">El chat del equipo ya no está disponible.</p>
    @endif
</div>
