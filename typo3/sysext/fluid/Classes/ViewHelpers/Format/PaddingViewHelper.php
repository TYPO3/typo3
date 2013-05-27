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
 * Formats a string using PHPs str_pad function.
 * @see http://www.php.net/manual/en/function.str_pad.php
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:format.padding padLength="10">TYPO3</f:format.padding>
 * </code>
 * <output>
 * TYPO3     (note the trailing whitespace)
 * <output>
 *
 * <code title="Specify padding string">
 * <f:format.padding padLength="10" padString="-=">TYPO3</f:format.padding>
 * </code>
 * <output>
 * TYPO3-=-=-
 * </output>
 *
 * <code title="Specify padding type">
 * <f:format.padding padLength="10" padString="-" padType="both">TYPO3</f:format.padding>
 * </code>
 * <output>
 * --TYPO3---
 * </output>
 *
 * @api
 */
class PaddingViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Pad a string to a certain length with another string
	 *
	 * @param integer $padLength Length of the resulting string. If the value of pad_length is negative or less than the length of the input string, no padding takes place.
	 * @param string $padString The padding string
	 * @param string $padType Append the padding at this site (Possible values: right,left,both. Default: right)
	 * @return string The formatted value
	 * @api
	 */
	public function render($padLength, $padString = ' ', $padType = 'right') {
		$string = $this->renderChildren();
		$padTypes = array(
			'left' => STR_PAD_LEFT,
			'right' => STR_PAD_RIGHT,
			'both' => STR_PAD_BOTH
		);
		if (!isset($padTypes[$padType])) {
			$padType = 'right';
		}
		return str_pad($string, $padLength, $padString, $padTypes[$padType]);
	}
}

?>