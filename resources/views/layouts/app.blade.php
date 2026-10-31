<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CryptoDungeon' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen bg-slate-950 text-slate-100 antialiased">
    <a href="#content" class="sr-only focus:not-sr-only focus:block focus:p-3">Saltar al contenido</a>
    <header class="border-b border-slate-800">
        <nav class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-4 px-6 py-5" aria-label="Principal">
            <a href="{{ route('home') }}" class="text-xl font-black tracking-tight text-amber-300">CryptoDungeon</a>
            <div class="flex items-center gap-5 text-sm">
                <a href="{{ route('player.join') }}" class="hover:text-amber-300">Entrar como alumno</a>
                @auth
                    <a href="{{ route('teacher.sessions.index') }}" class="hover:text-amber-300">Mis sesiones</a>
                    <form action="{{ route('logout') }}" method="POST">
                        @csrf
                        <button class="text-slate-400 hover:text-white">Salir</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="hover:text-amber-300">Profesor</a>
                @endauth
            </div>
        </nav>
    </header>
    <main id="content" class="mx-auto max-w-6xl px-6 py-10">
        {{ $slot }}
    </main>
    @livewireScripts
</body>
</html>
