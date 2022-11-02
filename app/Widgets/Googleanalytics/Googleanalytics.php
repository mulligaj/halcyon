<?php
namespace App\Widgets\Googleanalytics;

use App\Modules\Widgets\Entities\Widget;

/**
 * Google Analytics widget
 */
class Googleanalytics extends Widget
{
	/**
	 * Display module
	 *
	 * @return  null|\Illuminate\View\View
	 */
	public function run()
	{
		if (!$this->params->get('key'))
		{
			return '';
		}

		return view($this->getViewName(), ['key' => $this->params->get('key')]);
	}
}
