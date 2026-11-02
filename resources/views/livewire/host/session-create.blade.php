<section class="panel mx-auto max-w-xl">
    <a href="{{ route('rooms.index') }}" class="text-sm text-slate-400 hover:text-white">← Mis salas</a>
    <h1 class="mb-2 mt-5 text-2xl font-bold">Preparar una sala</h1>
    <p class="mb-6 text-slate-400">El código de acceso se generará al crear el lobby.</p>
    <p class="mb-6 text-sm text-amber-200">Podrás gestionar esta sala desde este navegador mientras conserves sus cookies. No necesitas una cuenta.</p>
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
            <select id="difficulty" wire:model.live="difficulty" class="field">
                <option value="easy">Fácil · {{ $puzzleCounts['easy'] }} acertijos</option>
                <option value="normal">Normal · {{ $puzzleCounts['normal'] }} acertijos</option>
                <option value="hard">Difícil · {{ $puzzleCounts['hard'] }} acertijos</option>
            </select>
            <p class="mt-2 text-sm text-slate-400">La dificultad determina la cantidad de acertijos. Todos los niveles incluyen los tres cifrados.</p>
        </div>
        <p class="text-sm text-slate-400">Sin límite de tiempo. César, Sustitución Simple y Vigenère.</p>
        <button class="btn-primary w-full" wire:loading.attr="disabled" wire:target="save">Crear lobby</button>
    </form>
</section>
