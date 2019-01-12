<?php

namespace App\Providers;

use App\Services\DeviceService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        //Possibly allow to force an URL, sometimes necessary if behind a reverse proxy
        $proxy_url    = getenv('PROXY_URL');
        $proxy_scheme = getenv('PROXY_SCHEME');
        if (!empty($proxy_url)) {
            URL::forceRootUrl($proxy_url);
        }
        if (!empty($proxy_scheme)) {
            URL::forceScheme($proxy_scheme);
        }
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('App\Services\DeviceService', function () {
            return new DeviceService();
        });
    }
}
