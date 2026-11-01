<div class="card" id="node-{{ $node->id }}">
    <div class="flex flex-wrap items-center justify-between gap-2">
        <div class="flex flex-wrap items-center gap-2">
            <span class="chip font-mono">{{ $node->node_key }}</span>
            @if ($node->is_start)<span class="chip text-guard-400">Awal</span>@endif
            <span class="chip {{ $node->kind === 'good' ? 'text-safe' : ($node->kind === 'bad' ? 'text-danger' : ($node->kind === 'neutral' ? 'text-warn' : '')) }}">{{ $node->kind }}</span>
            <span class="text-sm text-slate-300">{{ $node->title ?? Str::limit($node->body, 40) }}</span>
        </div>
        <form method="POST" action="{{ route('admin.nodes.destroy', $node) }}"
              onsubmit="return confirm('Hapus adegan ini beserta pilihannya?')">
            @csrf @method('DELETE')
            <button class="text-xs text-rose-300 hover:underline">Hapus adegan</button>
        </form>
    </div>

    <details class="mt-3">
        <summary class="cursor-pointer text-sm text-guard-400">Ubah adegan</summary>
        <div class="mt-3">
            @include('admin.scenarios._node_form', [
                'node' => $node,
                'action' => route('admin.nodes.update', $node),
                'method' => 'PUT',
            ])
        </div>
    </details>

    <div class="mt-4 space-y-3">
        <div class="text-xs font-semibold uppercase text-slate-400">Pilihan</div>

        @forelse ($node->choices as $choice)
            <div class="rounded-xl border border-white/10 p-3">
                <div class="flex items-start justify-between gap-3 text-sm">
                    <div>
                        <span class="font-bold text-guard-400">{{ chr(65 + $loop->index) }}.</span> {{ $choice->label }}
                        <div class="mt-1 text-xs text-slate-400">
                            → {{ $nodeOptions[$choice->next_node_id] ?? 'TUJUAN KOSONG' }} ·
                            {{ $choice->points > 0 ? '+' : '' }}{{ $choice->points }} poin
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.choices.destroy', $choice) }}"
                          onsubmit="return confirm('Hapus pilihan ini?')">
                        @csrf @method('DELETE')
                        <button class="text-xs text-rose-300 hover:underline">Hapus</button>
                    </form>
                </div>
                <details class="mt-2">
                    <summary class="cursor-pointer text-xs text-guard-400">Ubah pilihan</summary>
                    <div class="mt-2">
                        @include('admin.scenarios._choice_form', [
                            'choice' => $choice,
                            'action' => route('admin.choices.update', $choice),
                            'method' => 'PUT',
                        ])
                    </div>
                </details>
            </div>
        @empty
            <p class="text-sm text-slate-400">{{ $node->kind === 'story' ? 'Belum ada pilihan.' : 'Adegan akhir tidak memerlukan pilihan.' }}</p>
        @endforelse

        @if ($node->kind === 'story')
            <details class="rounded-xl border border-dashed border-white/15 p-3">
                <summary class="cursor-pointer text-sm text-guard-400">+ Tambah pilihan</summary>
                <div class="mt-3">
                    @include('admin.scenarios._choice_form', [
                        'choice' => new App\Models\ScenarioChoice(),
                        'action' => route('admin.choices.store', $node),
                        'method' => 'POST',
                    ])
                </div>
            </details>
        @endif
    </div>
</div>