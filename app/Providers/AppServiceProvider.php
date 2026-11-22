<?php

namespace App\Providers;

use App\Models\BusinessProfile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

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
        View::composer('*', function ($view) {
            try {
                if (Schema::hasTable('profil_usaha')) {
                    $view->with('shopProfile', BusinessProfile::query()->find(1));
                }
            } catch (Throwable) {
                // Ignore DB connection errors during early bootstrap/migrations
            }
        });
    }
}
