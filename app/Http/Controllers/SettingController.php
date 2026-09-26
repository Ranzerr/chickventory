<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        // Cache settings dictionary indefinitely until explicitly updated
        $settings = Cache::rememberForever('system.settings.map', function () {
            return SystemSetting::pluck('value', 'key');
        });

        return view('settings', [
            'title' => 'Settings',
            'settings' => $settings,
        ]);
    }
}