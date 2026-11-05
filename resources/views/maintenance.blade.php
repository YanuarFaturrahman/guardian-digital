@extends('layouts.app')
@section('title', 'Sedang Dipelihara')

@section('content')
<div class="rise mx-auto max-w-xl py-10 text-center">
    <div class="text-6xl">🚧</div>
    <h1 class="h1 mt-4">Guardian Digital sedang dipelihara</h1>
    <p class="mt-3 text-slate-300">
        {{ filled($message ?? null) ? $message : 'Kami sedang melakukan pembaruan singkat. Silakan kembali beberapa saat lagi.' }}
    </p>
    <div class="mt-6 flex justify-center gap-3">
        @auth
            <form method="POST" action="{{ route('logout') }}">@csrf <button class="btn btn-ghost">Keluar</button></form>
        @else
            <a href="{{ route('login') }}" class="btn btn-ghost">Masuk sebagai admin</a>
        @endauth
    </div>
</div>
@endsection
