<div wire:ignore x-data="{
    playing: false, volume: .15, failed: false,
    async toggle() {
        const audio = this.$refs.audio;
        this.failed = false;
        if (!audio.paused) { audio.pause(); return; }
        audio.volume = this.volume;
        try { await audio.play(); } catch (_) { this.failed = true; }
    }
}" class="flex flex-wrap items-center gap-3 rounded-xl border border-amber-200/15 bg-stone-900/70 px-4 py-3 text-sm">
    <audio x-ref="audio" loop preload="none" x-on:play="playing = true" x-on:pause="playing = false" x-on:error="failed = true">
        <source src="{{ asset('audio/dungeon-ambient.m4a') }}" type="audio/mp4">
    </audio>
    <button type="button" @click="toggle()" :aria-pressed="playing" class="text-amber-200 hover:text-amber-100" x-text="playing ? '♫ Pausar ambiente' : '♫ Activar ambiente'">♫ Activar ambiente</button>
    <label class="flex items-center gap-2 text-xs text-stone-400">Volumen
        <input type="range" min="0" max="1" step=".05" x-model.number="volume" @input="$refs.audio.volume = volume" class="w-20 accent-amber-300">
    </label>
    <span x-show="failed" x-cloak role="status" class="text-xs text-amber-200">No se pudo reproducir el audio. Puedes continuar jugando.</span>
</div>
