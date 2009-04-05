<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 */

/**
 * Formats a number with custom precision, decimal point and grouped thousands.
 *
 * Example:
 *
 * (1) default parameters:
 * <f:format.number value="423423.234" />
 *
 * Output:
 * 423,423.20
 *
 * (2) with all parameters
 * <f:format.number value="423423.234" decimals="1" decimalPoint="," thousandsSeparator="." />
 *
 * Output:
 * 423.423,2
 *
 * @see http://www.php.net/manual/en/function.number-format.php
 *
 * @package
 * @subpackage
 * @version $Id:$
 */
class Tx_Fluid_ViewHelpers_Format_NumberViewHelper extends Tx_Fluid_Core_AbstractViewHelper {
	/**
	 * Format the numeric value as a number with grouped thousands, decimal point and
	 * precision.
	 *
	 * @param numeric $value The value to format
	 * @param int $decimals The number of digits after the decimal point
	 * @param string $decimalPoint The decimal point character
	 * @param string $thousandsSeparator The character for grouping the thousand digits
	 * @return string The formatted number
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function render($value, $decimals = 2, $decimalPoint = '.', $thousandsSeparator = ',') {
		return number_format($value, $decimals, $decimalPoint, $thousandsSeparator);
	}
}


?>