<?php

namespace App\Providers;

use App\Models\GeneralSetting;
use App\Models\LogoSetting;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
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
        Paginator::useBootstrap();

        $generalSetting = null;
        $logoSetting = null;

        if (Schema::hasTable('general_settings')) {
            $generalSetting = GeneralSetting::first();
        }

        if (Schema::hasTable('logo_settings')) {
            $logoSetting = LogoSetting::first();
        }

        /** set time zone */
        if ($generalSetting) {
            Config::set('app.timezone', $generalSetting->time_zone);
        }

        /** Share variable at all view */
        View::composer('*', function($view) use ($generalSetting, $logoSetting){
            $view->with(['settings' => $generalSetting, 'logoSetting' => $logoSetting]);
        });
    }
}
