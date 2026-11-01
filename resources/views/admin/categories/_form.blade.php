<div>
    <label class="label">Nama kategori</label>
    <input id="c-name" name="name" value="{{ old('name', $category->name) }}" class="input" required>
</div>

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label class="label">Ikon (satu emoji)</label>
        <input id="c-icon" name="icon" value="{{ old('icon', $category->icon) }}" maxlength="16" class="input" placeholder="Tekan Win + . untuk memilih emoji" required>
    </div>
    <div>
        <label class="label">Warna</label>
        <input id="c-color" type="color" name="color" value="{{ old('color', $category->color) }}"
               class="h-11 w-full cursor-pointer rounded-xl border border-white/15 bg-guard-900/70 p-1">
    </div>
</div>

<div>
    <label class="label">Tagline (kalimat singkat)</label>
    <input id="c-tagline" name="tagline" value="{{ old('tagline', $category->tagline) }}" class="input" required>
</div>

<div>
    <label class="label">Deskripsi</label>
    <textarea name="description" rows="4" class="input" required>{{ old('description', $category->description) }}</textarea>
</div>

<div>
    <label class="label">Tips untuk keluarga (opsional)</label>
    <textarea name="family_tip" rows="3" class="input">{{ old('family_tip', $category->family_tip) }}</textarea>
</div>

<div>
    <div class="label">Pratinjau</div>
    <div id="pv-card" class="rounded-2xl border bg-white/5 p-4">
        <div class="flex items-center gap-3">
            <span id="pv-icon" class="text-3xl"></span>
            <div>
                <div id="pv-name" class="font-semibold"></div>
                <div id="pv-tag" class="text-xs text-slate-400"></div>
            </div>
        </div>
    </div>
</div>

<script>
(() => {
    const $ = (id) => document.getElementById(id);

    const render = () => {
        const color = $('c-color').value;
        $('pv-icon').textContent = $('c-icon').value || '❓';
        $('pv-name').textContent = $('c-name').value || 'Nama kategori';
        $('pv-name').style.color = color;
        $('pv-tag').textContent = $('c-tagline').value || 'Tagline kategori';
        $('pv-card').style.borderColor = color + '55';
    };

    ['c-name', 'c-icon', 'c-color', 'c-tagline'].forEach((id) => $(id).addEventListener('input', render));
    render();
})();
</script>