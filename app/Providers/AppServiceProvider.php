<?php

namespace App\Providers;

use App\Services\DeviceService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(\Illuminate\Http\Request $request)
    {
        Schema::defaultStringLength(191);


        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        //Possibly allow to force an URL, sometimes necessary if behind a reverse proxy
        $proxy_url = getenv('PROXY_URL');
        $proxy_scheme = getenv('PROXY_SCHEME');
        if (! empty($proxy_url)) {
            URL::forceRootUrl($proxy_url);
        }

        if (!empty( env('NGROK_URL') ) && $request->server->has('HTTP_X_ORIGINAL_HOST')) {
            URL::forceScheme('https');
            $this->app['url']->forceRootUrl(env('NGROK_URL'));
        }

        if (! empty($proxy_scheme)) {
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
        $this->app->bind(\App\Services\DeviceService::class, function () {
            return new DeviceService();
        });
    }
}
