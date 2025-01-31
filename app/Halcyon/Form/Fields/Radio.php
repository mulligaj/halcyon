<?php

namespace App\Halcyon\Form\Fields;

use App\Halcyon\Form\Field;
use App\Halcyon\Html\Builder\Select as Dropdown;

/**
 * Provides radio button inputs
 */
class Radio extends Field
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 */
	protected $type = 'Radio';

	/**
	 * Method to get the radio button field input markup.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = array();

		// Initialize some field attributes.
		$class = $this->element['class'] ? ' class="radio ' . (string) $this->element['class'] . '"' : ' class="radio"';

		// Start the radio field output.
		$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';

		// Get the field options.
		$options = $this->getOptions();
		$found = false;

		$html[] = '<ul>';

		// Build the radio field output.
		foreach ($options as $i => $option)
		{
			// Initialize some option attributes.
			$checked  = ((string) $option->value == (string) $this->value) ? ' checked="checked"' : '';
			$class    = ' class="form-check-input' . (!empty($option->class) ? ' ' . $option->class : '') . '"';
			$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';

			if ($checked)
			{
				$found = true;
			}

			// Add data attributes
			$dataAttributes = '';
			foreach ($option as $field => $value)
			{
				$dataField = strtolower(substr($field, 0, 4));
				if ($dataField == 'data')
				{
					$dataAttributes .= ' ' . $field . '="' . $value . '"';
				}
			}
			// Initialize some JavaScript option attributes.
			$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';

			$html[] = '<li' . (isset($this->element['inline']) ? ' class="d-inline mr-3"' : '') . '>';
			$html[] = '<div class="form-check">';
			$html[] = '<input type="radio" id="' . $this->id . $i . '" name="' . $this->name . '" value="' . htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $onclick . $disabled . $dataAttributes . '/>';
			//$html[] = '<label for="' . $this->id . $i . '"' . $class . '>' . trans($option->text, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)) . '</label>';
			$html[] = '<label class="form-check-label" for="' . $this->id . $i . '"' . $class . '>' . trans($option->text) . '</label>';
			$html[] = '</div>';
			$html[] = '</li>';
		}

		$i = is_null($i) ? 0 : $i;

		if ($this->element['option_other'])
		{
			$class    = ' class="form-check-input"';
			$disabled = '';

			$checked = '';
			if (!$found && $this->value)
			{
				$checked = ' checked="checked"';
			}
			$html[] = '<li>';
			$html[] = '<input type="radio" id="' . $this->id . ($i + 1) . '" name="' . $this->name . '" value=""' . $checked . $class . $onclick . $disabled . '/>';
			$html[] = '<label for="' . $this->id . ($i + 1) . '"' . $class . '>' . trans('global.other') . '</label>';
			$html[] = '<input type="text" id="' . $this->id . '_other" name="' . $this->getName($this->fieldname . '_other') . '" value="' . ($checked ? htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8') : '') . '"' . $class . $onclick . $disabled . '/>';
			$html[] = '</li>';
		}

		$html[] = '</ul>';

		// End the radio field output.
		$html[] = '</fieldset>';

		return implode($html);
	}

	/**
	 * Method to get the field options for radio buttons.
	 *
	 * @return  array<int,\stdClass>  The field option objects.
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		foreach ($this->element->children() as $option)
		{
			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}

			$label = (isset($option[0]) ? $option[0] : $option['label']);

			// Create a new option object based on the <option /> element.
			$tmp = Dropdown::option(
				(string) $option['value'], trim((string) $label), 'value', 'text',
				((string) $option['disabled'] == 'true')
			);
			foreach ($option->attributes() as $index => $value)
			{
				$dataCheck = strtolower(substr($index, 0, 4));
				if ($dataCheck == 'data')
				{
					$tmp->$index = (string) $value;
				}
			}

			// Set some option attributes.
			$tmp->class = (string) $option['class'];

			// Set some JavaScript option attributes.
			$tmp->onclick = (string) $option['onclick'];

			// Add the option object to the result set.
			$options[] = $tmp;
		}

		reset($options);

		return $options;
	}
}
