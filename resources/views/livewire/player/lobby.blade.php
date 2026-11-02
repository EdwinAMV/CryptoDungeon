<section wire:poll.3s class="mx-auto max-w-xl space-y-6">
    <div class="panel text-center">
        <p class="text-sm font-semibold uppercase tracking-widest text-amber-300">Ya estás dentro</p>
        <h1 class="mt-4 text-3xl font-bold">Hola, {{ $participant->display_name }}</h1>
        <p class="mt-3 text-slate-400">Sala <span class="font-mono text-slate-200">{{ $game->code }}</span> · {{ ['easy' => 'Fácil', 'normal' => 'Normal', 'hard' => 'Difícil'][$game->difficulty] }}</p>
        <div class="my-7 rounded-xl border border-amber-800 bg-amber-950/30 p-5">
            <p class="font-semibold text-amber-200">Esperando al anfitrión</p>
            <p class="mt-2 text-sm text-slate-400">Mantén esta página abierta. Tu plaza está guardada en este navegador.</p>
        </div>
        <h2 class="text-xl font-bold">{{ $participant->team->name }}</h2>
        @if ($game->mode === 'teams')
            <ul class="mt-4 flex flex-wrap justify-center gap-2" aria-label="Miembros de tu equipo">
                @foreach ($teammates as $teammate)
                    <li wire:key="teammate-{{ $teammate->id }}" class="rounded-full bg-slate-800 px-4 py-2 text-sm">{{ $teammate->display_name }}{{ $teammate->id === $participant->id ? ' (tú)' : '' }}</li>
                @endforeach
            </ul>
        @else
            <p class="mt-3 text-sm text-slate-400">Juegas en modo individual.</p>
        @endif
        <form action="{{ route('player.leave') }}" method="POST" class="mt-8">
            @csrf
            <button class="text-sm text-slate-400 underline hover:text-white">Salir como participante</button>
        </form>
    </div>
    @if ($game->mode === 'teams')
        <livewire:player.team-chat :key="'team-chat-'.$participant->team_id" />
    @endif
</section>
