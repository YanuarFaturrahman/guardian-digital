@extends('layouts.app')
@section('title', 'Daftar')

@section('content')
<div class="rise mx-auto max-w-lg">
    <div class="card">
        <h1 class="h1 mb-1">Buat akun Guardian</h1>
        <p class="mb-5 text-sm text-slate-400">Gratis dan aman. Kami hanya meminta data seperlunya.</p>

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf

            <div>
                <span class="label">Saya mendaftar sebagai</span>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (['student' => ['🎓', 'Siswa'], 'parent' => ['👪', 'Orang Tua'], 'teacher' => ['🧑‍🏫', 'Guru']] as $value => [$icon, $text])
                        <label class="cursor-pointer">
                            <input type="radio" name="role" value="{{ $value }}" class="peer sr-only"
                                   {{ old('role', 'student') === $value ? 'checked' : '' }}>
                            <div class="rounded-xl border border-white/15 p-3 text-center text-sm transition peer-checked:border-guard-500 peer-checked:bg-guard-500/15">
                                <div class="text-2xl">{{ $icon }}</div>{{ $text }}
                            </div>
                        </label>
                    @endforeach
                </div>
            </div>

            <div>
                <label class="label" for="name">Nama tampilan</label>
                <input class="input" id="name" name="name" value="{{ old('name') }}" required maxlength="60"
                       placeholder="Gunakan nama panggilan, bukan nama lengkap">
                <p class="mt-1 text-xs text-slate-400">Nama ini bisa terlihat di papan peringkat. Lindungi privasimu: pakai nama panggilan.</p>
            </div>

            <div>
                <label class="label" for="email">Email</label>
                <input class="input" id="email" type="email" name="email" value="{{ old('email') }}" required>
            </div>

            {{-- Sekolah: disembunyikan untuk Orang Tua --}}
            <div id="school-box" class="space-y-3 rounded-xl border border-white/10 bg-white/5 p-4">
                <div>
                    <label class="label" for="school_id">Sekolah</label>
                    <select class="input" id="school_id" name="school_id">
                        <option value="">— Pilih sekolah —</option>
                        @foreach ($schools as $school)
                            <option value="{{ $school->id }}" {{ (string) old('school_id') === (string) $school->id ? 'selected' : '' }}>
                                {{ $school->name }}
                            </option>
                        @endforeach
                    </select>

                    <p id="hint-student" class="mt-1 text-xs text-slate-400">
                        Sekolahmu belum ada di daftar? Kosongkan saja. Sekolahmu akan terisi otomatis saat kamu
                        bergabung ke kelas dengan kode dari gurumu. Tanyakan kode kelasnya kepada gurumu.
                    </p>
                </div>

                <div id="teacher-new">
                    <label class="label" for="school_name">Sekolahmu belum ada di daftar? Tulis di sini</label>
                    <input class="input" id="school_name" name="school_name" value="{{ old('school_name') }}"
                           maxlength="120" placeholder="Contoh: SMA Negeri 1 Contoh">
                    <p class="mt-1 text-xs text-slate-400">
                        Tulis <strong>nama lengkap sesuai dokumen resmi sekolah</strong>. Nama ini akan dipilih
                        oleh siswa-siswamu, jadi pastikan benar. Ejaan yang mirip dengan sekolah yang sudah ada
                        otomatis disatukan.
                    </p>
                </div>
            </div>

            {{-- Tanggal lahir hanya untuk siswa (menentukan perlu tidaknya persetujuan orang tua) --}}
            <div id="birth-box">
                <label class="label" for="birth_date">Tanggal lahir</label>
                <input class="input" id="birth_date" type="date" name="birth_date" value="{{ old('birth_date') }}">
                <p class="mt-1 text-xs text-slate-400">Siswa di bawah {{ config('privacy.consent_age') }} tahun memerlukan persetujuan orang tua/wali sebelum bisa bermain.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="label" for="password">Kata sandi</label>
                    <input class="input" id="password" type="password" name="password" required>
                </div>
                <div>
                    <label class="label" for="password_confirmation">Ulangi kata sandi</label>
                    <input class="input" id="password_confirmation" type="password" name="password_confirmation" required>
                </div>
            </div>
            <p class="text-xs text-slate-400">Minimal 8 karakter, mengandung huruf dan angka, dan tidak berupa kata sandi yang umum.</p>

            <label id="adult-box" class="flex items-start gap-2 text-sm text-slate-200">
                <input type="checkbox" name="confirm_adult" value="1" class="mt-1 rounded" {{ old('confirm_adult') ? 'checked' : '' }}>
                <span>Saya berusia 18 tahun atau lebih.</span>
            </label>

            <label class="flex items-start gap-2 text-sm text-slate-200">
                <input type="checkbox" name="accept_privacy" value="1" class="mt-1 rounded" {{ old('accept_privacy') ? 'checked' : '' }} required>
                <span>Saya sudah membaca dan menyetujui <a href="{{ route('privacy.policy') }}" target="_blank" class="text-guard-400 underline">Kebijakan Privasi</a>.</span>
            </label>

            <button class="btn btn-primary w-full">Daftar</button>
        </form>

        <p class="mt-5 text-center text-sm text-slate-400">
            Sudah punya akun? <a href="{{ route('login') }}" class="text-guard-400 hover:underline">Masuk</a>
        </p>
    </div>
</div>

<script>
    (function () {
        const box = document.getElementById('school-box');
        const select = document.getElementById('school_id');
        const teacherNew = document.getElementById('teacher-new');
        const nameInput = document.getElementById('school_name');
        const hintStudent = document.getElementById('hint-student');
        const birthBox = document.getElementById('birth-box');
        const birthInput = document.getElementById('birth_date');
        const adultBox = document.getElementById('adult-box');
        const adultInput = adultBox.querySelector('input');

        function sync() {
            const role = document.querySelector('input[name="role"]:checked')?.value;
            const isParent = role === 'parent';
            const isTeacher = role === 'teacher';

            box.classList.toggle('hidden', isParent);
            select.disabled = isParent;

            teacherNew.classList.toggle('hidden', !isTeacher);
            nameInput.disabled = !isTeacher;
            hintStudent.classList.toggle('hidden', isTeacher);

            const isStudent = !isParent && !isTeacher;
            birthBox.classList.toggle('hidden', !isStudent);
            birthInput.disabled = !isStudent;
            birthInput.required = isStudent;
            adultBox.classList.toggle('hidden', isStudent);
            adultInput.disabled = isStudent;
        }

        document.querySelectorAll('input[name="role"]').forEach((radio) =>
            radio.addEventListener('change', sync));
        sync();
    })();
</script>
@endsection