<?php

namespace App\Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;
//use Illuminate\Support\Facades\View;
//use Illuminate\Database\Eloquent\Factory;
use Illuminate\Auth\Events\Logout;
use Illuminate\Session\SessionManager;
use App\Modules\Orders\Console\RenewCommand;
use App\Modules\Orders\Console\EmailStatusCommand;
use App\Modules\Orders\Entities\Cart;
use App\Modules\Orders\Listeners\GroupOrders;
use App\Modules\Orders\Listeners\UserOrders;
use App\Modules\Orders\Listeners\RouteCollector;
use App\Modules\Orders\LogProcessors\Orders as OrdersProcessor;
use Nwidart\Modules\Facades\Module;

class OrdersServiceProvider extends ServiceProvider
{
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Module name
	 *
	 * @var string
	 */
	public $name = 'orders';

	/**
	 * Boot the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->registerTranslations();
		$this->registerConfig();
		$this->registerAssets();
		$this->registerViews();
		$this->registerConsoleCommands();

		$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

		if (Module::isEnabled('groups'))
		{
			$this->app['events']->subscribe(new GroupOrders);
		}

		if (Module::isEnabled('users'))
		{
			$this->app['events']->subscribe(new UserOrders);
		}

		if (Module::isEnabled('menus'))
		{
			$this->app['events']->subscribe(new RouteCollector);
		}

		if (Module::isEnabled('history'))
		{
			\App\Modules\History\Models\Log::pushProcessor(new OrdersProcessor);
		}
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app->bind('cart', Cart::class);

		$this->app['events']->listen(Logout::class, function ()
		{
			if ($this->app['config']->get('module.orders.destroy_on_logout'))
			{
				$this->app->make(SessionManager::class)->forget('cart');
				//$this->app->make(Cart::class)->forget();
			}
		});
	}

	/**
	 * Register console commands.
	 *
	 * @return void
	 */
	protected function registerConsoleCommands()
	{
		$this->commands([
			RenewCommand::class,
			EmailStatusCommand::class,
		]);
	}

	/**
	 * Register config.
	 *
	 * @return void
	 */
	protected function registerConfig()
	{
		$this->publishes([
			__DIR__ . '/../Config/config.php' => config_path('module/' . $this->name . '.php'),
		], 'config');

		$this->mergeConfigFrom(
			__DIR__ . '/../Config/config.php', $this->name
		);
	}

	/**
	 * Publish assets
	 *
	 * @return void
	 */
	protected function registerAssets()
	{
		$this->publishes([
			__DIR__ . '/../Resources/assets' => public_path() . '/modules/' . strtolower($this->name) . '/assets',
		], 'config');
	}

	/**
	 * Register views.
	 *
	 * @return void
	 */
	public function registerViews()
	{
		$viewPath = resource_path('views/modules/' . $this->name);

		$sourcePath = __DIR__ . '/../Resources/views';

		$this->publishes([
			$sourcePath => $viewPath
		],'views');

		$this->loadViewsFrom(array_merge(array_map(function ($path)
		{
			return $path . '/modules/' . $this->name;
		}, config('view.paths')), [$sourcePath]), $this->name);

		/*View::composer(
			'users::site.profile', ProfileComposer::class
		);*/
	}

	/**
	 * Register translations.
	 *
	 * @return void
	 */
	public function registerTranslations()
	{
		$langPath = resource_path('lang/modules/' . $this->name);

		if (!is_dir($langPath))
		{
			$langPath = __DIR__ . '/../Resources/lang';
		}

		$this->loadTranslationsFrom($langPath, $this->name);
	}
}
