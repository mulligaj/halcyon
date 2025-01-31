<?php

namespace App\Modules\Queues\Listeners;

use Illuminate\Events\Dispatcher;
use App\Modules\Users\Events\UserDeleted;
use App\Modules\Queues\Models\User;
use App\Modules\Queues\Models\GroupUser;

/**
 * Queue listener to remove memberships when a user is removed
 */
class RemoveMembershipsForDeletedUser
{
	/**
	 * Register the listeners for the subscriber.
	 *
	 * @param   Dispatcher  $events
	 * @return  void
	 */
	public function subscribe(Dispatcher $events): void
	{
		$events->listen(UserDeleted::class, self::class . '@handleUserDeleted');
	}

	/**
	 * Handle an updated User Request
	 *
	 * @param   UserDeleted  $event
	 * @return  void
	 */
	public function handleUserDeleted(UserDeleted $event): void
	{
		if (!$event->user || !$event->user->id)
		{
			return;
		}

		User::query()
			->where('userid', '=', $event->user->id)
			->delete();

		GroupUser::query()
			->where('userid', '=', $event->user->id)
			->delete();
	}
}
