<?php

namespace App\Halcyon\Form\Fields;

use App\Halcyon\Form\Field;

/**
 * Provides and input field for e-mail addresses
 */
class Email extends Field
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 */
	protected $type = 'Email';

	/**
	 * Method to get the field input markup for e-mail addresses.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		// Initialize some field attributes.
		$size = $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$maxLength = $this->element['maxlength'] ? ' maxlength="' . (int) $this->element['maxlength'] . '"' : '';
		$class = $this->element['class'] ? ' ' . (string) $this->element['class'] : '';
		$readonly = ((string) $this->element['readonly'] == 'true') ? ' readonly="readonly"' : '';
		$disabled = ((string) $this->element['disabled'] == 'true') ? ' disabled="disabled"' : '';
		$required  = ((string) $this->element['required'] == 'true' ? ' required="required"' : '');

		// " and \ are always forbidden in email unless escaped.
		$this->value = str_replace(array('"','\\'), '', (string) $this->value);

		// Initialize JavaScript field attributes.
		$onchange = $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';

		return '<input type="text" name="' . $this->name . '" class="form-control validate-email' . $class . '" id="' . $this->id . '"' . ' value="'
			. htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') . '"' . $size . $disabled . $readonly . $required . $onchange . $maxLength . '/>';
	}
}
