<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Attempt;
use App\Models\Classroom;
use App\Models\Scenario;
use App\Models\User;
use App\Services\BadgeService;
use Illuminate\Database\Seeder;

class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::findOrCreateByName('SMA Negeri 1 Contoh');

        // Semua akun demo sudah terverifikasi dan menyetujui kebijakan privasi (siswa demo dewasa).
        $ok = fn (array $extra = []) => $extra + [
            'email_verified_at' => now(),
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('privacy.policy_version'),
        ];

                User::create([
            'name' => 'Admin Guardian', 'email' => 'admin@guardian.test',
            'password' => 'password', 'role' => 'superadmin', 'avatar' => '👑',
        ] + $ok());

        User::create([
            'name' => 'Admin SMAN 1 Contoh', 'email' => 'adminsekolah@guardian.test',
            'password' => 'password', 'role' => 'school_admin', 'school_id' => $school->id, 'avatar' => '🏫',
        ] + $ok());

        $teacher = User::create([
            'name' => 'Bu Sari (Guru BK)', 'email' => 'guru@guardian.test',
            'password' => 'password', 'role' => 'teacher', 'school_id' => $school->id, 'avatar' => '🦉',
        ] + $ok());

        $parent = User::create([
            'name' => 'Ibu Rani', 'email' => 'ortu@guardian.test',
            'password' => 'password', 'role' => 'parent', 'avatar' => '🌻',
        ] + $ok());

        $classroom = Classroom::create([
            'teacher_id' => $teacher->id, 'name' => 'XI IPS 2', 'join_code' => 'KELAS1',
        ]);

        // [nama, email, avatar, daftar skor per misi sesuai urutan]
        $students = [
            ['Rani', 'rani@guardian.test', '🦊', [100, 90, 80, 100, 70]],
            ['Bima', 'bima@guardian.test', '🐯', [80, 60, 40]],
            ['Salsa', 'salsa@guardian.test', '🐼', [100, 100]],
            ['Dito', 'dito@guardian.test', '🦅', [50]],
            ['Citra', 'citra@guardian.test', '🐬', []],
        ];

        $scenarios = Scenario::orderBy('id')->get();

        foreach ($students as $i => [$name, $email, $avatar, $scores]) {
            $student = User::create([
                'name' => $name, 'email' => $email, 'password' => 'password',
                'role' => 'student', 'school_id' => $school->id, 'avatar' => $avatar,
            ] + $ok(['birth_date' => '2005-05-17']));

            $classroom->students()->attach($student->id);

            if ($i === 0) {
                $parent->children()->attach($student->id); // Ibu Rani menautkan Rani
            }

            $xp = 0;
            foreach ($scores as $k => $score) {
                $scenario = $scenarios[$k];
                $kind = $score >= 80 ? 'good' : 'neutral';
                $gain = (int) round($scenario->xp_reward * $score / 100) + 10;

                Attempt::create([
                    'user_id' => $student->id,
                    'scenario_id' => $scenario->id,
                    'current_node_id' => $scenario->nodes()->where('kind', $kind)->value('id'),
                    'status' => 'finished',
                    'score' => $score,
                    'ending_kind' => $kind,
                    'xp_gained' => $gain,
                    'finished_at' => now()->subDays(count($scores) - $k),
                ]);
                $xp += $gain;
            }

            $student->update(['xp' => $xp]);
            app(BadgeService::class)->evaluate($student->fresh());
        }
    }
}
