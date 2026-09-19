<?php

namespace App\Providers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer(['partials.header', 'dashboard'], function ($view): void {
            $authUser = Auth::user();

            $user = $authUser
                ? ['name' => $authUser->name, 'role' => $authUser->role]
                : Cache::remember('shared.active_user.v1', now()->addMinutes(10), function (): array {
                    $user = User::query()->where('status', 'active')->orderBy('name')->first(['name', 'role']);

                    return [
                        'name' => $user?->name ?? 'Administrator',
                        'role' => $user?->role ?? 'Admin',
                    ];
                });

            $lowStockCount = Cache::remember('shared.low_stock_count.v1', now()->addMinutes(10), fn () =>
                Product::whereColumn('current_stock', '<', 'minimum_stock')->where('status', 'active')->count()
            );

            $view->with([
                'currentUserName' => $user['name'],
                'currentUserRole' => $user['role'],
                'currentUserInitial' => mb_strtoupper(mb_substr($user['name'], 0, 1)),
                'notificationCount' => $lowStockCount,
            ]);
        });

        View::composer('*', function ($view): void {
            $view->with('isAdmin', Auth::user()?->role === 'Administrator');
        });
    }
}
