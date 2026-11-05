<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/** Sesi login aktif (butuh SESSION_DRIVER=database) dan tombol paksa keluar. */
class SessionController extends Controller
{
    public function index(Request $request)
    {
        if (config('session.driver') !== 'database') {
            return view('admin.system.sessions', [
                'supported' => false, 'sessions' => collect(), 'currentId' => null, 'guests' => 0,
            ]);
        }

        $since = now()->subMinutes((int) config('session.lifetime'))->timestamp;

        $sessions = DB::table('sessions')
            ->join('users', 'users.id', '=', 'sessions.user_id')
            ->where('sessions.last_activity', '>=', $since)
            ->select('sessions.id', 'sessions.ip_address', 'sessions.user_agent', 'sessions.last_activity',
                'users.id as uid', 'users.name', 'users.email', 'users.role')
            ->orderByDesc('sessions.last_activity')
            ->limit(200)
            ->get()
            ->map(function ($s) {
                $s->last_seen = Carbon::createFromTimestamp($s->last_activity);
                $s->browser = self::describeAgent($s->user_agent);

                return $s;
            });

        return view('admin.system.sessions', [
            'supported' => true,
            'sessions' => $sessions,
            'currentId' => $request->session()->getId(),
            'guests' => DB::table('sessions')->whereNull('user_id')->where('last_activity', '>=', $since)->count(),
        ]);
    }

    public function destroy(Request $request, string $id)
    {
        if ($id === $request->session()->getId()) {
            return back()->withErrors(['sesi' => 'Ini sesimu sendiri. Gunakan tombol Keluar di pojok kanan atas.']);
        }

        $row = DB::table('sessions')->where('id', $id)->first();

        if (! $row) {
            return back()->withErrors(['sesi' => 'Sesi tidak ditemukan (mungkin sudah berakhir).']);
        }

        DB::table('sessions')->where('id', $id)->delete();

        $target = $row->user_id ? User::find($row->user_id) : null;
        ActivityLog::record(
            'system.session_revoked',
            "{$request->user()->name} mengakhiri sesi " . ($target?->name ?? 'tamu') . " dari IP {$row->ip_address}",
            $target,
        );

        return back()->with('status', 'Sesi diakhiri. Pengguna harus masuk lagi.');
    }

    public function destroyAll(Request $request)
    {
        $me = $request->user();

        $count = DB::table('sessions')
            ->where('id', '!=', $request->session()->getId())
            ->whereNotNull('user_id')
            ->delete();

        // Batalkan juga cookie "Ingat saya" milik semua akun lain
        User::where('id', '!=', $me->id)->update(['remember_token' => null]);

        ActivityLog::record('system.force_logout_all', "{$me->name} memaksa semua pengguna keluar ({$count} sesi diakhiri)");

        return back()->with('status', "{$count} sesi diakhiri. Semua pengguna lain harus masuk lagi.");
    }

    private static function describeAgent(?string $ua): string
    {
        $ua = (string) $ua;

        $os = match (true) {
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone'), str_contains($ua, 'iPad') => 'iOS',
            str_contains($ua, 'Mac OS') => 'macOS',
            str_contains($ua, 'Linux') => 'Linux',
            default => 'OS tak dikenal',
        };

        $browser = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Browser tak dikenal',
        };

        return "{$browser} · {$os}";
    }
}
