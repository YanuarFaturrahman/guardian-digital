@php
    $user = auth()->user();
    $links = match ($user?->role) {
        'student' => [
            ['student.dashboard', 'Beranda'], ['student.scenarios.index', 'Misi'],
            ['student.lessons.index', 'Belajar'], ['student.actions.index', 'Aksi'],
            ['student.badges', 'Lencana'], ['leaderboard', 'Peringkat'],
        ],
        'parent' => [['family.dashboard', 'Pantau Anak'], ['leaderboard', 'Peringkat']],
        'teacher' => [['teacher.dashboard', 'Kelas Saya'], ['leaderboard', 'Peringkat']],
        'school_admin' => [
            ['school.dashboard', 'Ringkasan'], ['users.index', 'Pengguna'], ['leaderboard', 'Peringkat'],
        ],
        'superadmin' => [
            ['admin.dashboard', 'Ringkasan'], ['admin.scenarios.index', 'Skenario'],
            ['admin.lessons.index', 'Belajar'], ['admin.actions.index', 'Aksi'],
            ['admin.categories.index', 'Kategori'], ['admin.badges.index', 'Lencana'],
            ['users.index', 'Pengguna'], ['admin.schools.index', 'Sekolah'],
            ['admin.system.index', 'Sistem'],
            ['leaderboard', 'Peringkat'],
        ],
        default => [],
    };
@endphp

<header class="sticky top-0 z-40 border-b border-white/10 bg-guard-950/80 backdrop-blur">
    <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-3">
        <a href="{{ $user ? route('dashboard') : route('home') }}" class="flex items-center gap-2 text-lg font-bold">
            <span class="text-2xl">🛡️</span>
            <span>Guardian<span class="text-guard-500">Digital</span></span>
        </a>

        @auth
            <nav class="hidden items-center gap-1 lg:flex">
                @foreach ($links as [$route, $label])
                    <a href="{{ route($route) }}"
                       class="rounded-lg {{ $user->role === 'superadmin' ? 'px-2' : 'px-3' }} py-2 text-sm hover:bg-white/10 {{ (url()->current() === route($route) || ($route === 'admin.system.index' && request()->routeIs('admin.system.*'))) ? 'bg-white/10 text-guard-400' : 'text-slate-300' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            <div class="flex items-center gap-3">
                @if ($user->role === 'student')
                    <span class="chip hidden sm:inline-flex">⭐ {{ $user->xp }} XP · Lv {{ $user->levelData()['level'] }}</span>
                @endif
                <span class="hidden text-sm text-slate-300 sm:inline">{{ $user->avatar }} {{ $user->name }}</span>
                
                <a href="{{ route('account.security') }}" class="btn btn-ghost !px-3 !py-1.5 text-xs" title="Keamanan dan privasi akun">🔒 <span class="hidden sm:inline">Akun</span></a>
                <button type="button" class="btn btn-ghost !px-3 !py-1.5 text-xs" data-onboarding-open title="Buka panduan penggunaan">❓ <span class="hidden sm:inline">Panduan</span></button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost !px-3 !py-1.5 text-xs">Keluar</button>
                </form>
                <button class="btn btn-ghost !px-3 !py-1.5 lg:hidden" data-toggle="mobile-menu">☰</button>
            </div>
        @else
            <div class="flex items-center gap-2">
                <a href="{{ route('login') }}" class="btn btn-ghost">Masuk</a>
                @if (\App\Models\Setting::bool('registration_open'))
                    <a href="{{ route('register') }}" class="btn btn-primary">Daftar</a>
                @endif
            </div>
        @endauth
    </div>

    @auth
        <nav id="mobile-menu" class="hidden border-t border-white/10 px-4 py-2 lg:hidden">
            @foreach ($links as [$route, $label])
                <a href="{{ route($route) }}" class="block rounded-lg px-3 py-2 text-sm text-slate-200 hover:bg-white/10">{{ $label }}</a>
            @endforeach
        </nav>
    @endauth
</header>