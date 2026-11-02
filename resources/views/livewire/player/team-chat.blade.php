<div wire:poll.3s class="team-chat-widget" x-data="{ open: false }" @keydown.escape.window="if (open) { open = false; $refs.toggle.focus(); }">
    @if ($participant)
        <section id="team-chat-panel" x-show="open" x-cloak x-transition.opacity class="team-chat-panel" aria-labelledby="team-chat-title">
            <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-700 px-5 py-4">
                <div class="min-w-0">
                    <h2 id="team-chat-title" class="truncate text-lg font-bold">Chat del equipo</h2>
                    <p class="truncate text-xs text-slate-400">{{ $participant->team->name }} · Solo tu grupo</p>
                </div>
                <button type="button" @click="open = false; $refs.toggle.focus()" class="rounded-lg px-3 py-2 text-slate-300 hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-amber-300" aria-label="Cerrar chat">✕</button>
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
            <form wire:submit="send" class="shrink-0 space-y-2 border-t border-slate-700 px-4 py-3">
                <label for="team-chat-message" class="flex items-center gap-2 text-xs text-slate-300"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $participant->displayColor() }}"></span>Tu mensaje al equipo</label>
                <textarea id="team-chat-message" x-ref="composer" wire:model="message" rows="2" maxlength="500" required class="field resize-none text-sm" placeholder="Escribe al equipo…"></textarea>
                @error('message')<p role="alert" class="text-xs text-red-300">{{ $message }}</p>@enderror
                <div class="flex items-center justify-between gap-2">
                    <span class="text-[10px] text-slate-500">Últimos 50 mensajes · cada 3 s</span>
                    <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="send">Enviar</button>
                </div>
            </form>
        </section>
        <button type="button" x-ref="toggle" @click="open = !open; if (open) $nextTick(() => $refs.composer.focus())" :aria-expanded="open" aria-expanded="false" aria-controls="team-chat-panel" class="team-chat-toggle">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" class="h-6 w-6" aria-hidden="true"><path stroke-linejoin="round" d="M20 11.5a8 8 0 0 1-8 8H5l-4 3v-11a8 8 0 0 1 8-8h3a8 8 0 0 1 8 8Z" transform="translate(1 -1)"/><path stroke-linecap="round" d="M7 9h8M7 13h5"/></svg>
            <span>Chat del equipo</span>
        </button>
    @else
        <p class="sr-only">El chat del equipo ya no está disponible.</p>
    @endif
</div>
