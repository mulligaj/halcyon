<?php

namespace App\Halcyon\Form\Rules;

use App\Halcyon\Form\Rule;

/**
 * Requires the value entered be one of the options in a field of type="list"
 */
class Options extends Rule
{
	/**
	 * Method to test the value.
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
		// Check each value and return true if we get a match
		foreach ($element->option as $option)
		{
			if ($value == (string) $option->attributes()->value)
			{
				return true;
			}
		}

		return false;
	}
}
