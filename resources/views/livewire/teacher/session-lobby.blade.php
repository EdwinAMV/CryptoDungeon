<div wire:poll.3s>
    <a href="{{ route('teacher.sessions.index') }}" class="text-sm text-slate-400 hover:text-white">← Mis sesiones</a>
    <div class="my-6 grid gap-6 lg:grid-cols-[1fr_2fr]">
        <section class="panel text-center">
            <h1 class="text-xl font-bold">Lobby de la sesión</h1>
            <p class="mt-5 text-sm text-slate-400">Entra en {{ url('/entrar') }} con el código</p>
            <p class="my-5 font-mono text-4xl font-black tracking-widest text-amber-300 sm:text-5xl">{{ $game->code }}</p>
            <p>{{ $game->mode === 'teams' ? 'Por equipos' : 'Individual' }} · {{ $game->difficulty === 'easy' ? 'Fácil' : 'Normal' }}</p>
            <p class="mt-2 text-slate-400">{{ $game->duration_minutes }} minutos</p>
            <p class="mt-6 text-sm text-amber-200">El inicio de la aventura estará disponible en la siguiente fase.</p>
        </section>
        <section>
            <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-2xl font-bold">{{ $game->mode === 'teams' ? 'Equipos' : 'Participantes' }}</h2>
                <span class="rounded-full bg-slate-800 px-3 py-1 text-sm" aria-live="polite">{{ $game->teams->sum(fn ($team) => $team->participants->count()) }} / 100 alumnos</span>
            </div>
            <x-form-errors />
            @if ($game->mode === 'teams' && $game->status === \App\Enums\GameStatus::Lobby)
                <form wire:submit="addTeam" class="panel mb-5 flex flex-wrap items-end gap-3">
                    <div class="min-w-0 flex-1">
                        <label for="teamName" class="field-label">Nombre del equipo</label>
                        <input id="teamName" wire:model="teamName" minlength="2" maxlength="40" required placeholder="Los criptoexploradores" class="field">
                    </div>
                    <button class="btn-primary" wire:loading.attr="disabled" wire:target="addTeam">Añadir equipo</button>
                </form>
            @endif
            <div class="grid gap-4 sm:grid-cols-2">
                @forelse ($game->teams as $team)
                    <article wire:key="team-{{ $team->id }}" class="panel">
                        <h3 class="font-bold text-amber-200">{{ $team->name }}</h3>
                        <ul class="mt-4 space-y-2 text-sm">
                            @forelse ($team->participants as $player)
                                <li wire:key="participant-{{ $player->id }}" class="flex items-center justify-between gap-2">
                                    <span class="break-words">{{ $player->display_name }}</span>
                                    <span class="text-xs {{ $player->last_seen_at?->gt(now()->subSeconds(20)) ? 'text-emerald-400' : 'text-slate-500' }}">{{ $player->last_seen_at?->gt(now()->subSeconds(20)) ? 'Conectado' : 'Sin señal' }}</span>
                                </li>
                            @empty
                                <li class="text-slate-400">Esperando participantes…</li>
                            @endforelse
                        </ul>
                    </article>
                @empty
                    <p class="panel text-slate-400 sm:col-span-2">{{ $game->mode === 'teams' ? 'Añade equipos para que los alumnos puedan unirse.' : 'Comparte el código. Los alumnos aparecerán aquí al entrar.' }}</p>
                @endforelse
            </div>
            <p class="mt-5 text-xs text-slate-500">Se actualiza cada 3 segundos con la pestaña activa. «Sin señal» indica falta de actividad reciente.</p>
        </section>
    </div>
</div>
