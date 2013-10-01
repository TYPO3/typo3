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
 * Formats an integer with a byte count into human-readable form.
 *
 * = Examples =
 *
 * <code title="Defaults">
 * {fileSize -> f:format.bytes()}
 * </code>
 * <output>
 * 123 KB
 * // depending on the value of {fileSize}
 * </output>
 *
 * <code title="Defaults">
 * {fileSize -> f:format.bytes(decimals: 2, decimalSeparator: ',', thousandsSeparator: ',')}
 * </code>
 * <output>
 * 1,023.00 B
 * // depending on the value of {fileSize}
 * </output>
 *
 * @api
 */
class BytesViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @var array
	 */
	protected $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

	/**
	 * Render the supplied byte count as a human readable string.
	 *
	 * @param integer $value The incoming data to convert, or NULL if VH children should be used
	 * @param integer $decimals The number of digits after the decimal point
	 * @param string $decimalSeparator The decimal point character
	 * @param string $thousandsSeparator The character for grouping the thousand digits
	 * @return string Formatted byte count
	 * @api
	 */
	public function render($value = NULL, $decimals = 0, $decimalSeparator = '.', $thousandsSeparator = ',') {
		if ($value === NULL) {
			$value = $this->renderChildren();
		}

		if (!is_integer($value) && !is_float($value)) {
			if (is_numeric($value)) {
				$value = (float)$value;
			} else {
				$value = 0;
			}
		}
		$bytes = max($value, 0);
		$pow = floor(($bytes ? log($bytes) : 0) / log(1024));
		$pow = min($pow, count($this->units) - 1);
		$bytes /= pow(2, (10 * $pow));

		return sprintf(
			'%s %s',
			number_format(round($bytes, 4 * $decimals), $decimals, $decimalSeparator, $thousandsSeparator),
			$this->units[$pow]
		);
	}

}
