<?php

namespace App\Modules\Queues\Mail;

use App\Modules\Queues\Mail\Traits\HeadersAndTags;
use App\Modules\Users\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Queues\Events\WelcomeClusterBuild;

class WelcomeCluster extends Mailable
{
	use Queueable, SerializesModels, HeadersAndTags;

	/**
	 * The Queue
	 *
	 * @var Queue
	 */
	protected $user;

	/**
	 * The Queue
	 *
	 * @var Queue
	 */
	protected $activity;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(User $user, $activity = array())
	{
		$this->user = $user;
		$this->activity = $activity;

		$this->mailTags[] = 'queue-welcome-cluster';
	}

	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		event($e = new WelcomeClusterBuild($this->user, $this->activity));

		return $this->markdown($e->path ? $e->path : 'queues::mail.welcome.cluster')
					->subject(trans('queues::mail.welcome.cluster'))
					->with([
						'user' => $this->user,
						'activity' => $this->activity,
					]);
	}
}
