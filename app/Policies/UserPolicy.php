<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /** Boleh membuat kata sandi sementara untuk $target? */
    public function resetPassword(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    /** Boleh menonaktifkan $target? */
    public function deactivate(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    /** Boleh mengaktifkan kembali $target? */
    public function activate(User $actor, User $target): bool
    {
        return $this->canManage($actor, $target);
    }

    /** Reset verifikasi dua langkah (perangkat hilang): hanya superadmin, tidak untuk diri sendiri. */
    public function resetTwoFactor(User $actor, User $target): bool
    {
        return $actor->role === 'superadmin'
            && ! $target->is($actor)
            && $target->hasTwoFactorEnabled();
    }

    /** Hapus permanen: hanya superadmin, dan tidak untuk superadmin lain atau diri sendiri. */
    public function forceDelete(User $actor, User $target): bool
    {
        return $actor->role === 'superadmin'
            && $target->role !== 'superadmin'
            && ! $target->is($actor);
    }

    /** Aturan dasar mengelola akun orang lain. */
    private function canManage(User $actor, User $target): bool
    {
        // Akun superadmin tidak bisa dikelola lewat fitur ini, termasuk milik sendiri
        if ($target->role === 'superadmin' || $target->is($actor)) {
            return false;
        }

        return match ($actor->role) {
            'superadmin' => true,
            // Admin sekolah hanya untuk pengguna yang ada dalam cakupan sekolahnya
            'school_admin' => User::manageableBy($actor)->whereKey($target->id)->exists(),
            default => false,
        };
    }
}