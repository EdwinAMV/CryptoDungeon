@props(['completed' => false])
<form action="{{ route('player.leave') }}" method="POST"
    @if (! $completed) onsubmit="return confirm('¿Salir de la sala? Durante la partida no podrás volver a entrar. El progreso de tu equipo se conservará.')" @endif>
    @csrf
    <button type="submit" class="btn-secondary">{{ $completed ? 'Salir de la sala' : 'Abandonar sala' }}</button>
</form>
