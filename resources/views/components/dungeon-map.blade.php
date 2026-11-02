@props(['map', 'title' => 'Plano de la mazmorra'])
<section class="dungeon-atlas" x-data="{ zoom: 1 }">
    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-amber-200/10 px-5 py-4">
        <div>
            <p class="text-xs uppercase tracking-[.25em] text-amber-200/60">Atlas del explorador</p>
            <h2 class="mt-1 text-xl font-bold text-amber-100">{{ $title }}</h2>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" @click="zoom = Math.max(.6, zoom - .2)" class="map-zoom" aria-label="Alejar mapa">−</button>
            <span class="w-12 text-center text-xs text-stone-300" x-text="Math.round(zoom * 100) + '%'">100%</span>
            <button type="button" @click="zoom = Math.min(1.6, zoom + .2)" class="map-zoom" aria-label="Acercar mapa">+</button>
        </div>
    </div>
    <div class="dungeon-viewport" tabindex="0" aria-label="Mapa desplazable de salas y pasillos">
        <div class="dungeon-plan" :style="'--map-unit: ' + (12 * zoom) + 'px'" style="--map-width: {{ $map['width'] }}; --map-height: {{ $map['height'] }}">
            <span class="map-compass" aria-hidden="true">N<br>↑</span>
            @foreach ($map['corridors'] as $index => $corridor)
                <div wire:key="corridor-{{ $index }}" @class(['dungeon-corridor', 'is-open' => $corridor['open']])
                    style="--x: {{ $corridor['left'] }}; --y: {{ $corridor['top'] }}; --w: {{ $corridor['width'] }}; --h: {{ $corridor['height'] }}" aria-hidden="true"></div>
            @endforeach
            @foreach ($map['rooms'] as $room)
                <div wire:key="map-room-{{ $room['id'] }}" @class(['dungeon-room', 'is-locked' => $room['state'] === 'locked', 'is-solved' => $room['state'] === 'solved', 'is-current' => $room['state'] === 'current', 'is-selected' => $room['selected'] ?? false])
                    style="--x: {{ $room['left'] }}; --y: {{ $room['top'] }}; --w: {{ $room['width'] }}; --h: {{ $room['height'] }}">
                    <span class="dungeon-prop prop-{{ $room['decoration'] }}" aria-hidden="true"></span>
                    <span class="dungeon-torch torch-left" aria-hidden="true"></span>
                    <span class="dungeon-torch torch-right" aria-hidden="true"></span>
                    @if ($map['interactive'])
                        <button type="button" wire:click="moveTo({{ $room['id'] }})" wire:loading.attr="disabled" wire:target="moveTo" @disabled(! ($room['can_move'] ?? false)) class="dungeon-room-action" aria-label="Ir a {{ $room['name'] }}, habitación {{ $room['position'] }}{{ $room['state'] === 'locked' ? ', cerrada' : '' }}"></button>
                    @endif
                    <span class="dungeon-room-label">{{ $room['position'] }} · {{ $room['name'] }}</span>
                    <div class="dungeon-tokens" aria-label="Jugadores en {{ $room['name'] }}">
                        @foreach (array_slice($room['players'], 0, 8) as $player)
                            <span wire:key="map-player-{{ $player['id'] }}" @class(['player-token', 'is-away' => ! $player['online'], 'is-self' => $player['self'] ?? false]) style="--token-color: {{ $player['color'] }}" title="{{ $player['name'] }} · {{ $player['team_name'] }}{{ $player['online'] ? '' : ' · Sin señal' }}" tabindex="0" aria-label="{{ $player['name'] }} · {{ $player['team_name'] }}{{ ($player['self'] ?? false) ? ' · Tú' : '' }}{{ $player['online'] ? '' : ' · Sin señal' }}">{{ mb_strtoupper(mb_substr($player['name'], 0, 1)) }}</span>
                        @endforeach
                        @if (count($room['players']) > 8)
                            <span class="player-token token-count" title="{{ implode(', ', array_column(array_slice($room['players'], 8), 'name')) }}">+{{ count($room['players']) - 8 }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
            @foreach ($map['doors'] as $index => $door)
                <div wire:key="door-{{ $index }}" @class(['dungeon-door', 'is-open' => $door['open']])
                    style="--x: {{ $door['left'] }}; --y: {{ $door['top'] }}; --w: {{ $door['width'] }}; --h: {{ $door['height'] }}" aria-hidden="true"></div>
            @endforeach
        </div>
    </div>
    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-amber-200/10 px-5 py-3 text-xs text-stone-300">
        <div class="flex flex-wrap gap-4">
            @foreach ($map['teams'] as $team)
                <span class="inline-flex items-center gap-2"><span class="h-2.5 w-2.5 rounded-full" style="background: {{ $team['color'] }}"></span>{{ $team['name'] }}</span>
            @endforeach
        </div>
        <span>{{ $map['interactive'] ? 'Pulsa una sala contigua abierta para moverte.' : 'Ubicación de los jugadores · actualización cada 3 s' }}</span>
    </div>
    <details class="border-t border-amber-200/10 px-5 py-3 text-sm">
        <summary class="cursor-pointer text-amber-100">Ver ubicaciones de todos los jugadores</summary>
        <ul class="mt-3 grid gap-2 text-xs text-stone-300 sm:grid-cols-2">
            @foreach ($map['rooms'] as $room)
                @foreach ($room['players'] as $player)
                    <li wire:key="map-roster-{{ $player['id'] }}"><span style="color: {{ $player['color'] }}">●</span> {{ $player['name'] }} · {{ $player['team_name'] }} · {{ $room['name'] }}{{ $player['online'] ? '' : ' · Sin señal' }}</li>
                @endforeach
            @endforeach
        </ul>
    </details>
</section>
