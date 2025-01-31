<?php

namespace App\Modules\Users\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Foundation\Console\AboutCommand;
use App\Modules\Users\Console\SyncCommand;
use App\Modules\Users\Console\CleanUpCommand;
use App\Modules\Users\Console\CreateCommand;
use App\Modules\Users\Console\RoleCommand;
use App\Modules\Users\Listeners\RouteCollector;
use App\Modules\Users\Models\User;
use App\Modules\Users\Models\UserUsername;
use App\Modules\Users\Models\Session;
use Nwidart\Modules\Facades\Module;

class UsersServiceProvider extends ServiceProvider
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
	public $name = 'users';

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

		if (Module::isEnabled('menus'))
		{
			$this->app['events']->subscribe(new RouteCollector);
		}

		AboutCommand::add('Users', [
			'Accounts' => function()
			{
				$u = (new User)->getTable();
				$uu = (new UserUsername)->getTable();

				$rows = User::query()
					->join($uu, $u . '.id', $uu . '.userid')
					->whereNull($uu . '.dateremoved')
					->count();

				return number_format($rows);
			},
			'Session Lifetime' => function()
			{
				return config('session.lifetime') . ' minutes';
			},
			'Sessions' => function()
			{
				$seconds = config('session.lifetime') * 60;

				$dt = now()->modify('-' . $seconds . ' seconds')->timestamp;

				$rows = Session::query()
					->where('last_activity', '>', $dt)
					->count();

				return number_format($rows);
			},
			'Logged in' => function()
			{
				$seconds = config('session.lifetime') * 60;

				$dt = now()->modify('-' . $seconds . ' seconds')->timestamp;

				$rows = Session::query()
					->whereNotNull('user_id')
					->where('last_activity', '>', $dt)
					->count();

				return number_format($rows);
			},
		]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Register console commands.
	 *
	 * @return void
	 */
	protected function registerConsoleCommands()
	{
		$this->commands([
			SyncCommand::class,
			CleanUpCommand::class,
			RoleCommand::class,
			CreateCommand::class,
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

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return [];
	}
}
