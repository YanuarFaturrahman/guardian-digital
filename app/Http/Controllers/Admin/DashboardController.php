<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attempt;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', [
            'counts' => User::selectRaw('role, count(*) as total')
                ->groupBy('role')->pluck('total', 'role'),
            'plays' => Attempt::where('status', 'finished')->count(),
            'avgScore' => (int) round(Attempt::where('status', 'finished')->avg('score') ?? 0),
            'recentUsers' => User::latest()->take(8)->get(),
        ]);
    }
}
