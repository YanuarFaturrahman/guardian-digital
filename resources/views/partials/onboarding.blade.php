@php
    $user = auth()->user();

    $group = match ($user->role) {
        'student' => 'student',
        'parent' => 'parent',
        'teacher' => 'teacher',
        default => 'admin',
    };

    $slides = config("onboarding.$group", []);
    $levels = config('onboarding.levels', []);
    $xpSources = config('onboarding.xp_sources', []);
    $tiers = \App\Models\SafetyAction::PRIORITIES;

    // Muncul otomatis hanya jika belum pernah dilihat dan tidak sedang diminta mengganti sandi.
    $auto = $user->onboarded_at === null && ! ($user->must_change_password ?? false);
@endphp

@if (count($slides))
<div id="onboarding" class="fixed inset-0 z-[100] hidden" role="dialog" aria-modal="true"
     aria-label="Panduan penggunaan Guardian Digital"
     data-auto="{{ $auto ? 'true' : 'false' }}"
     data-completed="{{ $user->onboarded_at ? 'true' : 'false' }}"
     data-complete-url="{{ route('onboarding.complete') }}"
     data-token="{{ csrf_token() }}">

    <div class="absolute inset-0 bg-guard-950/85 backdrop-blur-sm"></div>

    <div class="relative mx-auto flex h-full max-w-2xl items-center justify-center p-3 sm:p-6">
        <div class="rise flex max-h-full w-full flex-col overflow-hidden rounded-3xl border border-white/15 bg-guard-900 shadow-2xl">

            {{-- Kepala --}}
            <div class="flex items-center gap-3 border-b border-white/10 px-5 py-3">
                <span class="text-sm font-semibold text-guard-400">🛡️ Panduan</span>
                <span data-ob-count class="text-xs text-slate-400"></span>
                <button type="button" data-ob-skip class="ml-auto text-xs text-slate-400 hover:text-white">Lewati ✕</button>
            </div>
            <div class="h-1 bg-white/10"><div data-ob-bar class="h-full bg-guard-500 transition-all" style="width: 0%"></div></div>

            {{-- Isi --}}
            <div data-ob-body class="grow overflow-y-auto px-5 py-5">
                @foreach ($slides as $i => $slide)
                    <section data-ob-step class="{{ $i === 0 ? '' : 'hidden' }} space-y-4">

                        <div class="text-center">
                            <div class="text-5xl">{{ $slide['icon'] }}</div>
                            <h2 class="mt-2 text-xl font-bold sm:text-2xl">{{ $slide['title'] }}</h2>
                            @if (! empty($slide['lead']))
                                <p class="mt-2 text-sm leading-relaxed text-slate-300">{{ $slide['lead'] }}</p>
                            @endif
                        </div>

                        {{-- Kartu kecil --}}
                        @if (! empty($slide['items']))
                            @php
                                $cols = count($slide['items']) === 3 ? 'sm:grid-cols-3' : 'sm:grid-cols-2';
                            @endphp
                            <div class="grid gap-2 {{ $cols }}">
                                @foreach ($slide['items'] as [$itemIcon, $itemTitle, $itemText])
                                    <div class="rounded-xl bg-white/5 p-3 text-center sm:text-left">
                                        <div class="text-2xl">{{ $itemIcon }}</div>
                                        <div class="mt-1 text-sm font-semibold text-guard-400">{{ $itemTitle }}</div>
                                        <div class="mt-0.5 text-xs text-slate-300">{{ $itemText }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Langkah bernomor --}}
                        @if (! empty($slide['steps']))
                            <ol class="space-y-2">
                                @foreach ($slide['steps'] as $n => [$stepTitle, $stepText])
                                    <li class="flex gap-3 rounded-xl bg-white/5 p-3">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-guard-500 text-sm font-bold text-guard-950">{{ $n + 1 }}</span>
                                        <div>
                                            <div class="text-sm font-semibold">{{ $stepTitle }}</div>
                                            <div class="text-xs text-slate-300">{{ $stepText }}</div>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif

                        {{-- Bagian khusus --}}
                        @if (! empty($slide['demo']))
                            @switch($slide['demo'])

                                @case('mission')
                                    @php $m = config('onboarding.demo_mission'); @endphp
                                    <div data-ob-demo class="rounded-2xl border border-guard-500/30 bg-white/5 p-4">
                                        <div class="mb-2 text-xs uppercase tracking-widest text-guard-400">🎮 Coba sendiri (contoh, tidak menambah XP)</div>
                                        <div class="text-xs text-slate-400">Dari: {{ $m['speaker'] }}</div>
                                        <div class="mt-1 whitespace-pre-line rounded-2xl rounded-tl-sm bg-white/10 p-3 text-sm">{{ $m['body'] }}</div>
                                        <p class="mb-2 mt-3 text-xs font-semibold text-guard-400">Apa yang akan kamu lakukan?</p>
                                        <div class="space-y-2">
                                            @foreach ($m['choices'] as $k => $c)
                                                <button type="button" data-ob-choice data-points="{{ $c['points'] }}" data-text="{{ $c['feedback'] }}"
                                                        class="w-full cursor-pointer rounded-xl border border-white/15 bg-white/5 p-3 text-left text-sm transition hover:border-guard-500 hover:bg-guard-500/10">
                                                    <span class="mr-2 font-bold text-guard-400">{{ chr(65 + $k) }}.</span>{{ $c['label'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                        <div data-ob-feedback class="hidden" aria-live="polite"></div>
                                    </div>
                                    @break

                                @case('action')
                                    @php
                                        $a = config('onboarding.demo_action');
                                        $t1 = $tiers[1];
                                    @endphp
                                    <div data-ob-demo class="rounded-2xl border border-guard-500/30 bg-white/5 p-4">
                                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                            <span class="text-xs uppercase tracking-widest text-guard-400">🎮 Coba sendiri (contoh, tidak menambah XP)</span>
                                            <span data-ob-demo-xp class="chip">⭐ 0 XP</span>
                                        </div>

                                        <div class="rounded-xl border p-3" style="border-color: {{ $t1['color'] }}66; background: {{ $t1['color'] }}14">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xl">{{ $t1['icon'] }}</span>
                                                <span class="font-bold" style="color: {{ $t1['color'] }}">{{ $t1['label'] }}</span>
                                                <span class="chip">+{{ $t1['xp'] }} XP per aksi</span>
                                                <span data-ob-demo-count class="ml-auto text-xs font-semibold">0/2 selesai</span>
                                            </div>
                                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/10">
                                                <div data-ob-demo-bar class="h-full rounded-full transition-all" style="width: 0%; background: {{ $t1['color'] }}"></div>
                                            </div>
                                        </div>

                                        <div data-ob-card class="card mt-3 flex gap-3 !p-3">
                                            <div class="text-3xl">{{ $a['icon'] }}</div>
                                            <div class="grow">
                                                <div class="text-sm font-semibold">{{ $a['title'] }}</div>
                                                <p class="mt-1 text-xs text-slate-300">{{ $a['description'] }}</p>
                                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                                    <button type="button" data-ob-action-btn data-xp="{{ $t1['xp'] }}" class="btn btn-primary !py-1.5 text-xs">
                                                        Sudah saya lakukan (+{{ $t1['xp'] }} XP)
                                                    </button>
                                                    <span data-ob-pop class="hidden text-sm font-bold text-guard-400"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @break

                                @case('tiers')
                                    <div class="space-y-2">
                                        @foreach ($tiers as $tier)
                                            <div class="rounded-xl border p-3" style="border-color: {{ $tier['color'] }}66; background: {{ $tier['color'] }}14">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="text-xl">{{ $tier['icon'] }}</span>
                                                    <span class="font-bold" style="color: {{ $tier['color'] }}">{{ $tier['label'] }}</span>
                                                    <span class="chip">{{ $tier['when'] }}</span>
                                                    <span class="chip ml-auto">⭐ +{{ $tier['xp'] }} XP</span>
                                                </div>
                                                <p class="mt-1 text-xs text-slate-300">{{ $tier['desc'] }}</p>
                                            </div>
                                        @endforeach
                                    </div>
                                    @break

                                @case('levels')
                                    <div class="rounded-xl bg-white/5 p-3">
                                        <div class="mb-2 text-sm font-semibold text-guard-400">5 level yang bisa kamu capai</div>
                                        <div class="space-y-1.5">
                                            @foreach ($levels as $n => [$levelName, $levelXp])
                                                <div class="flex items-center gap-3 text-sm">
                                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-guard-500/20 text-xs font-bold text-guard-400">{{ $n + 1 }}</span>
                                                    <span class="grow font-medium">{{ $levelName }}</span>
                                                    <span class="text-xs text-slate-400">mulai {{ number_format($levelXp, 0, ',', '.') }} XP</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="text-sm font-semibold text-guard-400">Dari mana XP didapat?</div>
                                        @foreach ($xpSources as [$srcIcon, $srcTitle, $srcText])
                                            <div class="flex gap-3 rounded-xl bg-white/5 p-3">
                                                <div class="text-2xl">{{ $srcIcon }}</div>
                                                <div>
                                                    <div class="text-sm font-semibold">{{ $srcTitle }}</div>
                                                    <div class="text-xs text-slate-300">{{ $srcText }}</div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    @break

                            @endswitch
                        @endif

                        {{-- Daftar poin --}}
                        @if (! empty($slide['bullets']))
                            <ul class="space-y-1.5">
                                @foreach ($slide['bullets'] as $bullet)
                                    <li class="flex gap-2 text-sm text-slate-200">
                                        <span class="text-guard-400">✓</span><span>{{ $bullet }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        {{-- Catatan --}}
                        @if (! empty($slide['note']))
                            <div class="rounded-xl border border-warn/40 bg-warn/10 p-3 text-xs text-amber-100">
                                💡 {{ $slide['note'] }}
                            </div>
                        @endif

                        {{-- Tombol aksi di slide terakhir --}}
                        @if (! empty($slide['cta']))
                            <div class="flex flex-wrap justify-center gap-3 pt-2">
                                @foreach ($slide['cta'] as [$ctaLabel, $ctaRoute, $ctaClass])
                                    @if ($ctaRoute && \Illuminate\Support\Facades\Route::has($ctaRoute))
                                        <a href="{{ route($ctaRoute) }}" data-ob-done class="btn {{ $ctaClass }}">{{ $ctaLabel }}</a>
                                    @else
                                        <button type="button" data-ob-finish class="btn {{ $ctaClass }}">{{ $ctaLabel }}</button>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                    </section>
                @endforeach
            </div>

            {{-- Kaki --}}
            <div class="flex items-center justify-between gap-3 border-t border-white/10 px-5 py-3">
                <button type="button" data-ob-prev class="btn btn-ghost !px-4 !py-2 text-xs">← Kembali</button>
                <div data-ob-dots class="hidden flex-wrap items-center justify-center gap-1 sm:flex"></div>
                <button type="button" data-ob-next class="btn btn-primary !px-4 !py-2 text-xs">Lanjut →</button>
            </div>
        </div>
    </div>
</div>
@endif