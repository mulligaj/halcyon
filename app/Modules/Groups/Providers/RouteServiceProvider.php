<?php

namespace App\Modules\Groups\Providers;

use App\Providers\RoutingServiceProvider as CoreRoutingServiceProvider;

class RouteServiceProvider extends CoreRoutingServiceProvider
{
	/**
	 * The root namespace to assume when generating URLs to actions.
	 * @var string
	 */
	protected $namespace = 'App\Modules\Groups\Http\Controllers';

	/**
	 * @return string
	 */
	protected function getSiteRoute()
	{
		return dirname(__DIR__) . '/Http/siteRoutes.php';
	}

	/**
	 * @return string
	 */
	protected function getAdminRoute()
	{
		return dirname(__DIR__) . '/Http/adminRoutes.php';
	}

	/**
	 * @return string
	 */
	protected function getApiRoute()
	{
		return dirname(__DIR__) . '/Http/apiRoutes.php';
	}

	/**
	 * // [!] Legacy compatibility
	 * 
	 * @return string
	 */
	protected function getWsRoute()
	{
		return dirname(__DIR__) . '/Http/wsRoutes.php';
	}
}
