<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Scenario;
use App\Models\User;

class HomeController extends Controller
{
    public function index()
    {
        // Hanya kategori yang sudah punya minimal satu misi terbit,
        // supaya janji "punya misi sendiri" di halaman depan selalu benar.
        $categories = Category::whereHas(
            'scenarios',
            fn ($q) => $q->where('is_published', true)
        )->get();

        return view('home', [
            'categories' => $categories,
            'scenarioCount' => Scenario::where('is_published', true)->count(),
            'studentCount' => User::where('role', 'student')->count(),
        ]);
    }

        /** Arahkan pengguna ke beranda sesuai perannya. */
    public function dashboard()
    {
        return redirect()->route(match (auth()->user()->role) {
            'student' => 'student.dashboard',
            'parent' => 'family.dashboard',
            'teacher' => 'teacher.dashboard',
            'school_admin' => 'school.dashboard',
            'superadmin' => 'admin.dashboard',
            default => 'home', // peran tidak dikenal tidak diberi halaman admin
        });
    }
}