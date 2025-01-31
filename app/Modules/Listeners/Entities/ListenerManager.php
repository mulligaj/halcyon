<?php

namespace App\Modules\Listeners\Entities;

use Illuminate\Events\Dispatcher;
use Illuminate\Support\Str;
use Illuminate\Support\Fluent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use App\Modules\Listeners\Models\Listener;

class ListenerManager
{
	/**
	 * Container
	 *
	 * @var  Dispatcher
	 */
	public $dispatcher;

	/**
	 * Constructor
	 *
	 * @param   Dispatcher $dispatcher
	 * @return  void
	 */
	public function __construct(Dispatcher $dispatcher)
	{
		$this->dispatcher = $dispatcher;
	}

	/**
	 * Subscribe all published listeners
	 *
	 * @return  void
	 */
	public function subscribe(): void
	{
		foreach ($this->allEnabled() as $listener)
		{
			$this->subscribeListener($listener);
		}
	}

	/**
	 * Get by folder and element
	 *
	 * @param   string  $folder   Listener type
	 * @param   string  $element  Listener element
	 * @return  object  collection
	 */
	public function byType($folder, $element = null)
	{
		$listeners = $this->all()
			->filter(function($value, $key) use ($folder, $element)
			{
				if ($value->folder == $folder)
				{
					return ($value->element == $element);
				}

				return false;
			});

		return $listeners;
	}

	/**
	 * Subscribe the listener
	 *
	 * @param   Listener  $listener
	 * @return  void
	 */
	protected function subscribeListener(Listener $listener): void
	{
		if (!$listener->path)
		{
			return;
		}

		$cls = $listener->className;

		$r = new \ReflectionClass($cls);

		foreach ($r->getMethods(\ReflectionMethod::IS_PUBLIC) as $method)
		{
			$name = $method->getName();

			if ($name == 'subscribe')
			{
				$this->dispatcher->subscribe(new $cls);
			}
			elseif (substr(strtolower($name), 0, 6) == 'handle')
			{
				$event = lcfirst(substr($name, 6));

				$this->dispatcher->listen($event, $cls . '@' . $name);
			}

			config()->set('listener.' . strtolower($listener->folder) . '.' . strtolower($listener->element), $listener->params->all());
		}
	}

	/**
	 * Load all listeners.
	 *
	 * @return  object  Collection
	 */
	public function all()
	{
		static $listeners;

		if (isset($listeners))
		{
			return $listeners;
		}

		$listeners = Schema::hasTable('extensions') ? $this->allByDatabase() : $this->allByFile();

		return $listeners;
	}

	/**
	 * Load published listeners.
	 *
	 * @return  object  Collection
	 */
	public function allEnabled()
	{
		$levels = [];

		if ($user = auth()->user())
		{
			$levels = $user->getAuthorisedViewLevels();
		}

		$listeners = $this->all()
			->filter(function($value, $key) use ($levels)
			{
				if ($value->enabled == 1 && (empty($levels) || in_array($value->access, $levels)))
				{
					return true;
				}

				return false;
			});

		return $listeners;
	}

	/**
	 * Load unpublished listeners.
	 *
	 * @return  object  Collection
	 */
	public function allDisabled()
	{
		$levels = [];

		if ($user = auth()->user())
		{
			$levels = $user->getAuthorisedViewLevels();
		}

		$listeners = $this->all()
			->filter(function($value, $key) use ($levels)
			{
				if (!$value->enabled && (empty($levels) || in_array($value->access, $levels)))
				{
					return true;
				}

				return false;
			});

		return $listeners;
	}

	/**
	 * Load published listeners by database.
	 *
	 * @return  object  Collection
	 */
	public function allByDatabase()
	{
		$listeners = Listener::query()
			->where('type', '=', 'listener')
			->orderBy('ordering', 'asc')
			->get();

		if (!count($listeners))
		{
			$listeners = $this->allByFile();

			if (count($listeners))
			{
				foreach ($listeners as $listener)
				{
					$listener->save();
				}
			}
		}
		
		return $listeners;
	}

	/**
	 * Load published listeners by files.
	 *
	 * @return  object  Collection
	 */
	public function allByFile()
	{
		$files = app('files')->glob(app_path('Listeners') . '/*/*/listener.json');

		$orders = array();

		foreach ($files as $i => $file)
		{
			$data = json_decode(file_get_contents($file));

			$listener = new Listener;
			$listener->type      = 'listener';
			$listener->name      = $data->name;
			$listener->element   = strtolower(basename(dirname($file)));
			$listener->folder    = strtolower(basename(dirname(dirname($file))));

			if (!isset($orders[$listener->folder]))
			{
				$orders[$listener->folder] = 1;
			}

			$listener->enabled   = $data->active;
			$listener->ordering  = (isset($data->order) && $data->order ? $data->order : $orders[$listener->folder]);
			$listener->access    = (isset($data->access) ? $data->access : 1);
			$listener->protected = 1;

			$listeners[] = $listener;

			$orders[$listener->folder]++;
		}

		return collect($listeners);
	}
}
