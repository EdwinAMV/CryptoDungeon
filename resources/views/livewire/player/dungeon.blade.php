<div wire:poll.3s class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-sm text-amber-300">Sala {{ $game->code }} · {{ $participant->team->name }}</p>
            <h1 class="mt-2 text-3xl font-bold">Descifra la mazmorra</h1>
        </div>
        <p aria-live="polite">{{ $solvedCount }} / {{ count($tiles) }} habitaciones · {{ $score }} puntos</p>
    </header>

    <x-leave-room :completed="$participant->team->finished_at !== null || $game->status === \App\Enums\GameStatus::Finished" />
    <p class="text-sm text-slate-400">{{ $attemptCount }} intentos · {{ $incorrectCount }} respuestas incorrectas</p>
    @error('movement')<p role="alert" class="rounded-xl bg-red-950 p-4 text-red-200">{{ $message }}</p>@enderror
    <x-dungeon-map :map="$map" title="Tu expedición" />

    <section class="panel">
            @if ($feedback)<p role="status" class="mb-5 rounded-lg bg-emerald-950 p-3 text-emerald-200">{{ $feedback }}</p>@endif
            @if ($puzzle && $game->status === \App\Enums\GameStatus::Active)
                <div wire:key="puzzle-{{ $puzzle['id'] }}">
                    <p class="text-sm text-amber-300">Habitación {{ $puzzle['position'] }}{{ $puzzle['position'] === count($tiles) ? ' · Salida' : '' }}</p>
                    <h2 class="mt-2 text-2xl font-bold">{{ $puzzle['label'] }}</h2>
                    <p class="mt-4 text-slate-300">Descifra el mensaje completo para abrir la siguiente habitación.</p>
                    <p class="mt-3 text-sm text-amber-200">Esta habitación: {{ $roomIncorrectCount }} fallos · {{ $availablePoints }} puntos disponibles. Cada fallo resta 10 de los 100 puntos; la puntuación puede ser negativa.{{ $game->mode === 'teams' ? ' Los fallos de todo el equipo se acumulan.' : '' }}</p>
                    <p class="ciphertext-no-copy my-6 break-words rounded-xl border border-slate-700 bg-slate-950 p-5 font-mono text-lg leading-relaxed tracking-wider" draggable="false" oncopy="return false" oncut="return false" oncontextmenu="return false" ondragstart="return false">{{ $puzzle['ciphertext'] }}</p>
                    @if ($puzzle['cipher'] === 'caesar')
                        <p class="text-sm text-slate-300">Cada letra original avanzó {{ $puzzle['parameters']['shift'] }} posiciones en el alfabeto. Para descifrar, retrocede ese desplazamiento.</p>
                    @elseif ($puzzle['cipher'] === 'simple_substitution')
                        <p class="text-sm text-slate-300">Cada letra original se sustituyó por la letra que ocupa su posición en el alfabeto cifrado.</p>
                        <div class="my-4 overflow-x-auto rounded-lg bg-slate-950 p-3 font-mono text-sm">
                            <p class="whitespace-nowrap">Original: ABCDEFGHIJKLMNOPQRSTUVWXYZ</p>
                            <p class="whitespace-nowrap">Cifrado: &nbsp;{{ $puzzle['parameters']['alphabet'] }}</p>
                        </div>
                    @else
                        <p class="text-sm text-slate-300">Clave: <strong class="font-mono text-amber-200">{{ $puzzle['parameters']['key'] }}</strong>. Repite la clave sobre las letras del mensaje, sin contar espacios. Con A=0 y Z=25, resta cada letra de la clave y vuelve al inicio del alfabeto si es necesario.</p>
                    @endif
                    <p class="mt-3 text-xs text-slate-400">Usamos A-Z, sin Ñ ni tildes. Al validar, se ignoran espacios, puntuación, tildes y mayúsculas.</p>
                    <form wire:submit="submit" class="mt-6 space-y-4">
                        <x-form-errors />
                        <label for="answer" class="field-label">Mensaje descifrado</label>
                        <textarea id="answer" wire:model="answer" maxlength="300" rows="3" required autocomplete="off" class="field"></textarea>
                        <button class="btn-primary" wire:loading.attr="disabled" wire:target="submit">Comprobar respuesta</button>
                    </form>
                </div>
            @elseif ($nextPosition !== null)
                <h2 class="text-2xl font-bold text-amber-200">Habitación {{ $selectedPosition }} completada</h2>
                <p class="mt-4 text-slate-300">Tu equipo ya ha descifrado este mensaje. Avanza por las salas abiertas del mapa hasta la habitación {{ $nextPosition }} para continuar.</p>
            @else
                <h2 class="text-2xl font-bold text-emerald-300">{{ $game->status === \App\Enums\GameStatus::Finished ? '¡Partida completada!' : '¡Habéis salido de la mazmorra!' }}</h2>
                <p class="mt-4 text-slate-300">{{ $game->status === \App\Enums\GameStatus::Finished ? 'La partida ha terminado. Ya puedes salir de la sala.' : 'Vuestro recorrido está completo. Puedes salir de la sala mientras los demás equipos continúan.' }}</p>
            @endif
    </section>

    @if ($participant->team->finished_at || $game->status === \App\Enums\GameStatus::Finished)
        <section class="panel space-y-5">
            <h2 class="text-2xl font-bold">Resumen de intentos</h2>
            <p class="text-slate-300">{{ $attemptCount }} intentos en total: {{ $incorrectCount }} incorrectos y {{ $attemptCount - $incorrectCount }} correctos · {{ $score }} puntos.</p>
            <div>
                <h3 class="font-bold text-amber-200">Por jugador</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($playerResults as $result)
                        <li wire:key="player-result-{{ $result->id }}" class="flex flex-wrap justify-between gap-2"><span>{{ $result->display_name }}{{ $result->left_at ? ' · Ha salido' : '' }}</span><span>{{ $result->attempt_count }} intentos · {{ $result->incorrect_count }} fallos</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="mb-3 text-left font-bold text-amber-200">Por habitación</caption>
                    <thead class="text-slate-400"><tr><th scope="col" class="py-2 pr-4">Habitación</th><th scope="col" class="pr-4">Intentos</th><th scope="col" class="pr-4">Fallos</th><th scope="col">Puntos</th></tr></thead>
                    <tbody>
                        @foreach ($roomResults as $result)
                            <tr wire:key="room-result-{{ $result['position'] }}" class="border-t border-slate-800"><th scope="row" class="py-3 pr-4 font-normal">{{ $result['position'] }}{{ $result['solved'] ? '' : ' · Pendiente' }}</th><td class="pr-4">{{ $result['attempts'] }}</td><td class="pr-4">{{ $result['incorrect'] }}</td><td>{{ $result['points'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($game->mode === 'teams')
        <livewire:player.team-chat :key="'team-chat-'.$participant->team_id" />
    @endif

    <section class="panel">
        <h2 class="mb-4 text-xl font-bold">Progreso de los equipos</h2>
        <ul class="space-y-3">
            @foreach ($standings as $team)
                <li wire:key="standing-{{ $team->id }}" class="flex flex-wrap justify-between gap-2 border-b border-slate-800 pb-3">
                    <span>{{ $team->name }}</span>
                    <span class="text-slate-300">{{ $team->solved_count }} / {{ count($tiles) }} · {{ $team->score ?? 0 }} puntos · {{ $team->attempt_count }} intentos · {{ $team->incorrect_count }} fallos</span>
                </li>
            @endforeach
        </ul>
    </section>
</div>
