@php $types = App\Models\Badge::TYPES; @endphp

<div class="grid gap-4 sm:grid-cols-3">
    <div class="sm:col-span-2">
        <label class="label">Nama lencana</label>
        <input id="b-name" name="name" value="{{ old('name', $badge->name) }}" class="input" required>
    </div>
    <div>
        <label class="label">Ikon (satu emoji)</label>
        <input id="b-icon" name="icon" value="{{ old('icon', $badge->icon) }}" maxlength="16" class="input" placeholder="Win + ." required>
    </div>
</div>

<div>
    <label class="label">Deskripsi singkat</label>
    <input id="b-desc" name="description" value="{{ old('description', $badge->description) }}" maxlength="255" class="input" required>
</div>

<div class="grid gap-4 sm:grid-cols-3">
    <div class="sm:col-span-2">
        <label class="label">Jenis syarat</label>
        <select id="b-type" name="type" class="input">
            @foreach ($types as $key => [$label])
                <option value="{{ $key }}" @selected(old('type', $badge->type) === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="label">Batas (threshold)</label>
        <input type="number" name="threshold" min="1" max="1000000" value="{{ old('threshold', $badge->threshold) }}" class="input" required>
    </div>
</div>
<p id="threshold-hint" class="-mt-2 text-xs text-slate-400"></p>

<div id="category-box">
    <label class="label">Kategori</label>
    <select name="category_id" class="input">
        <option value="">— pilih kategori —</option>
        @foreach ($categories as $category)
            <option value="{{ $category->id }}" @selected(old('category_id', $badge->category_id) == $category->id)>
                {{ $category->icon }} {{ $category->name }}
            </option>
        @endforeach
    </select>
</div>

<div>
    <div class="label">Pratinjau (seperti yang dilihat siswa)</div>
    <div class="card mx-auto max-w-xs border-warn/50 bg-warn/10 text-center">
        <div id="pv-icon" class="text-5xl"></div>
        <div id="pv-name" class="mt-2 font-semibold"></div>
        <p id="pv-desc" class="mt-1 text-sm text-slate-300"></p>
    </div>
</div>

<script>
(() => {
    const $ = (id) => document.getElementById(id);
    const hints = @json(collect($types)->map(fn ($t) => $t[1]));

    const render = () => {
        const type = $('b-type').value;
        $('pv-icon').textContent = $('b-icon').value || '❓';
        $('pv-name').textContent = $('b-name').value || 'Nama lencana';
        $('pv-desc').textContent = $('b-desc').value || 'Deskripsi lencana';
        $('threshold-hint').textContent = hints[type] ?? '';
        $('category-box').classList.toggle('hidden', type !== 'category');
    };

    ['b-icon', 'b-name', 'b-desc', 'b-type'].forEach((id) => $(id).addEventListener('input', render));
    render();
})();
</script>