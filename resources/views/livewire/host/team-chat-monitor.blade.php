<details wire:poll.3s wire:ignore.self class="panel my-6">
    <summary class="cursor-pointer font-bold text-amber-100">Supervisión de chats</summary>
    <p class="mt-3 text-xs text-slate-400">Solo lectura · Últimos 50 mensajes de cada equipo · Actualización cada 3 segundos</p>
    @if ($teams->isNotEmpty())
        <label for="monitor-team-{{ $gameSessionId }}" class="field-label mt-4">Equipo</label>
        <select id="monitor-team-{{ $gameSessionId }}" wire:change="selectTeam($event.target.value)" class="field">
            @foreach ($teams as $team)
                <option value="{{ $team->id }}" @selected($teamId === $team->id)>{{ $team->name }}</option>
            @endforeach
        </select>
        <div wire:key="monitored-team-{{ $teamId }}" class="mt-4 flex max-h-80 flex-col-reverse overflow-y-auto rounded-xl bg-slate-950 p-4" tabindex="0" role="log" aria-label="Mensajes del equipo seleccionado" aria-live="polite" aria-relevant="additions">
            <ol class="space-y-4">
                @forelse ($messages as $entry)
                    <li wire:key="monitored-message-{{ $entry->id }}" class="text-sm">
                        <div class="flex flex-wrap items-center gap-2">
                            <strong style="color: {{ $entry->participant->displayColor() }}">{{ $entry->participant->display_name }}</strong>
                            <time datetime="{{ $entry->created_at->toIso8601String() }}" class="text-xs text-slate-500">{{ $entry->created_at->format('H:i') }}</time>
                        </div>
                        <p class="mt-1 whitespace-pre-wrap break-words text-slate-200">{{ $entry->body }}</p>
                    </li>
                @empty
                    <li class="text-sm text-slate-400">Este equipo todavía no ha enviado mensajes.</li>
                @endforelse
            </ol>
        </div>
    @else
        <p class="mt-4 text-sm text-slate-400">Los chats aparecerán cuando añadas equipos.</p>
    @endif
</details>
