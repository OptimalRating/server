<?php

namespace App\Providers;

use App\CustomObjects\CustomJsonResponse;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
            $this->app->bind('CustomJsonResponse', function (){
            return new CustomJsonResponse();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if (config('app.debug')) {
            error_reporting(E_ALL & ~E_USER_DEPRECATED);
        } else {
            error_reporting(0);
        }
        Schema::defaultStringLength(191);

    }
}
