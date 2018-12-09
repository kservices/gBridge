<?php

namespace App\Providers;

use App\Services\DeviceService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

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
        $proxy_schema = getenv('PROXY_SCHEMA');
        if (!empty($proxy_url)) {
            URL::forceRootUrl($proxy_url);
        }
        if (!empty($proxy_schema)) {
            URL::forceSchema($proxy_schema);
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
