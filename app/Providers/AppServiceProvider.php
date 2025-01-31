<?php

namespace App\Providers;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The filters base class name.
     *
     * @var array<string, array>
     */
    protected $middleware = [
        'App' => [
            //'permissions'           => 'PermissionMiddleware',
            'auth.admin'            => 'AdminMiddleware',
            'auth.ip'               => 'IpWhitelistMiddleware',
            //'public.checkLocale'    => 'PublicMiddleware',
            //'localizationRedirect'  => 'LocalizationMiddleware',
            //'can'                   => 'Authorization',
        ],
    ];

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerMiddleware($this->app['router']);
    }

    /**
     * Register the filters.
     *
     * @param  Router $router
     * @return void
     */
    public function registerMiddleware(Router $router)
    {
        foreach ($this->middleware as $module => $middlewares)
        {
            foreach ($middlewares as $name => $middleware)
            {
                $class = "{$module}\\Http\\Middleware\\{$middleware}";

                $router->aliasMiddleware($name, $class);
            }
        }
    }
}
