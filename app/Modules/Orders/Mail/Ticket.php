<?php

namespace App\Modules\Orders\Mail;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Mail\Traits\HeadersAndTags;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Users\Models\User;

class Ticket extends Mailable
{
	use Queueable, SerializesModels, HeadersAndTags;

	/**
	 * The order instance.
	 *
	 * @var Order
	 */
	protected $order;

	/**
	 * The user instance.
	 *
	 * @var User
	 */
	protected $user;

	/**
	 * @inheritdoc
	 */
	protected $mailTags = [
		'order',
		'order-ticket',
	];

	/**
	 * Create a new message instance.
	 *
	 * @param  Order $order
	 * @param  User $user
	 * @return void
	 */
	public function __construct(Order $order, User $user)
	{
		$this->order = $order;
		$this->user = $user;

		//$this->mailableTags[] = 'order-ticket';
	}

	/**
	 * Build the message.
	 *
	 * @return self
	 */
	public function build()
	{
		return $this->markdown('orders::mail.ticket')
					->subject('Order #' . $this->order->id . ' Fulfillment Request')
					->with([
						'order' => $this->order,
						'user' => $this->user,
					]);
	}
}
