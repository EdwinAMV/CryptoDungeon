<x-app-layout>
    <section class="mx-auto max-w-3xl py-12 text-center">
        <p class="mb-5 text-sm font-semibold uppercase tracking-widest text-amber-300">Una aventura de criptografía en el aula</p>
        <h1 class="text-4xl font-black leading-tight sm:text-6xl">Cada mensaje es una puerta.</h1>
        <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-slate-400">Reúne a tu equipo y prepárate para descifrar los secretos de la mazmorra.</p>
        <div class="mt-10 flex flex-wrap justify-center gap-4">
            <a href="{{ route('player.join') }}" class="btn-primary">Entrar con un código</a>
            <a href="{{ route('teacher.sessions.index') }}" class="btn-secondary">Soy profesor</a>
        </div>
    </section>
</x-app-layout>
