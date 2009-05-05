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
 * @version $Id: NumberViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 */

/**
 * Formats a number with custom precision, decimal point and grouped thousands.
 * @see http://www.php.net/manual/en/function.number-format.php
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.number value="423423.234" />
 * </code>
 *
 * Output:
 * 423,423.20
 *
 * <code title="With all parameters">
 * <f:format.number value="423423.234" decimals="1" decimalSeparator="," thousandsSeparator="." />
 * </code>
 *
 * Output:
 * 423.423,2
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id: NumberViewHelper.php 2172 2009-04-21 20:52:08Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_NumberViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * Format the numeric value as a number with grouped thousands, decimal point and
	 * precision.
	 *
	 * @param float $value The value to format
	 * @param int $decimals The number of digits after the decimal point
	 * @param string $decimalSeparator The decimal point character
	 * @param string $thousandsSeparator The character for grouping the thousand digits
	 * @return string The formatted number
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($value, $decimals = 2, $decimalSeparator = '.', $thousandsSeparator = ',') {
		return number_format($value, $decimals, $decimalSeparator, $thousandsSeparator);
	}
}
?>