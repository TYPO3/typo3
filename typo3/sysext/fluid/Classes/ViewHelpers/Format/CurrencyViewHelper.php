<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */


/**
 * Formats a given float to a currency representation.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.currency>123.456</f:format.currency>
 * </code>
 * <output>
 * 123,46
 * </output>
 *
 * <code title="All parameters">
 * <f:format.currency currencySign="$" decimalSeparator="." thousandsSeparator=",">54321</f:format.currency>
 * </code>
 * <output>
 * 54,321.00 $
 * </output>
 *
 * <code title="Inline notation">
 * {someNumber -> f:format.currency(thousandsSeparator: ',', currencySign: '€')}
 * </code>
 * <output>
 * 54,321,00 €
 * (depending on the value of {someNumber})
 * </output>
 *
 * @api
 */
class Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper extends Tx_Fluid_Core_ViewHelper_AbstractViewHelper {

	/**
	 * @param string $currencySign (optional) The currency sign, eg $ or €.
	 * @param string $decimalSeparator (optional) The separator for the decimal point.
	 * @param string $thousandsSeparator (optional) The thousands separator.
	 * @return string the formatted amount.
	 * @api
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