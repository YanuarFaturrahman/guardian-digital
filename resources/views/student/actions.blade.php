@extends('layouts.app')
@section('title', 'Pusat Aksi')

@section('content')
<div class="rise space-y-8">
    <div>
        <h1 class="h1">⚡ Pusat Aksi</h1>
        <p class="text-sm text-slate-400">Tahu, tidak cukup. Lindungi dirimu dan orang lain dengan tindakan nyata.</p>
    </div>

    {{-- Protokol --}}
    <section>
        <h2 class="h2 mb-3">Saat ada yang terasa janggal: JEDA - CEK - LAPOR - BAGIKAN</h2>
        <div class="grid gap-4 md:grid-cols-4">
            @foreach ([
                ['⏸️', 'JEDA', 'Berhenti. Jangan klik, balas, atau transfer. Tarik napas: desakan waktu adalah senjata penipu.'],
                ['🔎', 'CEK', 'Verifikasi lewat jalur resmi yang kamu buka sendiri. Cek tautan, rekening, sumber berita, dan izin usaha.'],
                ['🚨', 'LAPOR', 'Ceritakan ke orang tua atau guru, simpan bukti (tangkapan layar), amankan akun, lalu laporkan.'],
                ['📣', 'BAGIKAN', 'Peringatkan teman dan keluarga lewat jalur aman. Guardian melindungi orang lain.'],
            ] as [$icon, $title, $text])
                <div class="card">
                    <div class="text-3xl">{{ $icon }}</div>
                    <div class="mt-1 font-bold text-guard-400">{{ $title }}</div>
                    <p class="mt-1 text-sm text-slate-300">{{ $text }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Kanal resmi --}}
    <section>
        <h2 class="h2 mb-3">Ke mana melapor?</h2>
        <div class="card overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase text-slate-400">
                    <tr><th class="pb-2">Kasus</th><th class="pb-2">Kanal resmi</th></tr>
                </thead>
                <tbody class="divide-y divide-white/10">
                    <tr><td class="py-2 pr-4">Penipuan transaksi keuangan (rekening perlu diblokir)</td>
                        <td>Indonesia Anti-Scam Centre: <strong>iasc.ojk.go.id</strong></td></tr>
                    <tr><td class="py-2 pr-4">Tanya legalitas atau lapor investasi dan pinjol ilegal</td>
                        <td>Kontak OJK <strong>157</strong>, WhatsApp 081-157-157-157, atau <strong>sipasti.ojk.go.id</strong></td></tr>
                    <tr><td class="py-2 pr-4">Cek rekening atau nomor penjual sebelum transfer</td>
                        <td><strong>cekrekening.id</strong></td></tr>
                    <tr><td class="py-2 pr-4">Cek kebenaran kabar viral</td>
                        <td><strong>turnbackhoax.id</strong> dan situs cek fakta media tepercaya</td></tr>
                    <tr><td class="py-2 pr-4">Tindak pidana siber (peretasan, pemerasan, pencurian identitas)</td>
                        <td>Kepolisian, termasuk kanal patroli siber Polri</td></tr>
                    <tr><td class="py-2 pr-4">Akun media sosial atau game dibajak, akun palsu</td>
                        <td>Fitur "Laporkan" dan pusat bantuan di platform bersangkutan</td></tr>
                </tbody>
            </table>
            <p class="mt-3 text-xs text-slate-400">Alamat dan nomor layanan dapat berubah. Pastikan kamu membuka situs yang benar (ketik sendiri alamatnya) dan tanyakan pada orang tua atau guru bila ragu. Penipu juga sering menyamar sebagai kanal pelaporan.</p>
        </div>
    </section>

    {{-- Checklist per tingkat urgensi --}}
    <section>
        <h2 class="h2 mb-1">Checklist Aksi Keamanan</h2>
        <p class="mb-4 text-sm text-slate-400">
            Kerjakan dari yang paling mendesak. Makin mendesak, makin besar XP-nya. Lakukan di akun aslimu, lalu tandai selesai.
            Ajak orang tua untuk membantu memeriksanya.
        </p>

        {{-- Progres keseluruhan --}}
        <div class="card mb-6">
            <div class="mb-2 flex items-center justify-between text-sm">
                <span class="font-semibold">Progres keamananmu</span>
                <span class="text-slate-300">{{ $totalDone }}/{{ $totalAll }} aksi</span>
            </div>
            <x-progress :value="$totalAll ? (int) round($totalDone / $totalAll * 100) : 0" />
        </div>

        @forelse ($tiers as $tier)
            <div class="mb-8">
                {{-- Judul tingkat --}}
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-2xl border p-4"
                     style="border-color: {{ $tier['color'] }}66; background: {{ $tier['color'] }}14">
                    <div class="min-w-0 grow">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-2xl">{{ $tier['icon'] }}</span>
                            <h3 class="text-lg font-bold" style="color: {{ $tier['color'] }}">{{ $tier['label'] }}</h3>
                            <span class="chip">{{ $tier['when'] }}</span>
                            <span class="chip">⭐ +{{ $tier['xp'] }} XP per aksi</span>
                        </div>
                        <p class="mt-1 text-sm text-slate-300">{{ $tier['desc'] }}</p>
                    </div>
                    <div class="w-40">
                        <div class="mb-1 text-right text-sm font-semibold">{{ $tier['done'] }}/{{ $tier['total'] }} selesai</div>
                        <x-progress :value="$tier['percent']" :color="$tier['color']" />
                    </div>
                </div>

                {{-- Aksi di tingkat ini --}}
                <div class="grid gap-4 md:grid-cols-2">
                    @foreach ($tier['items'] as $action)
                        @php $done = $doneIds->contains($action->id); @endphp
                        <div class="card flex gap-4 {{ $done ? 'border-safe/40 bg-safe/10' : '' }}">
                            <div class="text-3xl">{{ $action->icon }}</div>
                            <div class="grow">
                                <div class="font-semibold">{{ $action->title }}</div>
                                <p class="mt-1 text-sm text-slate-300">{{ $action->description }}</p>
                                <div class="mt-3">
                                    @if ($done)
                                        <span class="chip text-safe">✓ Sudah dilakukan</span>
                                    @else
                                        <form method="POST" action="{{ route('student.actions.complete', $action) }}">
                                            @csrf
                                            <button class="btn btn-primary !py-1.5 text-xs">Sudah saya lakukan (+{{ $action->xp_reward }} XP)</button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <p class="card text-sm text-slate-400">Belum ada aksi keamanan.</p>
        @endforelse
    </section>
</div>
@endsection