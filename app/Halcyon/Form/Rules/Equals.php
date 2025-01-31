<?php

namespace App\Halcyon\Form\Rules;

use App\Halcyon\Form\Form;
use App\Halcyon\Form\Rule;
use Exception;

/**
 * Form Rule class for testing a value equals another.
 */
class Equals extends Rule
{
	/**
	 * Method to test for a valid color in hexadecimal.
	 *
	 * @param   object   &$element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed    $value     The form field value to validate.
	 * @param   string   $group     The field name group control value. This acts as as an array container for the field.
	 *                              For example if the field has name="foo" and the group value is set to "bar" then the
	 *                              full field name would end up being "bar[foo]".
	 * @param   object   &$input    An optional Repository object with the entire data set to validate against the entire form.
	 * @param   object   &$form     The form object for which the field is being tested.
	 * @return  bool     True if the value is valid, false otherwise.
	 */
	public function test(&$element, $value, $group = null, &$input = null, &$form = null)
	{
		// Initialize variables.
		$field = (string) $element['field'];

		// Check that a validation field is set.
		if (!$field)
		{
			return new Exception('core::core.error.invalid form rule' . get_class($this));
		}

		// Check that a valid Form object is given for retrieving the validation field value.
		if (!($form instanceof Form))
		{
			return new Exception('core::core.error.invalid form object' . get_class($this));
		}

		// Test the two values against each other.
		if ($value == $input->get($field))
		{
			return true;
		}

		return false;
	}
}
