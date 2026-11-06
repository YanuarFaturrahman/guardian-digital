<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Beranda') · Guardian Digital</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2? family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

@auth
    @include('partials.onboarding')
@endauth
<body class="min-h-screen font-sans text-slate-100 antialiased">

    @include('partials.navbar')

    <main class="mx-auto max-w-6xl px-4 py-8">
        @include('partials.flash')
        @yield('content')
    </main>

    <footer class="border-t border-white/10 py-6 text-center text-xs text-slate-400">
        Guardian Digital · Kenali · Pelajari · Lindungi<br>
        Semua skenario adalah simulasi aman. Tidak ada uang atau data nyata yang dipakai.<br>
        <a href="{{ route('privacy.policy') }}" class="underline hover:text-white">Kebijakan Privasi</a>
    </footer>
</body>
</html>
