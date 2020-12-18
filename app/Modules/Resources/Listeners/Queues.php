<?php

namespace App\Modules\Resources\Listeners;

use App\Modules\Queues\Events\QueueCreated;

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
	 * Plugin that loads module positions within content
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   object   $article  The article object.  Note $article->text is also available
	 * @return  void
	 */
	public function handleQueueCreated(QueueCreated $event)
	{
		$queue = $event->queue;

		if (!$queue)
		{
			return;
		}

		// Create roles as necessary
		if ($queue->scheduler->resource
		 && $queue->scheduler->resource->rolename)
		{
			foreach ($queue->group->managers as $user)
			{
				event($resourcemember = new ResourceMemberStatus($user, $queue->scheduler->resource));

				if ($resourcemember->status <= 0)
				{
					throw new \Exception(__METHOD__ . '(): Bad status for `resourcemember` ' . $user->id);
				}
				elseif ($resourcemember->status == 1 || $resourcemember->status == 4)
				{
					event($resourcemember = new ResourceMemberCreated($user, $queue->scheduler->resource));
				}
			}
		}
	}
}
