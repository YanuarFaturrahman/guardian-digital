<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'kategori' => ['nullable', Rule::in(array_keys(ActivityLog::CATEGORIES))],
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date', 'after_or_equal:dari'],
        ]);

        $logs = ActivityLog::query()
            ->when($filters['kategori'] ?? null, fn ($q, $cat) => $q->where('action', 'like', $cat . '.%'))
            ->when($filters['q'] ?? null, fn ($q, $s) => $q->where(
                fn ($w) => $w->where('description', 'like', "%{$s}%")
                    ->orWhere('actor_name', 'like', "%{$s}%")
                    ->orWhere('ip_address', 'like', "%{$s}%")
            ))
            ->when($filters['dari'] ?? null, fn ($q, $d) => $q->where('created_at', '>=', Carbon::parse($d)->startOfDay()))
            ->when($filters['sampai'] ?? null, fn ($q, $d) => $q->where('created_at', '<=', Carbon::parse($d)->endOfDay()))
            ->latest('id')
            ->simplePaginate(25)
            ->withQueryString();

        return view('admin.system.logs', [
            'logs' => $logs,
            'filters' => $filters,
            'categories' => ActivityLog::CATEGORIES,
        ]);
    }
}
