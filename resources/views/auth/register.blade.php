<x-app-layout>
    <section class="panel mx-auto max-w-md">
        <h1 class="mb-6 text-2xl font-bold">Crear cuenta de profesor</h1>
        <x-form-errors />
        <form action="{{ route('register') }}" method="POST" class="space-y-5">
            @csrf
            <div>
                <label for="name" class="field-label">Nombre</label>
                <input id="name" name="name" autocomplete="name" value="{{ old('name') }}" minlength="2" maxlength="80" required autofocus class="field">
            </div>
            <div>
                <label for="email" class="field-label">Correo electrónico</label>
                <input id="email" name="email" type="email" autocomplete="username" value="{{ old('email') }}" required class="field">
            </div>
            <div>
                <label for="password" class="field-label">Contraseña (mínimo 8 caracteres)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required class="field">
            </div>
            <div>
                <label for="password_confirmation" class="field-label">Repetir contraseña</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required class="field">
            </div>
            <button class="btn-primary w-full">Crear cuenta</button>
        </form>
        <p class="mt-6 text-sm text-slate-400"><a href="{{ route('login') }}" class="text-amber-300 underline">Ya tengo cuenta</a></p>
    </section>
</x-app-layout>
