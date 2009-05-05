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
 * Formats a given float to a currency representation.
 * 
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.currency>123.456</f:format.currency>
 * </code>
 * 
 * Output:
 * 123,46
 * 
 * <code title="All parameters">
 * <f:format.currency currencySign="$" decimalSeparator="." thousandsSeparator=",">54321</f:format.currency>
 * </code>
 * 
 * Output:
 * 54,321.00 $
 *
 * @package Fluid
 * @subpackage ViewHelpers
 * @version $Id$
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
class Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper extends Tx_Fluid_Core_AbstractViewHelper {

	/**
	 * @param string $currencySign (optional) The currency sign, eg $ or €.
	 * @param string $decimalSeparator (optional) The separator for the decimal point. 
	 * @param string $thousandsSeparator (optional) The thousands separator. 
	 * @return string the formatted amount.
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function render($currencySign = '', $decimalSeparator = ',', $thousandsSeparator = '.') {
		$stringToFormat = $this->renderChildren();
		$output = number_format($stringToFormat, 2, $decimalSeparator, $thousandsSeparator);
		if($currencySign !== '') {
			$output.= ' ' . $currencySign;
		}
		return $output;
	}
}
?>