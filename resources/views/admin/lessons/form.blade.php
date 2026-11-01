@extends('layouts.app')
@section('title', $lesson->exists ? 'Ubah Materi' : 'Materi Baru')

@section('content')
<div class="rise mx-auto max-w-6xl">
    <a href="{{ route('admin.lessons.index') }}" class="text-sm text-slate-400 hover:text-white">← Semua materi</a>
    <h1 class="h1 mb-4 mt-2">{{ $lesson->exists ? 'Ubah Materi' : 'Materi Baru' }}</h1>

    <form method="POST" id="lesson-form" class="card space-y-4"
          action="{{ $lesson->exists ? route('admin.lessons.update', $lesson) : route('admin.lessons.store') }}">
        @csrf
        @if ($lesson->exists) @method('PUT') @endif

        <div class="grid gap-4 md:grid-cols-2">
            <div>
                <label class="label" for="category_id">Kategori</label>
                <select class="input" id="category_id" name="category_id" required>
                    <option value="">— pilih kategori —</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                            {{ (string) old('category_id', $lesson->category_id) === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->icon }} {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label" for="title">Judul</label>
                <input class="input" id="title" name="title" maxlength="120" required
                       value="{{ old('title', $lesson->title) }}">
            </div>
        </div>

        <div>
            <label class="label" for="summary">Ringkasan (satu kalimat, tampil di daftar materi)</label>
            <input class="input" id="summary" name="summary" maxlength="200" required
                   value="{{ old('summary', $lesson->summary) }}">
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div>
                <label class="label" for="read_minutes">Lama baca (menit)</label>
                <input class="input" id="read_minutes" name="read_minutes" type="number" min="1" max="60" required
                       value="{{ old('read_minutes', $lesson->read_minutes) }}">
            </div>
            <div>
                <label class="label" for="xp_reward">XP saat selesai</label>
                <input class="input" id="xp_reward" name="xp_reward" type="number" min="0" max="200" required
                       value="{{ old('xp_reward', $lesson->xp_reward) }}">
            </div>
            <div>
    <label class="label" for="position">Urutan</label>
    <input class="input" id="position" name="position" type="number" min="1" max="250"
           placeholder="Kosongkan = paling akhir"
           value="{{ old('position', $lesson->exists ? $lesson->position : '') }}">
    <p class="mt-1 text-xs text-slate-400">Dihitung per kategori. Materi lain bergeser otomatis.</p>
</div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div>
                <label class="label" for="body">Isi materi (Markdown)</label>
                <textarea class="input font-mono" id="body" name="body" rows="18" required
                          maxlength="20000">{{ old('body', $lesson->body) }}</textarea>
                <details class="mt-2 text-xs text-slate-400">
                    <summary class="cursor-pointer">Contoh penulisan Markdown</summary>
<pre class="mt-2 whitespace-pre-wrap rounded-lg bg-white/5 p-3">## Judul bagian
Paragraf biasa. Kata **tebal** dan *miring*.

- Poin pertama
- Poin kedua

1. Langkah satu
2. Langkah dua

[teks tautan](https://contoh.com)</pre>
                </details>
            </div>
            <div>
                <span class="label">Pratinjau (seperti tampilan siswa)</span>
                <div id="preview" class="lesson-body min-h-[20rem] rounded-xl border border-white/10 bg-white/5 p-4"></div>
            </div>
        </div>

        <div class="flex flex-wrap gap-3">
            <button class="btn btn-primary">{{ $lesson->exists ? 'Simpan perubahan' : 'Tambah materi' }}</button>
            <a href="{{ route('admin.lessons.index') }}" class="btn btn-ghost">Batal</a>
        </div>
    </form>
</div>

<script>
    (function () {
        const body = document.getElementById('body');
        const preview = document.getElementById('preview');
        const token = document.querySelector('#lesson-form input[name="_token"]').value;
        const url = @json(route('admin.lessons.preview'));
        let timer = null;

        function render() {
            const data = new FormData();
            data.append('_token', token);
            data.append('body', body.value);

            fetch(url, { method: 'POST', body: data, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then((r) => (r.ok ? r.text() : Promise.reject()))
                .then((html) => {
                    preview.innerHTML = html || '<p class="text-slate-400">Pratinjau akan tampil di sini.</p>';
                })
                .catch(() => { preview.textContent = 'Pratinjau gagal dimuat.'; });
        }

        body.addEventListener('input', () => {
            clearTimeout(timer);
            timer = setTimeout(render, 400);
        });
        render();
    })();
</script>
@endsection