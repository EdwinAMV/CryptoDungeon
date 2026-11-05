<div wire:poll.3s class="space-y-6">
    <header class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <p class="text-xs text-slate-400">Sala {{ $game->code }} · {{ $participant->team->name }}</p>
            <h1 class="mt-1 text-2xl font-bold">Tu expedición</h1>
        </div>
        <div class="flex flex-wrap items-center gap-4 text-sm">
            <p aria-live="polite"><span class="font-semibold text-amber-200">{{ $score }} puntos</span><span class="ml-3 text-slate-400">{{ $solvedCount }} / {{ count($tiles) }} completadas</span></p>
            <a href="#expedition-map" class="rounded-lg border border-slate-700 px-3 py-2 text-slate-200 hover:border-amber-300 focus-visible:outline-2 focus-visible:outline-amber-300">Ver mapa ↓</a>
        </div>
    </header>

    <section id="current-challenge" class="panel scroll-mt-6">
        @if ($feedback)<p role="status" class="mb-5 rounded-lg bg-emerald-950 p-3 text-emerald-200">{{ $feedback }}</p>@endif
        @if ($puzzle && $game->status === \App\Enums\GameStatus::Active)
            <div wire:key="puzzle-{{ $puzzle['id'] }}">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-amber-300">Habitación {{ $puzzle['position'] }} de {{ count($tiles) }}{{ $puzzle['position'] === count($tiles) ? ' · Salida' : '' }}</p>
                        <h2 class="mt-2 text-2xl font-bold">{{ $puzzle['label'] }}</h2>
                    </div>
                    <span class="rounded-full bg-slate-800 px-3 py-1 text-xs text-slate-300">{{ $availablePoints }} puntos en juego</span>
                </div>
                <div class="mt-5 grid gap-6 lg:grid-cols-2 lg:gap-8">
                    <div class="min-w-0">
                        <p class="text-sm text-slate-400">Descifra este mensaje para avanzar.</p>
                        <p class="ciphertext-no-copy my-4 break-words rounded-xl border border-slate-700 bg-slate-950 p-5 font-mono text-lg leading-relaxed tracking-wider" draggable="false" oncopy="return false" oncut="return false" oncontextmenu="return false" ondragstart="return false">{{ $puzzle['ciphertext'] }}</p>
                        @if ($puzzle['cipher'] === 'caesar')
                            <p class="text-sm text-slate-300">Retrocede <strong class="text-amber-200">{{ $puzzle['parameters']['shift'] }} posiciones</strong> en el alfabeto para recuperar cada letra.</p>
                        @elseif ($puzzle['cipher'] === 'simple_substitution')
                            <p class="text-sm text-slate-300">Busca cada letra en «Cifrado» y usa la que tiene encima en «Original».</p>
                            <div class="my-4 overflow-x-auto rounded-lg bg-slate-950 p-3 font-mono text-sm">
                                <p class="whitespace-nowrap">Original: ABCDEFGHIJKLMNOPQRSTUVWXYZ</p>
                                <p class="whitespace-nowrap">Cifrado: &nbsp;{{ $puzzle['parameters']['alphabet'] }}</p>
                            </div>
                        @else
                            <p class="text-sm text-slate-300">Clave: <strong class="font-mono text-amber-200">{{ $puzzle['parameters']['key'] }}</strong>. Repítela sin contar espacios y resta sus letras al mensaje (A=0…Z=25). Si el resultado es negativo, suma 26.</p>
                        @endif
                        <x-puzzle-hints :hints="$hints" :room-id="$puzzle['id']" :teams="$game->mode === 'teams'" />
                    </div>
                    <form wire:submit="submit" class="min-w-0 space-y-3">
                        <x-form-errors />
                        <label for="answer" class="field-label">Mensaje descifrado</label>
                        <textarea id="answer" wire:model="answer" maxlength="300" rows="3" placeholder="Escribe aquí el mensaje descifrado…" required autocomplete="off" class="field"></textarea>
                        <p class="text-xs text-slate-400">Puedes escribir con o sin tildes, espacios y mayúsculas.</p>
                        <button class="btn-primary w-full sm:w-auto" wire:loading.attr="disabled" wire:target="submit">Comprobar respuesta</button>
                    </form>
                </div>
                <details wire:key="puzzle-rules-{{ $puzzle['id'] }}" wire:ignore.self class="mt-5 border-t border-slate-800 pt-4 text-xs text-slate-400">
                    <summary class="cursor-pointer rounded py-1 hover:text-slate-200">Puntuación y reglas</summary>
                    <div class="mt-3 space-y-2 leading-relaxed">
                        <p>{{ $roomIncorrectCount }} fallos · {{ $roomHintPenalty }} puntos de penalización por comodines · {{ $availablePoints }} puntos disponibles.</p>
                        <p>Cada habitación parte de 100 puntos. Cada fallo resta 10 y los comodines añaden su coste. El resultado puede ser negativo.{{ $game->mode === 'teams' ? ' Los fallos y los comodines se comparten con tu equipo.' : '' }}</p>
                        <p>Usamos A–Z, sin Ñ ni tildes. Al comprobar se ignoran espacios, puntuación, tildes y mayúsculas.</p>
                    </div>
                </details>
            </div>
        @elseif ($nextPosition !== null)
            <h2 class="text-2xl font-bold text-amber-200">Habitación {{ $selectedPosition }} completada</h2>
            <p class="mt-4 text-slate-300">Tu equipo ya ha descifrado este mensaje. Avanza por las salas abiertas del mapa hasta la habitación {{ $nextPosition }} para continuar.</p>
        @else
            <h2 class="text-2xl font-bold text-emerald-300">{{ $game->status === \App\Enums\GameStatus::Finished ? '¡Partida completada!' : '¡Habéis salido de la mazmorra!' }}</h2>
            <p class="mt-4 text-slate-300">{{ $game->status === \App\Enums\GameStatus::Finished ? 'La partida ha terminado. Ya puedes salir de la sala.' : 'Vuestro recorrido está completo. Puedes salir de la sala mientras los demás equipos continúan.' }}</p>
        @endif
    </section>

    <div id="expedition-map" class="scroll-mt-6">
        @error('movement')<p role="alert" class="mb-4 rounded-xl bg-red-950 p-4 text-red-200">{{ $message }}</p>@enderror
        <x-dungeon-map :map="$map" title="Tu expedición" />
        <a href="#current-challenge" class="mt-3 inline-block rounded py-1 text-sm text-slate-400 hover:text-amber-200">↑ Volver al acertijo</a>
    </div>

    @if ($participant->team->finished_at || $game->status === \App\Enums\GameStatus::Finished)
        <section class="panel space-y-5">
            <h2 class="text-2xl font-bold">Resumen de intentos</h2>
            <p class="text-slate-300">{{ $attemptCount }} intentos en total: {{ $incorrectCount }} incorrectos y {{ $attemptCount - $incorrectCount }} correctos · {{ $score }} puntos.</p>
            <p class="text-sm text-slate-400">Pistas utilizadas: {{ $hintCount }} · Penalización por pistas: {{ $hintPenalty }} puntos. Ya está incluida en los premios de las habitaciones resueltas.</p>
            <div>
                <h3 class="font-bold text-amber-200">Por jugador</h3>
                <ul class="mt-3 space-y-2 text-sm">
                    @foreach ($playerResults as $result)
                        <li wire:key="player-result-{{ $result->id }}" class="flex flex-wrap justify-between gap-2"><span>{{ $result->display_name }}{{ $result->left_at ? ' · Ha salido' : '' }}</span><span>{{ $result->attempt_count }} intentos · {{ $result->incorrect_count }} fallos · {{ $result->hint_count }} pistas solicitadas</span></li>
                    @endforeach
                </ul>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <caption class="mb-3 text-left font-bold text-amber-200">Por habitación</caption>
                    <thead class="text-slate-400"><tr><th scope="col" class="py-2 pr-4">Habitación</th><th scope="col" class="pr-4">Intentos</th><th scope="col" class="pr-4">Fallos</th><th scope="col" class="pr-4">Pistas</th><th scope="col">Puntos</th></tr></thead>
                    <tbody>
                        @foreach ($roomResults as $result)
                            <tr wire:key="room-result-{{ $result['position'] }}" class="border-t border-slate-800"><th scope="row" class="py-3 pr-4 font-normal">{{ $result['position'] }}{{ $result['solved'] ? '' : ' · Pendiente' }}</th><td class="pr-4">{{ $result['attempts'] }}</td><td class="pr-4">{{ $result['incorrect'] }}</td><td class="pr-4">{{ $result['hints'] }} (−{{ $result['hint_penalty'] }})</td><td>{{ $result['points'] }}</td></tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif

    @if ($game->mode === 'teams')
        <livewire:player.team-chat :key="'team-chat-'.$participant->team_id" />
    @endif

    <details wire:key="player-standings" wire:ignore.self class="panel">
        <summary class="cursor-pointer text-sm font-semibold text-slate-300 hover:text-white">Clasificación y estadísticas</summary>
        <p class="mt-4 text-sm text-slate-400">Tu recorrido: {{ $attemptCount }} intentos · {{ $incorrectCount }} fallos.</p>
        <ul class="mt-4 space-y-3">
            @foreach ($standings as $team)
                <li wire:key="standing-{{ $team->id }}" class="flex flex-wrap justify-between gap-2 border-b border-slate-800 pb-3">
                    <span>{{ $team->name }}</span>
                    <span class="text-slate-300">{{ $team->solved_count }} / {{ count($tiles) }} · {{ $team->score ?? 0 }} puntos · {{ $team->attempt_count }} intentos · {{ $team->incorrect_count }} fallos · {{ $team->hint_count }} pistas</span>
                </li>
            @endforeach
        </ul>
    </details>

    @if ($participant->team->finished_at || $game->status === \App\Enums\GameStatus::Finished)
        <x-leave-room :completed="true" />
    @else
        <details wire:key="player-room-options" wire:ignore.self class="text-sm text-slate-500">
            <summary class="cursor-pointer py-2 hover:text-slate-300">Opciones de la sala</summary>
            <div class="mt-2"><x-leave-room /></div>
        </details>
    @endif
</div>
