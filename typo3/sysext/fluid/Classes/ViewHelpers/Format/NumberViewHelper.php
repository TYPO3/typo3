<?php
namespace TYPO3\CMS\Fluid\ViewHelpers\Format;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Formats a number with custom precision, decimal point and grouped thousands.
 * @see http://www.php.net/manual/en/function.number-format.php
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.number>423423.234</f:format.number>
 * </code>
 * <output>
 * 423,423.20
 * </output>
 *
 * <code title="With all parameters">
 * <f:format.number decimals="1" decimalSeparator="," thousandsSeparator=".">423423.234</f:format.number>
 * </code>
 * <output>
 * 423.423,2
 * </output>
 *
 * @api
 */
class NumberViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Format the numeric value as a number with grouped thousands, decimal point and
	 * precision.
	 *
	 * @param integer $decimals The number of digits after the decimal point
	 * @param string $decimalSeparator The decimal point character
	 * @param string $thousandsSeparator The character for grouping the thousand digits
	 * @return string The formatted number
	 * @api
	 */
	public function render($decimals = 2, $decimalSeparator = '.', $thousandsSeparator = ',') {
		$stringToFormat = $this->renderChildren();
		return number_format($stringToFormat, $decimals, $decimalSeparator, $thousandsSeparator);
	}
}

?>