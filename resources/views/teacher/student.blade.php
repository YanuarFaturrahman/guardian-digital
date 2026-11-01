@extends('layouts.app')
@section('title', 'Detail ' . $student->name)

@section('content')
<div class="rise mx-auto max-w-4xl">
    <a href="{{ route('teacher.classes.show', $classroom) }}" class="mb-3 inline-block text-sm text-slate-400 hover:text-white">← {{ $classroom->name }}</a>
    @include('partials.student-report')
</div>
@endsection
