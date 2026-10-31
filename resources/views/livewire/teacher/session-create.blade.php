<section class="panel mx-auto max-w-xl">
    <a href="{{ route('teacher.sessions.index') }}" class="text-sm text-slate-400 hover:text-white">← Mis sesiones</a>
    <h1 class="mb-2 mt-5 text-2xl font-bold">Preparar una sesión</h1>
    <p class="mb-6 text-slate-400">El código de acceso se generará al crear el lobby.</p>
    <x-form-errors />
    <form wire:submit="save" class="space-y-6">
        <div>
            <label for="mode" class="field-label">Modo de participación</label>
            <select id="mode" wire:model="mode" class="field">
                <option value="teams">Por equipos</option>
                <option value="individual">Individual</option>
            </select>
            <p class="mt-2 text-sm text-slate-400">En equipos, tú creas los grupos. En individual, cada alumno juega por su cuenta.</p>
        </div>
        <div>
            <label for="difficulty" class="field-label">Dificultad</label>
            <select id="difficulty" wire:model="difficulty" class="field">
                <option value="easy">Fácil</option>
                <option value="normal">Normal</option>
            </select>
        </div>
        <div>
            <label for="duration" class="field-label">Duración en minutos</label>
            <input id="duration" type="number" min="5" max="120" wire:model="durationMinutes" required class="field">
            <p class="mt-2 text-sm text-slate-400">Entre 5 y 120 minutos. El tiempo empezará al iniciar la partida.</p>
        </div>
        <button class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save">Crear lobby</button>
    </form>
</section>
