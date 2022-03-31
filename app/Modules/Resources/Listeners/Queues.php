<?php

namespace App\Modules\Resources\Listeners;

use App\Modules\Queues\Events\QueueCreated;
use App\Modules\Resources\Events\ResourceMemberCreated;
use App\Modules\Resources\Events\ResourceMemberStatus;

/**
 * Queue listener
 */
class Queues
{
	/**
	 * Register the listeners for the subscriber.
	 *
	 * @param  Illuminate\Events\Dispatcher  $events
	 * @return void
	 */
	public function subscribe($events)
	{
		$events->listen(QueueCreated::class, self::class . '@handleQueueCreated');
	}

	/**
	 * Auto-add group managers to any of the group's queues/resources
	 *
	 * @param   QueueCreated $event
	 * @return  void
	 */
	public function handleQueueCreated(QueueCreated $event)
	{
		$queue = $event->queue;

		if (!$queue || !$queue->group || !$queue->group->cascademanagers)
		{
			return;
		}

		// Create roles as necessary
		if ($queue->scheduler
		 && $queue->scheduler->resource
		 && $queue->scheduler->resource->rolename)
		{
			foreach ($queue->group->managers as $user)
			{
				event($resourcemember = new ResourceMemberStatus($queue->scheduler->resource, $user->user));

				if ($resourcemember->status <= 0)
				{
					throw new \Exception(__METHOD__ . '(): Bad status for `resourcemember` ' . $user->userid . '.' . $queue->scheduler->resource->id);
					continue;
				}

				if ($resourcemember->status == 1  // no role exists
				 || $resourcemember->status == 4) // removed
				{
					event($resourcemember = new ResourceMemberCreated($queue->scheduler->resource, $user->user));
				}
			}
		}
	}
}
