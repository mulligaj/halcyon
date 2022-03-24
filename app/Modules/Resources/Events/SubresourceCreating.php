<?php

namespace App\Modules\Resources\Events;

use App\Modules\Resources\Models\Subresource;

class SubresourceCreating
{
	/**
	 * @var Subresource
	 */
	public $subresource;

	/**
	 * Constructor
	 *
	 * @param Subresource $subresource
	 * @return void
	 */
	public function __construct(Subresource $subresource)
	{
		$this->subresource = $subresource;
	}
}
