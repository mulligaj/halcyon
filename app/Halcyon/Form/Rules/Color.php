<?php

namespace App\Halcyon\Form\Rules;

use App\Halcyon\Form\Rule;

/**
 * Form Rule class for color values.
 */
class Color extends Rule
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
		$value = trim($value);

		if (empty($value))
		{
			// A color field can't be empty, we default to black. This is the same as the HTML5 spec.
			$value = '#000000';
			return true;
		}

		if ($value[0] != '#')
		{
			return false;
		}

		// Remove the leading # if present to validate the numeric part
		$value = ltrim($value, '#');

		// The value must be 6 or 3 characters long
		if (!((strlen($value) == 6 || strlen($value) == 3) && ctype_xdigit($value)))
		{
			return false;
		}

		// Prepend the # again
		$value = '#' . $value;

		return true;
	}
}
