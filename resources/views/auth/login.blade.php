<x-app-layout>
    <section class="panel mx-auto max-w-md">
        <h1 class="text-2xl font-bold">Acceso del profesor</h1>
        <p class="mb-6 mt-2 text-slate-400">Organiza las sesiones de tu aula.</p>
        <x-form-errors />
        <form action="{{ route('login') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label for="email" class="field-label">Correo electrónico</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required autofocus class="field">
            </div>
            <div>
                <label for="password" class="field-label">Contraseña</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required class="field">
            </div>
            <button class="btn-primary w-full">Acceder</button>
        </form>
        <p class="mt-6 text-sm text-slate-400">¿Primera visita? <a href="{{ route('register') }}" class="text-amber-300 underline">Crear cuenta de profesor</a></p>
    </section>
</x-app-layout>
