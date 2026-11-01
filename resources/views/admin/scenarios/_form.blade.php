@php
    $flags = old('flags', $scenario->exists
        ? $scenario->redFlags->map(fn ($f) => ['title' => $f->title, 'explanation' => $f->explanation])->all()
        : []);
@endphp

<div>
    <label class="label">Judul</label>
    <input name="title" value="{{ old('title', $scenario->title) }}" class="input" required>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label">Kategori</label>
        <select name="category_id" class="input" required>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(old('category_id', $scenario->category_id) == $category->id)>
                    {{ $category->icon }} {{ $category->name }}
                </option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Kanal</label>
        <input name="channel" value="{{ old('channel', $scenario->channel) }}" class="input" placeholder="WhatsApp, SMS, Instagram..." required>
    </div>
    <div>
        <label class="label">Tingkat kesulitan</label>
        <select name="difficulty" class="input">
            @foreach ([1 => 'Mudah', 2 => 'Sedang', 3 => 'Sulit'] as $value => $text)
                <option value="{{ $value }}" @selected(old('difficulty', $scenario->difficulty) == $value)>{{ $text }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">XP hadiah</label>
        <input type="number" name="xp_reward" min="10" max="1000" value="{{ old('xp_reward', $scenario->xp_reward) }}" class="input" required>
    </div>
</div>

<div>
    <label class="label">Sinopsis</label>
    <textarea name="synopsis" rows="3" class="input" required>{{ old('synopsis', $scenario->synopsis) }}</textarea>
</div>

<div>
    <label class="label">Tanda bahaya (red flags)</label>
    <div id="flags" class="space-y-3">
        @foreach ($flags as $i => $flag)
            <div class="flag-row rounded-xl border border-white/10 p-3">
                <input name="flags[{{ $i }}][title]" value="{{ $flag['title'] ?? '' }}" class="input mb-2" placeholder="Tanda bahaya">
                <textarea name="flags[{{ $i }}][explanation]" rows="2" class="input" placeholder="Penjelasan">{{ $flag['explanation'] ?? '' }}</textarea>
                <button type="button" data-remove class="mt-2 text-xs text-rose-300 hover:underline">Hapus baris</button>
            </div>
        @endforeach
    </div>
    <button type="button" id="add-flag" class="btn btn-ghost mt-3 !py-1.5 text-xs">+ Tambah tanda bahaya</button>
</div>

<script>
(() => {
    const wrap = document.getElementById('flags');
    let i = Date.now(); // indeks unik agar tidak bentrok dengan baris yang sudah ada

    document.getElementById('add-flag').addEventListener('click', () => {
        const row = document.createElement('div');
        row.className = 'flag-row rounded-xl border border-white/10 p-3';
        row.innerHTML = `
            <input name="flags[${i}][title]" class="input mb-2" placeholder="Tanda bahaya">
            <textarea name="flags[${i}][explanation]" rows="2" class="input" placeholder="Penjelasan"></textarea>
            <button type="button" data-remove class="mt-2 text-xs text-rose-300 hover:underline">Hapus baris</button>`;
        i++;
        wrap.appendChild(row);
    });

    wrap.addEventListener('click', (e) => {
        if (e.target.matches('[data-remove]')) e.target.closest('.flag-row').remove();
    });
})();
</script>