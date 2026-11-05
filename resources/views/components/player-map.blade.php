@props(['map'])
<div wire:key="player-map-widget" x-data="{ mapOpen: false }" @keydown.escape.window="if (mapOpen) mapOpen = false">
    <button type="button" @click="mapOpen = true" class="player-minimap" aria-label="Ampliar mapa de la mazmorra"
        aria-haspopup="dialog" aria-controls="player-map-dialog" :aria-expanded="mapOpen">
        <svg viewBox="0 0 {{ $map['width'] }} {{ $map['height'] }}" class="player-minimap-plan" aria-hidden="true" focusable="false">
            @foreach ($map['corridors'] as $corridor)
                <rect x="{{ $corridor['left'] }}" y="{{ $corridor['top'] }}" width="{{ $corridor['width'] }}" height="{{ $corridor['height'] }}" fill="{{ $corridor['open'] ? '#887958' : '#343b41' }}" />
            @endforeach
            @foreach ($map['rooms'] as $room)
                <rect x="{{ $room['left'] }}" y="{{ $room['top'] }}" width="{{ $room['width'] }}" height="{{ $room['height'] }}"
                    rx=".4" fill="{{ $room['state'] === 'locked' ? '#282f37' : ($room['state'] === 'solved' ? '#486454' : '#89774d') }}"
                    stroke="{{ ($room['selected'] ?? false) ? '#fcd34d' : '#6b6b5e' }}" stroke-width="{{ ($room['selected'] ?? false) ? '.6' : '.25' }}" />
                @foreach (array_slice($room['players'], 0, 8) as $index => $player)
                    <circle cx="{{ $room['center_x'] - 2 + ($index % 4) * 1.3 }}" cy="{{ $room['center_y'] - .6 + intdiv($index, 4) * 1.5 }}" r=".65" fill="{{ $player['color'] }}" />
                @endforeach
            @endforeach
        </svg>
        <span class="player-minimap-caption">Mapa <span aria-hidden="true">⤢</span></span>
    </button>
    <template x-teleport="body">
        <div x-show="mapOpen" x-cloak x-transition.opacity class="player-map-backdrop" @click.self="mapOpen = false">
            <section id="player-map-dialog" role="dialog" aria-modal="true" aria-labelledby="player-map-title" class="player-map-dialog"
                x-trap.inert.noscroll="mapOpen" tabindex="-1">
                <header class="flex shrink-0 items-center justify-between gap-3 border-b border-slate-700 px-4 py-3 sm:px-5">
                    <h2 id="player-map-title" class="font-bold text-amber-100">Mapa de la mazmorra</h2>
                    <button type="button" autofocus @click="mapOpen = false" class="rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-slate-800 focus-visible:outline-2 focus-visible:outline-amber-300" aria-label="Cerrar mapa">Cerrar <span aria-hidden="true">✕</span></button>
                </header>
                <div class="player-map-content">
                    @error('movement')<p role="alert" class="mb-3 rounded-lg bg-red-950 p-3 text-sm text-red-200">{{ $message }}</p>@enderror
                    <x-dungeon-map :map="$map" title="Tu expedición" />
                </div>
                <footer class="shrink-0 border-t border-slate-700 px-4 py-3 text-right sm:px-5">
                    <button type="button" @click="mapOpen = false" class="btn-secondary">Volver al acertijo</button>
                </footer>
            </section>
        </div>
    </template>
</div>
