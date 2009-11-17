<?php
/***************************************************************
*  Copyright notice
*
*  (c) Marcus Krause (marcus#exp2009@t3sec.info)
*  (c) Steffen Ritter (info@rs-websystems.de)
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
*  A copy is found in the textfile GPL.txt and important notices to the license
*  from the author is found in LICENSE.txt distributed with these scripts.
*
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * Class implementing salted evaluation methods.
 *
 * @author	Marcus Krause <marcus#exp2009@t3sec.info>
 * @author	Steffen Ritter <info@rs-websystems.de>
 *
 * @since	2009-06-14
 * @package	TYPO3
 * @subpackage	tx_saltedpasswords
 */
class tx_saltedpasswords_eval {
	/**
	 * Keeps TYPO3 mode.
	 *
	 * Either 'FE' or 'BE'.
	 *
	 * @var string
	 */
	protected $mode = NULL;

	/**
	 * This function just return the field value as it is. No transforming,
	 * hashing will be done on server-side.
	 *
	 * @return	JavaScript code for evaluating the
	 */
	function returnFieldJS() {
		return 'return value;';
	}

	/**
	 * Function uses Portable PHP Hashing Framework to create a proper password string if needed
	 *
	 * @param	mixed		$value: The value that has to be checked.
	 * @param	string		$is_in: Is-In String
	 * @param	integer		$set: Determines if the field can be set (value correct) or not, e.g. if input is required but the value is empty, then $set should be set to FALSE. (PASSED BY REFERENCE!)
	 * @return	The new value of the field
	 */
	function evaluateFieldValue($value, $is_in, &$set) {
		$isEnabled = $this->mode ? tx_saltedpasswords_div::isUsageEnabled($this->mode) : tx_saltedpasswords_div::isUsageEnabled();

		if ($isEnabled) {
			$set = FALSE;
			$isMD5 = preg_match('/[0-9abcdef]{32,32}/', $value);
			$isSaltedHash = t3lib_div::inList('$1$,$2$,$2a,$P$', substr($value, 0, 3));

			$this->objInstanceSaltedPW = tx_saltedpasswords_salts_factory::getSaltingInstance(NULL, $this->mode);

			if ($isMD5) {
				$set = TRUE;
				$value = 'M' . $this->objInstanceSaltedPW->getHashedPassword($value);
			} else if (!$isSaltedHash) {
				$set = TRUE;
				$value = $this->objInstanceSaltedPW->getHashedPassword($value);
			}
		}

		return $value;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/eval/class.tx_saltedpasswords_eval.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/saltedpasswords/classes/eval/class.tx_saltedpasswords_eval.php']);
}
?>