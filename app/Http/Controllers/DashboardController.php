<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Dashboard\LoadDashboardStatsAction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, LoadDashboardStatsAction $loadStats): Response
    {
        $user = $request->user();
        assert($user !== null);

        return Inertia::render('Dashboard', [
            'stats' => $loadStats($user),
            'has_api_key' => $user->gemini_api_key_encrypted !== null,
        ]);
    }
}
