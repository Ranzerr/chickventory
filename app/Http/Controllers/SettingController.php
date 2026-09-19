<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\View\View;

class SettingController extends Controller
{
    public function index(): View
    {
        $settings = SystemSetting::pluck('value', 'key');

        return view('settings', ['title' => 'Settings', 'settings' => $settings]);
    }
}
