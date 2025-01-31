<?php

namespace App\Modules\Queues\Mail;

use App\Modules\Queues\Mail\Traits\HeadersAndTags;
use App\Modules\Users\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class FreeDenied extends Mailable
{
	use Queueable, SerializesModels, HeadersAndTags;

	/**
	 * The User
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * The User
	 *
	 * @var array
	 */
	protected $queueusers;

	/**
	 * Create a new message instance.
	 *
	 * @return void
	 */
	public function __construct(User $user, $queueusers)
	{
		$this->user = $user;
		$this->queueusers = $queueusers;

		$this->mailTags[] = 'queue-denied';
		$this->mailTags[] = 'queue-free';
	}


	/**
	 * Build the message.
	 *
	 * @return $this
	 */
	public function build()
	{
		return $this->markdown('queues::mail.freedenied.user')
					->subject(trans('queues::mail.freedenied.user'))
					->with([
						'user' => $this->user,
						'denials' => $this->queueusers,
					]);
	}
}
