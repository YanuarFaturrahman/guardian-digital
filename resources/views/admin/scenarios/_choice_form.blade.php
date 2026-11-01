<form method="POST" action="{{ $action }}" class="space-y-2">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <textarea name="label" rows="2" class="input" placeholder="Teks pilihan yang dibaca siswa" required>{{ $choice->label }}</textarea>

    <div class="grid gap-2 sm:grid-cols-3">
        <select name="next_node_id" class="input sm:col-span-2" required>
            <option value="">— pilih adegan tujuan —</option>
            @foreach ($nodeOptions as $id => $key)
                <option value="{{ $id }}" @selected($choice->next_node_id == $id)>{{ $key }}</option>
            @endforeach
        </select>
        <input type="number" name="points" min="-50" max="50" value="{{ $choice->points ?? 0 }}" class="input" required>
    </div>

    <textarea name="feedback" rows="2" class="input" placeholder="Umpan balik setelah memilih">{{ $choice->feedback }}</textarea>

    <button class="btn btn-primary !py-1.5 text-xs">Simpan pilihan</button>
</form>