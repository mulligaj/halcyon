<?php

namespace App\Halcyon\Form\Fields;

use App\Halcyon\Form\Field;

/**
 * Supports a numeric field.
 */
class Number extends Field
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 */
	protected $type = 'Number';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		// Initialize some field attributes.
		$attributes = array(
			'type'         => 'number',
			'value'        => htmlspecialchars((string)$this->value, ENT_COMPAT, 'UTF-8'),
			'name'         => $this->name,
			'id'           => $this->id,
			'min'          => (!is_null($this->element['min']) ? (int) $this->element['min'] : null),
			'max'          => ($this->element['max'] ? (int) $this->element['max'] : null),
			'step'         => ($this->element['step'] ? (int) $this->element['step'] : null),
			'pattern'      => ($this->element['pattern'] ? $this->element['pattern'] : null),
			'class'        => 'form-control' . ($this->element['class'] ? ' ' . (string) $this->element['class'] : null),
			'readonly'     => ((string) $this->element['readonly'] == 'true' ? 'readonly' : null),
			'disabled'     => ((string) $this->element['disabled'] == 'true' ? 'disabled' : null),
			'required'     => ((string) $this->element['required'] == 'true' ? 'required' : null),
			'onchange'     => ($this->element['onchange']  ? (string) $this->element['onchange'] : null)
		);

		$attr = array();
		foreach ($attributes as $key => $value)
		{
			if ($key != 'value' && $key != 'min' && !$value)
			{
				continue;
			}
			if ($key == 'min' && is_null($value))
			{
				continue;
			}

			$attr[] = $key . '="' . $value . '"';
		}
		$attr = implode(' ', $attr);

		return '<input ' . $attr . ' />';
	}
}
