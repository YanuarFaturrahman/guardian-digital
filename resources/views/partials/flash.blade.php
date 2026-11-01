@if (session('status'))
    <div class="rise mb-4 rounded-xl border border-safe/40 bg-safe/15 px-4 py-3 text-sm text-emerald-200">
        {{ session('status') }}
    </div>
@endif

@if (session('new_badges'))
    <div class="rise mb-4 rounded-xl border border-warn/50 bg-warn/15 px-4 py-3 text-sm text-amber-100">
        🏅 Lencana baru terbuka: <strong>{{ implode(', ', session('new_badges')) }}</strong>
    </div>
@endif

@if ($errors->any())
    <div class="mb-4 rounded-xl border border-danger/40 bg-danger/15 px-4 py-3 text-sm text-rose-200">
        <ul class="list-disc pl-5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
