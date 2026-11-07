<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    public function edit()
    {
        $values = [];
        foreach (array_keys(Setting::DEFAULTS) as $key) {
            $values[$key] = Setting::read($key);
        }

        // Kunci rahasia tidak pernah dikirim ke browser, hanya status tersimpan atau belum
        $values['external_api_key'] = '';

        return view('admin.system.settings', [
            'v' => $values,
            'hasApiKey' => Setting::read('external_api_key') !== '',
            'phpLimit' => ini_get('upload_max_filesize'),
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'maintenance_message' => ['nullable', 'string', 'max:300'],
            'max_upload_kb' => ['required', 'integer', 'min:100', 'max:51200'],
            'backup_keep' => ['required', 'integer', 'min:1', 'max:60'],
            'log_retention_days' => ['required', 'integer', 'min:7', 'max:730'],
            'error_retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'health_retention_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'external_api_url' => ['nullable', 'url:https', 'max:255'],
            'external_api_key' => ['nullable', 'string', 'max:255'],
        ], [
            'max_upload_kb.min' => 'Batas unggah minimal 100 KB.',
            'max_upload_kb.max' => 'Batas unggah maksimal 51200 KB (50 MB).',
            'backup_keep.min' => 'Simpan minimal 1 backup otomatis.',
            'log_retention_days.min' => 'Log disimpan minimal 7 hari.',
            'error_retention_days.min' => 'Catatan error disimpan minimal 1 hari.',
            'health_retention_days.min' => 'Riwayat kesehatan disimpan minimal 1 hari.',
            'external_api_url.url' => 'Alamat API harus berupa URL https yang valid.',
        ]);

        $new = [
            'maintenance_mode' => $request->boolean('maintenance_mode'),
            'maintenance_message' => trim((string) ($data['maintenance_message'] ?? '')),
            'registration_open' => $request->boolean('registration_open'),
            'max_upload_kb' => (int) $data['max_upload_kb'],
            'backup_auto' => $request->boolean('backup_auto'),
            'backup_keep' => (int) $data['backup_keep'],
            'log_retention_days' => (int) $data['log_retention_days'],
            // Kolom baru: bila tidak dikirim (formulir lama), pertahankan nilai yang tersimpan.
            'error_retention_days' => (int) ($data['error_retention_days'] ?? Setting::read('error_retention_days')),
            'health_retention_days' => (int) ($data['health_retention_days'] ?? Setting::read('health_retention_days')),
            'alerts_enabled' => $request->boolean('alerts_enabled'),
            'external_api_enabled' => $request->boolean('external_api_enabled'),
            'external_api_url' => trim((string) ($data['external_api_url'] ?? '')),
        ];

        $changed = [];
        foreach ($new as $key => $value) {
            if (Setting::read($key) !== $value) {
                $changed[$key] = ['dari' => Setting::read($key), 'ke' => $value];
                Setting::put($key, $value);
            }
        }

        // Kunci API: hanya diganti jika diisi, atau dihapus lewat kotak centang
        if (filled($data['external_api_key'] ?? null)) {
            Setting::put('external_api_key', $data['external_api_key']);
            $changed['external_api_key'] = 'diganti';
        } elseif ($request->boolean('clear_external_api_key') && Setting::read('external_api_key') !== '') {
            Setting::put('external_api_key', '');
            $changed['external_api_key'] = 'dihapus';
        }

        if ($changed) {
            $keys = implode(', ', array_keys($changed));
            ActivityLog::record('system.settings_updated', "{$request->user()->name} mengubah pengaturan sistem: {$keys}", null, ['perubahan' => $changed]);

            if (isset($changed['maintenance_mode'])) {
                ActivityLog::record(
                    $new['maintenance_mode'] ? 'system.maintenance_on' : 'system.maintenance_off',
                    'Mode pemeliharaan ' . ($new['maintenance_mode'] ? 'diaktifkan' : 'dimatikan') . " oleh {$request->user()->name}"
                );
            }
        }

        $status = $changed ? 'Pengaturan disimpan.' : 'Tidak ada perubahan.';

        if ($new['maintenance_mode']) {
            $status .= ' Mode pemeliharaan AKTIF: hanya superadmin yang bisa membuka aplikasi.';
        }

        return back()->with('status', $status);
    }
}
