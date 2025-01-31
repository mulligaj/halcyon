<?php

namespace App\Halcyon\Form\Fields;

use App\Halcyon\Form\Field;
use App\Halcyon\Html\Builder\Select as Dropdown;

/**
 * Supports a generic list of options.
 */
class Select extends Field
{
	/**
	 * The form field type.
	 *
	 * @var  string
	 */
	protected $type = 'Select';

	/**
	 * Method to get the field input markup for a generic list.
	 * Use the multiple attribute to enable multiselect.
	 *
	 * @return  string  The field input markup.
	 */
	protected function getInput()
	{
		// Initialize variables.
		$html = array();
		//$attr = '';

		$attributes = array(
			'name'         => $this->name,
			'id'           => $this->id,
			'size'         => ($this->element['size']      ? (int) $this->element['size']      : ''),
			'multiple'     => ($this->element['multiple'] ? 'multiple' : ''),
			'class'        => 'form-control' . ($this->element['class'] ? (string) ' ' . $this->element['class'] : ''),
			'readonly'     => ((string) $this->element['readonly'] == 'true'    ? 'readonly' : ''),
			'disabled'     => ((string) $this->element['disabled'] == 'true'    ? 'disabled' : ''),
			'required'     => ((string) $this->element['required'] == 'true'    ? 'required' : ''),
			'onchange'     => ($this->element['onchange']  ? (string) $this->element['onchange'] : '')
		);

		$attr = array();
		foreach ($attributes as $key => $value)
		{
			if (!$value)
			{
				continue;
			}
			$attr[] = $key . '="' . $value . '"';
		}
		$attr = implode(' ', $attr);

		// Initialize some field attributes.
		/*$attr .= $this->element['class'] ? ' class="' . (string) $this->element['class'] . '"' : '';

		// To avoid user's confusion, readonly="true" should imply disabled="true".
		if ((string) $this->element['readonly'] == 'true' || (string) $this->element['disabled'] == 'true')
		{
			$attr .= ' disabled="disabled"';
		}

		$attr .= $this->element['size'] ? ' size="' . (int) $this->element['size'] . '"' : '';
		$attr .= $this->multiple ? ' multiple="multiple"' : '';

		// Initialize JavaScript field attributes.
		$attr .= $this->element['onchange'] ? ' onchange="' . (string) $this->element['onchange'] . '"' : '';
		*/

		// Get the field options.
		$options = (array) $this->getOptions();

		// Create a read-only list (no name) with a hidden input to store the value.
		if ((string) $this->element['readonly'] == 'true')
		{
			$html[] = Dropdown::genericlist($options, '', trim($attr), 'value', 'text', $this->value, $this->id);
			$html[] = '<input type="hidden" name="' . $this->name . '" value="' . htmlspecialchars((string)$this->value, ENT_COMPAT, 'UTF-8') . '" />';
		}
		// Create a regular list.
		else
		{
			$html[] = Dropdown::genericlist($options, $this->name, trim($attr), 'value', 'text', $this->value, $this->id);

			if ($this->element['option_other'])
			{
				$found = false;

				foreach ($options as $option)
				{
					if ($option->value == $this->value)
					{
						$found = true;
					}
				}
				$html[] = '<input type="text" name="' . $this->getName($this->fieldname . '_other') . '" value="' . ($found ? '' : htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8')) . '" placeholder="' . (empty($this->placeholder) ?  trans('global.other') : htmlspecialchars($this->placeholder, ENT_COMPAT, 'UTF-8')) . '" />';
			}
		}

		return implode($html);
	}

	/**
	 * Method to get the field options.
	 *
	 * @return  array<int,\stdClass>  The field option objects.
	 */
	protected function getOptions()
	{
		// Initialize variables.
		$options = array();

		if ($this->element['option_blank'])
		{
			$options[] = Dropdown::option('', trans('- Select -'), 'value', 'text');
		}

		foreach ($this->element->children() as $option)
		{
			// Only add <option /> elements.
			if ($option->getName() != 'option')
			{
				continue;
			}

			$label = (isset($option[0]) ? $option[0] : $option['label']);

			// Create a new option object based on the <option /> element.
			$tmp = Dropdown::option((string) $option['value'],
				trans(trim((string) $label)), 'value', 'text', //, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)
				((string) $option['disabled'] == 'true')
			);
			foreach ($option->attributes() as $index => $value)
			{
				$dataCheck = strtolower(substr($index, 0, 4));
				if ($dataCheck == 'data')
				{
					$tmp->$index = (string) $value;
				}
				if (!$this->value && $index == 'selected')
				{
					$this->value = (string) $option['value'];
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
