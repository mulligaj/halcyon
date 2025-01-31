<?php
namespace App\Modules\ContactReports\Providers;

use Illuminate\Support\ServiceProvider;
use App\Modules\ContactReports\Console\EmailCommentsCommand;
use App\Modules\ContactReports\Console\EmailFollowupsCommand;
use App\Modules\ContactReports\Console\EmailReportsCommand;
use App\Modules\ContactReports\Listeners\GroupReports;
use App\Modules\ContactReports\Listeners\CourseReport;
use App\Modules\ContactReports\LogProcessors\Reports;
use Nwidart\Modules\Facades\Module;

class ContactReportsServiceProvider extends ServiceProvider
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
	public $name = 'contactreports';

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
		$this->registerCommands();

		$this->loadMigrationsFrom(__DIR__ . '/../Database/Migrations');

		if (Module::find('groups') && Module::isEnabled('groups'))
		{
			$this->app['events']->subscribe(new GroupReports);
		}
		if (Module::find('courses') && Module::isEnabled('courses'))
		{
			$this->app['events']->subscribe(new CourseReport);
		}

		if (Module::find('history') && Module::isEnabled('history'))
		{
			\App\Modules\History\Models\Log::pushProcessor(new Reports);
		}
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
	 * Register console commands.
	 *
	 * @return void
	 */
	public function registerCommands()
	{
		$this->commands([
			EmailCommentsCommand::class,
			EmailReportsCommand::class,
			EmailFollowupsCommand::class
		]);
	}
}
