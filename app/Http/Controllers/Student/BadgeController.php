<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Badge;

class BadgeController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        return view('student.badges', [
            'badges' => Badge::with('category')->orderBy('id')->get(),
            'earned' => $user->badges()->get()->keyBy('id'),
        ]);
    }
}
