<?php

namespace App\Modules\Queues\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Modules\History\Models\Log;
use App\Modules\Queues\Mail\FreeRequested;
use App\Modules\Queues\Models\Queue;
use App\Modules\Queues\Models\GroupUser;
use App\Modules\Queues\Models\User as QueueUser;
use App\Modules\Users\Models\User;
use App\Modules\Groups\Models\Group;

/**
 * This script proccess all new requested groupqueueuser entries
 * Notice State 6 => 0
 */
class EmailFreeRequestedCommand extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'queues:emailfreerequested {--debug : Output emails rather than sending}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Email latest groupqueueuser requests.';

	/**
	 * Execute the console command.
	 */
	public function handle()
	{
		$debug = $this->option('debug') ? true : false;

		$gu = (new GroupUser)->getTable();
		$qu = (new QueueUser)->getTable();
		$q = (new Queue)->getTable();

		$users = GroupUser::query()
			->select($gu . '.*', $qu . '.queueid')
			->join($qu, $qu . '.id', $gu . '.queueuserid')
			->join($q, $q . '.id', $qu . '.queueid')
			->whereIn($qu . '.membertype', [1, 4])
			->where($qu . '.notice', '=', 6)
			->get();

		if (!count($users))
		{
			if ($debug)
			{
				$this->comment('No records to email.');
			}
			return;
		}

		// Group activity by groupid so we can determine when to send the group mail
		$group_activity = array();

		foreach ($users as $user)
		{
			if (!isset($group_activity[$user->groupid]))
			{
				$group_activity[$user->groupid] = array();
			}

			array_push($group_activity[$user->groupid], $user);
		}

		$now = date("U");
		$threshold = 300; // threshold for when considering activity "done"

		foreach ($group_activity as $groupid => $users)
		{
			// Find the latest activity
			$latest = 0;
			foreach ($users as $user)
			{
				if ($user->datetimecreated->format('U') > $latest)
				{
					$latest = $user->datetimecreated->format('U');
				}
			}

			if ($now - $latest >= $threshold)
			{
				$group = Group::find($groupid);

				if (!$group)
				{
					if ($debug)
					{
						$this->error('Could not find group #' . $groupid);
					}
					continue;
				}

				$user_activity = array();
				foreach ($users as $gquser)
				{
					$queueuser = $gquser->queueuser;

					if (!isset($user_activity[$queueuser->userid]))
					{
						$user_activity[$queueuser->userid] = array();
					}

					array_push($user_activity[$queueuser->userid], $gquser);
				}

				foreach ($user_activity as $userid => $activity)
				{
					// Change states
					foreach ($activity as $queueuser)
					{
						$queueuser->update(['notice' => 0]);
					}
				}

				// Assemble list of managers to email
				foreach ($group->managers as $manager)
				{
					// Prepare and send actual email
					$message = new FreeRequested($manager->user, $user_activity);

					if ($debug)
					{
						echo $message->render();
						$this->info("Emailed freerequested to manager {$manager->user->email}.");
						continue;
					}

					Mail::to($manager->user->email)->send($message);

					$this->log($manager->user->id, $manager->user->email, "Emailed freerequested to manager.");
				}
			}
		}
	}

	/**
	 * Log email
	 *
	 * @param   integer $targetuserid
	 * @param   integer $targetobjectid
	 * @param   string  $uri
	 * @param   mixed   $payload
	 * @return  null
	 */
	protected function log($targetuserid, $uri = '', $payload = '')
	{
		Log::create([
			'ip'              => request()->ip(),
			'userid'          => (auth()->user() ? auth()->user()->id : 0),
			'status'          => 200,
			'transportmethod' => 'POST',
			'servername'      => request()->getHttpHost(),
			'uri'             => Str::limit($uri, 128, ''),
			'app'             => Str::limit('email', 20, ''),
			'payload'         => Str::limit($payload, 2000, ''),
			'classname'       => Str::limit('queues:emailfreerequested', 32, ''),
			'classmethod'     => Str::limit('handle', 16, ''),
			'targetuserid'    => $targetuserid,
		]);
	}
}
