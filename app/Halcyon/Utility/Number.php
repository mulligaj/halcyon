<?php

namespace App\Halcyon\Utility;

/**
 * Numeric helper class. Provides additional formatting methods for
 * working with numeric values.
 *
 * Techniques and inspiration were taken from all over, including:
 *	Kohana Framework: kohanaframework.org
 *	CakePHP: cakephp.org
 *	Fuel Framework: fuelphp.com
 */
class Number
{
	/**
	 * Converts a number of bytes to a human readable number by taking the
	 * number of that unit that the bytes will go into it. Supports TB value.
	 *
	 * Note: Integers in PHP are limited to 32 bits, unless they are on 64 bit
	 * architectures, then they have 64 bit size. If you need to place the
	 * larger size then what the PHP integer type will hold, then use a string.
	 * It will be converted to a double, which should always have 64 bit length.
	 *
	 * @param   int $bytes
	 * @param   int $decimals
	 * @return  string
	 */
	public static function formatBytes($bytes = 0, $decimals = 2): string
	{
		$quant = array(
			'TB' => 1099511627776,  // pow(1024, 4)
			'GB' => 1073741824,     // pow(1024, 3)
			'MB' => 1048576,        // pow(1024, 2)
			'KB' => 1024,           // pow(1024, 1)
			'B ' => 1,              // pow(1024, 0)
		);

		$sign = '';
		if ($bytes < 0)
		{
			$sign = '-';
		}

		$bytes = abs($bytes);

		foreach ($quant as $unit => $mag)
		{
			if (doubleval($bytes) >= $mag)
			{
				return $sign . sprintf('%01.' . $decimals . 'f', ($bytes / $mag)) . ' ' . $unit;
			}
		}

		return $sign . '0 B';
	}

	/**
	 * Convert a string to bytes
	 *
	 * @param   string  $value
	 * @return  int
	 */
	public static function toBytes($value): int
	{
		$value = str_replace(',', '', $value);
		$neg = false;

		//if (preg_match_all("/^(\-?\d*\.?\d+)\s*(\D+)$/", $value, $matches))
		if (preg_match_all("/^(\-?\d*\.?\d+)\s*([PpTtGgMmKkiBb]{1,3})$/", $value, $matches))
		{
			if ($matches[1][0] < 0)
			{
				$neg = true;
			}
			$num  = abs((int)$matches[1][0]);
			$unit = $matches[2][0];

			$units = array(
				array("b", "bytes?"),
				array("ki?b?", "kilobytes?", "kibibytes?", "kbytes?"),
				array("mi?b?", "megabytes?", "mebibytes?", "mbytes?"),
				array("gi?b?", "gigabytes?", "gibibytes?", "gbytes?"),
				array("ti?b?", "terabytes?", "tebibytes?", "tbytes?"),
				array("pi?b?", "petabytes?", "pebibytes?", "pbytes?"),
				array("xi?b?", "exabytes?", "exibytes?", "xbytes?"),
			);

			$power = 0;
			foreach ($units as $unit_group)
			{
				foreach ($unit_group as $unit_regex)
				{
					if (preg_match("/^" . $unit_regex . "$/i", $unit))
					{
						break 2;
					}
				}
				$power++;
			}

			$mult = $num;
			for ($i = 0; $i < $power; $i++)
			{
				$mult = $mult * 1024;
			}

			$value = $mult;
		}
		else
		{
			$value = intval($value);
		}

		return $neg ? -(int)$value : (int)$value;
	}

	/**
	 * Converts a number into a more readable human-type number.
	 *
	 * Usage:
	 * <code>
	 * echo Number::quantity(7000); // 7K
	 * echo Number::quantity(7500); // 8K
	 * echo Number::quantity(7500, 1); // 7.5K
	 * </code>
	 *
	 * @param   int $num
	 * @param   int $decimals
	 * @return  string
	 */
	public static function quantity($num, $decimals = 0): string
	{
		if ($num >= 1000 && $num < 1000000)
		{
			return sprintf('%01.' . $decimals . 'f', (sprintf('%01.0f', $num) / 1000)) . 'K';
		}
		elseif ($num >= 1000000 && $num < 1000000000)
		{
			return sprintf('%01.' . $decimals . 'f', (sprintf('%01.0f', $num) / 1000000)) . 'M';
		}
		elseif ($num >= 1000000000)
		{
			return sprintf('%01.' . $decimals . 'f', (sprintf('%01.0f', $num) / 1000000000)) . 'B';
		}

		return $num;
	}

	/**
	 * Formats a number by injecting non-numeric characters in a specified
	 * format into the string in the positions they appear in the format.
	 *
	 * Usage:
	 * <code>
	 * echo Number::format('1234567890', '(000) 000-0000'); // (123) 456-7890
	 * echo Number::format('1234567890', '000.000.0000'); // 123.456.7890
	 * </code>
	 *
	 * @link    http://snippets.symfony-project.org/snippet/157
	 * @param   string $string the string to format
	 * @param   string $format the format to apply
	 * @return  string
	 */
	public static function format($string = '', $format = ''): string
	{
		if (empty($format) || empty($string))
		{
			return $string;
		}

		if ($format == 'bytes')
		{
			return self::formatBytes($string);
		}

		$result = '';
		$fpos = 0;
		$spos = 0;

		while ((strlen($format) - 1) >= $fpos)
		{
			if (ctype_alnum(substr($format, $fpos, 1)))
			{
				$result .= substr($string, $spos, 1);
				$spos++;
			}
			else
			{
				$result .= substr($format, $fpos, 1);
			}

			$fpos++;
		}

		return $result;
	}
}
