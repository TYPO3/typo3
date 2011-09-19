<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008 Patrick Broens (patrick@patrickbroens.nl)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Currency filter
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_System_Filter_Currency implements tx_form_System_Filter_Interface {

	/**
	 * Separator between group of thousands
	 * Mostly dot, comma or whitespace
	 *
	 * @var string
	 */
	protected $decimalsPoint;

	/**
	 * Separator between group of thousands
	 * Mostly dot, comma or whitespace
	 *
	 * @var string
	 */
	protected $thousandSeparator;

	/**
	 * Constructor
	 *
	 * @param array $arguments Filter configuration
	 */
	public function __construct($arguments = array()) {
		$this->setDecimalsPoint($arguments['decimalPoint']);
		$this->setThousandSeparator($arguments['thousandSeparator']);
	}

	/**
	 * Set the decimal point character
	 *
	 * @param string $decimalsPoint Character used for decimal point
	 * @return tx_form_System_Filter_Currency
	 */
	public function setDecimalsPoint($decimalsPoint = '.') {
		if (empty($decimalsPoint)) {
			$this->decimalsPoint = '.';
		} else {
			$this->decimalsPoint = (string) $decimalsPoint;
		}

		return $this;
	}

	/**
	 * Set the thousand separator character
	 *
	 * @param string $thousandSeparator Character used for thousand separator
	 * @return tx_form_System_Filter_Currency
	 */
	public function setThousandSeparator($thousandSeparator = ',') {
		if (empty($thousandSeparator)) {
			$this->thousandSeparator = ',';
		} elseif ($thousandSeparator === 'space') {
			$this->thousandSeparator = ' ';
		} elseif ($thousandSeparator === 'none') {
			$this->thousandSeparator = '';
		} else {
			$this->thousandSeparator = (string) $thousandSeparator;
		}

		return $this;
	}

	/**
	 * Change to float with 2 decimals
	 * Change the dot to comma if requested
	 *
	 * @param  string $value
	 * @return string
	 */
	public function filter($value) {
		$value = (float) ((string) $value);

		return number_format($value, 2, $this->decimalsPoint, $this->thousandSeparator);
	}
}
?>