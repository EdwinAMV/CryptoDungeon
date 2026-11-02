<div wire:ignore x-data="{
    tracks: @js($tracks), index: null,
    playing: false, volume: .15, failed: false,
    selectTrack() {
        const choices = this.tracks.map((_, index) => index).filter(index => index !== this.index);
        this.index = choices.length ? choices[Math.floor(Math.random() * choices.length)] : 0;
        this.$refs.audio.src = this.tracks[this.index];
        this.$refs.audio.load();
    },
    async playNext() {
        if (!this.tracks.length) return;
        this.selectTrack();
        await this.play();
    },
    async play() {
        this.failed = false;
        this.$refs.audio.volume = this.volume;
        try { await this.$refs.audio.play(); } catch (_) { this.failed = true; }
    },
    async toggle() {
        const audio = this.$refs.audio;
        if (!audio.paused) { audio.pause(); return; }
        if (this.index === null || this.failed) { await this.playNext(); return; }
        await this.play();
    }
}" class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200/15 bg-stone-900/70 px-4 py-3 text-sm">
    <audio x-ref="audio" preload="none" x-on:play="playing = true" x-on:pause="playing = false" x-on:ended="playNext()" x-on:error="failed = true"></audio>
    <button type="button" @click="toggle()" :aria-pressed="playing" @disabled($tracks === []) class="text-amber-200 hover:text-amber-100 disabled:opacity-50" x-text="playing ? '♫ Pausar ambiente' : '♫ Activar ambiente'">♫ Activar ambiente</button>
    <label class="flex items-center gap-2 text-xs text-stone-400">Volumen
        <input type="range" min="0" max="1" step=".05" value="0.15" x-model.number="volume" @input="$refs.audio.volume = Number($event.target.value)" class="w-20 accent-amber-300">
    </label>
    <span x-show="failed" x-cloak role="status" class="text-xs text-amber-200">No se pudo reproducir el audio. Puedes continuar jugando.</span>
    @if ($tracks === [])<span class="text-xs text-stone-400">No hay pistas disponibles.</span>@endif
</div>
