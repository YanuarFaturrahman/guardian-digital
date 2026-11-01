<form method="POST" action="{{ $action }}" class="space-y-3">
    @csrf
    @if ($method !== 'POST') @method($method) @endif

    <div class="grid gap-3 sm:grid-cols-2">
        <div>
            <label class="label">Kunci adegan</label>
            <input name="node_key" value="{{ $node->node_key }}" class="input" placeholder="mis. start, cek, end_good" required>
        </div>
        <div>
            <label class="label">Jenis</label>
            <select name="kind" class="input">
                @foreach (['story' => 'Story (adegan biasa)', 'good' => 'Akhir baik', 'neutral' => 'Akhir netral', 'bad' => 'Akhir buruk'] as $value => $text)
                    <option value="{{ $value }}" @selected(($node->kind ?? 'story') === $value)>{{ $text }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="label">Pembicara (opsional)</label>
            <input name="speaker" value="{{ $node->speaker }}" class="input">
        </div>
        <div>
            <label class="label">Judul adegan (opsional)</label>
            <input name="title" value="{{ $node->title }}" class="input">
        </div>
    </div>

    <div>
        <label class="label">Isi cerita</label>
        <textarea name="body" rows="5" class="input" required>{{ $node->body }}</textarea>
    </div>
    <div>
        <label class="label">Pelajaran (disarankan diisi pada adegan akhir)</label>
        <textarea name="lesson" rows="2" class="input">{{ $node->lesson }}</textarea>
    </div>

    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="is_start" value="1" @checked($node->is_start)> Jadikan adegan awal
    </label>

    <button class="btn btn-primary !py-1.5 text-sm">Simpan adegan</button>
</form>