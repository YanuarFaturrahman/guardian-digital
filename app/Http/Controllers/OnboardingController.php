<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OnboardingController extends Controller
{
    /** Tandai panduan sudah dilihat (selesai atau dilewati), supaya tidak muncul otomatis lagi. */
    public function complete(Request $request): Response
    {
        $user = $request->user();

        if (! $user->onboarded_at) {
            $user->forceFill(['onboarded_at' => now()])->save();
        }

        return response()->noContent();
    }
}