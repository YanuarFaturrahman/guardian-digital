<?php

namespace Database\Factories;

use App\Models\School;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Bawaan: siswa dewasa, email terverifikasi, sudah menyetujui kebijakan privasi
 * (supaya tes tidak tersangkut gerbang persetujuan). Kata sandi: "Password123".
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password123'),
            'remember_token' => Str::random(10),
            'role' => 'student',
            'avatar' => '🦊',
            'xp' => 0,
            'birth_date' => '2000-01-01',
            'privacy_accepted_at' => now(),
            'privacy_policy_version' => config('privacy.policy_version'),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn () => ['email_verified_at' => null]);
    }

    public function student(): static
    {
        return $this->state(fn () => ['role' => 'student']);
    }

    public function parent(): static
    {
        return $this->state(fn () => ['role' => 'parent', 'birth_date' => null]);
    }

    public function teacher(): static
    {
        return $this->state(fn () => ['role' => 'teacher', 'birth_date' => null]);
    }

    public function schoolAdmin(?School $school = null): static
    {
        return $this->state(fn () => [
            'role' => 'school_admin',
            'birth_date' => null,
            'school_id' => $school?->id ?? School::findOrCreateByName('SMA Negeri 1 Contoh')->id,
        ])->withTwoFactor();
    }

    public function superadmin(): static
    {
        return $this->state(fn () => ['role' => 'superadmin', 'birth_date' => null])->withTwoFactor();
    }

    public function withTwoFactor(): static
    {
        return $this->state(fn () => [
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => now(),
            'two_factor_recovery_codes' => [],
        ]);
    }

    /** Siswa di bawah usia persetujuan (belum disetujui orang tua). */
    public function minor(int $age = 14): static
    {
        return $this->state(fn () => [
            'role' => 'student',
            'birth_date' => now()->subYears($age)->subMonths(2)->toDateString(),
            'parental_consent_at' => null,
        ]);
    }

    public function deactivated(): static
    {
        return $this->state(fn () => ['deactivated_at' => now()]);
    }
}
