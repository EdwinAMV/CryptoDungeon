<x-app-layout>
    @if (session('status'))<p role="status" class="panel mb-6">{{ session('status') }}</p>@endif
    <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold">Mis salas</h1>
            <p class="mt-2 text-slate-400">Salas creadas desde este navegador. Conserva sus cookies para seguir gestionándolas.</p>
        </div>
        <a href="{{ route('rooms.create') }}" class="btn-primary">Crear sala</a>
    </div>
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($sessions as $game)
            <a href="{{ route('rooms.lobby', $game) }}" class="panel block transition hover:border-amber-400">
                <p class="text-sm text-slate-400">{{ $game->created_at->format('d/m/Y H:i') }}</p>
                <p class="my-3 font-mono text-3xl font-bold tracking-widest text-amber-300">{{ $game->code }}</p>
                <p>{{ $game->mode === 'teams' ? 'Por equipos' : 'Individual' }} · {{ ['easy' => 'Fácil', 'normal' => 'Normal', 'hard' => 'Difícil'][$game->difficulty] }}</p>
                <p class="mt-2 text-sm text-slate-400">{{ $game->participants_count }} participantes · {{ ['lobby' => 'En el lobby', 'active' => 'En curso', 'finished' => 'Finalizada'][$game->status->value] }}</p>
            </a>
        @empty
            <div class="panel sm:col-span-2 lg:col-span-3">
                <p class="text-lg font-semibold">Tu primera sala empieza aquí.</p>
                <p class="mt-2 text-slate-400">Crea una sala y comparte su código con los alumnos.</p>
            </div>
        @endforelse
    </div>
    <div class="mt-6">{{ $sessions->links() }}</div>
</x-app-layout>
