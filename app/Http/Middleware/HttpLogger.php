<?php
namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use App\Modules\History\Models\Log as Logger;

class HttpLogger
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
	 * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
	 */
	public function handle(Request $request, \Closure $next)
	{
		$response = $next($request);

		/*if (app()->has('isAdmin') && app()->get('isAdmin'))
		{
			return $response;
		}*/

		$app = 'ui';

		if ($request->segment(1) == 'api')
		{
			$app = 'api';
		}

		$action = Route::currentRouteAction();
		$cls = $action;
		$method = '';
		if (strstr($action, '@'))
		{
			$action = explode('@', $action);
			$cls = array_shift($action);
			$method = array_pop($action);
		}

		$cls = explode('\\', $cls);
		$cls = end($cls);

		$log = new Logger();
		$log->userid = (auth()->user() ? auth()->user()->id : 0);
		$log->transportmethod = $request->method();
		$log->hostname = $this->getClientHost();
		$log->servername = $this->getClientServer();
		$log->ip = $request->ip();
		// Handle some localhost cases
		$log->ip = $log->ip == '::1' ? '127.0.0.1' : $log->ip;
		if ($log->transportmethod == 'GET' && ($app == 'ui' || $app == 'admin'))
		{
			// Collect some diagnostic info
			$headers = $request->headers->all();
			if (empty($headers))
			{
				// No headers? Collect the user agent string
				$headers['user-agent'] = $request->server('HTTP_USER_AGENT');
			}
			if (isset($headers['cookie']))
			{
				unset($headers['cookie']);
			}
			$log->payload = json_encode($headers);
			$log->uri = '/' . $request->path();
		}
		else
		{
			$all = $request->all();
			foreach ($all as $k => $v)
			{
				if (is_string($v) && strlen($v) > 500)
				{
					$all[$k] = substr($v, 0, 500) . ' ...';
				}
			}
			$log->payload = json_encode($all);
			$log->uri = str_replace($request->root(), '', $request->fullUrl());
		}
		$log->payload = $log->payload ?: '';
		if ($request->has('groupid'))
		{
			$log->groupid = intval($request->input('groupid'));
		}
		elseif ($request->has('group'))
		{
			$log->groupid = intval($request->input('group'));
		}
		if ($request->has('userid') && is_numeric($request->input('userid')))
		{
			$log->targetuserid = intval($request->input('userid'));
		}

		$log->status = 200;
		if (!($response instanceof StreamedResponse)
		 && !($response instanceof BinaryFileResponse))
		{
			$log->status = $response->status();
		}

		$log->app = $app;
		$log->classname = $cls;
		$log->classmethod = $method;
		$log->objectid = '';
		try
		{
			$log->save();
		}
		catch (\Exception $e)
		{
			// Don't break everything if this fails
		}

		return $response;
	}

	/**
	 * Get client server name
	 *
	 * @return  string
	 */
	private function getClientServer(): string
	{
		$servername = '';

		if (isset($_SERVER['SERVER_NAME']))
		{
			$servername = $_SERVER['SERVER_NAME'];
		}
		elseif (isset($_SERVER['HTTP_HOST']))
		{
			$servername = $_SERVER['HTTP_HOST'];
		}
		elseif (function_exists('gethostname'))
		{
			$servername = gethostname();
		}

		return $servername;
	}

	/**
	 * Get client server name
	 *
	 * @return  string
	 */
	private function getClientHost(): string
	{
		$hostname = '';

		if (isset($_SERVER['REMOTE_HOST']))
		{
			$hostname = $_SERVER['REMOTE_HOST'];
		}
		elseif (!isset($_SERVER['REMOTE_ADDR']))
		{
			if (function_exists('gethostname'))
			{
				$hostname = gethostname();
			}
		}

		return $hostname;
	}
}
