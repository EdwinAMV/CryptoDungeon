<section class="panel mx-auto max-w-md">
    <p class="text-sm font-semibold uppercase tracking-widest text-amber-300">Acceso de alumnos</p>
    <h1 class="mb-2 mt-3 text-3xl font-bold">Entra en la aventura</h1>
    <p class="mb-6 text-slate-400">Pide el código de seis cifras a quien creó la sala.</p>
    <x-form-errors />
    <form wire:submit="join" class="space-y-5">
        <div>
            <label for="code" class="field-label">Código de sesión</label>
            <input id="code" wire:model.live.debounce.400ms="code" inputmode="numeric" pattern="[0-9]{6}" minlength="6" maxlength="6" autocomplete="off" placeholder="123456" required autofocus class="field font-mono text-2xl tracking-widest">
        </div>
        <div>
            <label for="displayName" class="field-label">Tu nombre o alias</label>
            <input id="displayName" wire:model="displayName" minlength="2" maxlength="40" autocomplete="nickname" required class="field">
        </div>
        @if ($game?->mode === 'teams')
            <div wire:poll.3s>
                <label for="teamId" class="field-label">Tu equipo</label>
                <select id="teamId" wire:model="teamId" required class="field">
                    <option value="">Selecciona un equipo</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}">{{ $team->name }}</option>
                    @endforeach
                </select>
                @if ($teams->isEmpty())
                    <p class="mt-2 text-sm text-amber-200">El anfitrión todavía no ha creado los equipos.</p>
                @endif
            </div>
        @elseif ($game)
            <p class="text-sm text-slate-400">Esta sesión es individual. Tendrás tu propio recorrido.</p>
        @endif
        <button class="btn-primary w-full" wire:loading.attr="disabled" wire:target="join">Entrar al lobby</button>
    </form>
</section>
