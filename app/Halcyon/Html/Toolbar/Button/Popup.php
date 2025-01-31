<?php

namespace App\Halcyon\Html\Toolbar\Button;

use App\Halcyon\Html\Toolbar\Button;
//use App\Halcyon\Html\Builder\Behavior;

/**
 * Renders a popup window button
 */
class Popup extends Button
{
	/**
	 * Button type
	 *
	 * @var  string
	 */
	protected $_name = 'Popup';

	/**
	 * Fetch the HTML for the button
	 *
	 * @param   string   $type     Unused string, formerly button type.
	 * @param   string   $name     Button name
	 * @param   string   $text     The link text
	 * @param   string   $url      URL for popup
	 * @param   int  $width    Width of popup
	 * @param   int  $height   Height of popup
	 * @param   int  $top      Top attribute.
	 * @param   int  $left     Left attribute
	 * @param   string   $onClose  JavaScript for the onClose event.
	 * @return  string   HTML string for the button
	 */
	public function fetchButton($type = 'Popup', $name = '', $text = '', $url = '', $width = 640, $height = 480, $top = 0, $left = 0, $onClose = '')
	{
		//Behavior::modal();

		$text  = trans($text);
		$class = $this->fetchIconClass($name);
		$url   = $this->_getCommand($name, $url, $width, $height, $top, $left);

		$html  = "<a data-title=\"$text\" class=\"btn popup btn-" . $name . "\" href=\"$url\" data-width=\"$width\" data-height=\"$height\" data-close=\"function() {" . $onClose . "}\">\n";
		$html .= "<span class=\"$class\">\n";
		$html .= "$text\n";
		$html .= "</span>\n";
		$html .= "</a>\n";

		return $html;
	}

	/**
	 * Get the button id
	 *
	 * @param   string  $type  Button type
	 * @param   string  $name  Button name
	 * @return  string  Button CSS Id
	 */
	public function fetchId($type, $name)
	{
		return $this->_parent->getName() . '-popup-' . $name;
	}

	/**
	 * Get the JavaScript command for the button
	 *
	 * @param   string   $name    Button name
	 * @param   string   $url     URL for popup
	 * @param   int  $width   Unused formerly width.
	 * @param   int  $height  Unused formerly height.
	 * @param   int  $top     Unused formerly top attribute.
	 * @param   int  $left    Unused formerly left attribure.
	 * @return  string   Command string
	 */
	protected function _getCommand($name, $url, $width, $height, $top, $left)
	{
		if (substr($url, 0, 4) !== 'http')
		{
			$root = rtrim(request()->root(true), '/');
			if (substr($url, 0, strlen($root)) != $root)
			{
				$url = $root . '/' . ltrim($url, '/');
			}
		}

		return $url;
	}
}
