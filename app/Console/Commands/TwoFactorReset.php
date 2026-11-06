<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\TwoFactorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TwoFactorReset extends Command
{
    protected $signature = 'two-factor:reset {email : Email akun}';

    protected $description = 'Reset verifikasi dua langkah (misalnya superadmin kehilangan ponsel dan kode pemulihan)';

    public function handle(TwoFactorService $twoFactor): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Akun tidak ditemukan.');

            return self::FAILURE;
        }

        $twoFactor->disable($user);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        ActivityLog::record('user.two_factor_reset', "Verifikasi dua langkah {$user->name} ({$user->email}) direset lewat konsol server", $user, [], null);

        $this->info("2FA untuk {$user->email} direset. Akun akan diminta memasangnya ulang saat masuk berikutnya.");

        return self::SUCCESS;
    }
}
