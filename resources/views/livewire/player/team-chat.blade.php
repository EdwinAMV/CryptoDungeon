<section wire:poll.3s class="panel">
    @if ($participant)
        <h2 class="text-xl font-bold">Chat del equipo · {{ $participant->team->name }}</h2>
        <p class="mt-2 text-xs text-slate-400">Solo para los miembros de tu grupo. Últimos 50 mensajes · actualización cada 3 segundos.</p>
        <div class="mt-4 flex max-h-72 min-h-24 flex-col-reverse overflow-y-auto rounded-xl bg-slate-950 p-4" role="log" aria-label="Mensajes del equipo" aria-live="polite" aria-relevant="additions">
            <ol class="space-y-3">
                @forelse ($messages as $entry)
                    <li wire:key="chat-message-{{ $entry->id }}" class="text-sm">
                        <p class="flex items-center gap-3"><strong class="text-amber-200">{{ $entry->participant->display_name }}{{ $entry->participant_id === $participant->id ? ' (tú)' : '' }}</strong><time datetime="{{ $entry->created_at->toIso8601String() }}" class="text-xs text-slate-500">{{ $entry->created_at->format('H:i') }}</time></p>
                        <p class="mt-1 whitespace-pre-wrap break-words text-slate-200">{{ $entry->body }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Todavía no hay mensajes. Comparte tus ideas con el equipo.</li>
                @endforelse
            </ol>
        </div>
        <form wire:submit="send" class="mt-4 space-y-3">
            <label for="team-chat-message" class="field-label">Mensaje al equipo</label>
            <textarea id="team-chat-message" wire:model="message" rows="2" maxlength="500" required class="field" placeholder="¿Cómo podemos resolver este mensaje?"></textarea>
            @error('message')<p role="alert" class="text-sm text-red-300">{{ $message }}</p>@enderror
            <button type="submit" class="btn-primary" wire:loading.attr="disabled" wire:target="send">Enviar mensaje</button>
        </form>
    @else
        <p class="text-sm text-slate-400">El chat del equipo ya no está disponible.</p>
    @endif
</section>
