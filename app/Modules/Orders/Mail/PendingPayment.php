<?php

namespace App\Modules\Orders\Mail;

use App\Modules\Orders\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Modules\Users\Models\User;

class PendingPayment extends Mailable
{
	use Queueable, SerializesModels;

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
	}

	/**
	 * Build the message.
	 *
	 * @return self
	 */
	public function build()
	{
		return $this->markdown('orders::mail.pendingpayment')
					->subject('Order #' . $this->order->id . ' Payment Information Request')
					->with([
						'order' => $this->order,
						'user' => $this->user,
					]);
	}
}
