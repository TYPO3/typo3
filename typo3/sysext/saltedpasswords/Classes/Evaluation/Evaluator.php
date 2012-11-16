<?php
namespace TYPO3\CMS\Saltedpasswords\Evaluation;

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
 * @author Marcus Krause <marcus#exp2009@t3sec.info>
 * @author Steffen Ritter <info@rs-websystems.de>
 * @since 2009-06-14
 */
class Evaluator {

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
	 * @return string JavaScript code for evaluating the
	 * @todo Define visibility
	 */
	public function returnFieldJS() {
		return 'return value;';
	}

	/**
	 * Function uses Portable PHP Hashing Framework to create a proper password string if needed
	 *
	 * @param mixed $value The value that has to be checked.
	 * @param string $is_in Is-In String
	 * @param integer $set Determines if the field can be set (value correct) or not, e.g. if input is required but the value is empty, then $set should be set to FALSE. (PASSED BY REFERENCE!)
	 * @return The new value of the field
	 * @todo Define visibility
	 */
	public function evaluateFieldValue($value, $is_in, &$set) {
		$isEnabled = $this->mode ? \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled($this->mode) : \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled();
		if ($isEnabled) {
			$set = FALSE;
			$isMD5 = preg_match('/[0-9abcdef]{32,32}/', $value);
			$isDeprecatedSaltedHash = \TYPO3\CMS\Core\Utility\GeneralUtility::inList('C$,M$', substr($value, 0, 2));
			/** @var $objInstanceSaltedPW \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface */
			$objInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL, $this->mode);
			if ($isMD5) {
				$set = TRUE;
				$value = 'M' . $objInstanceSaltedPW->getHashedPassword($value);
			} else {
				// Determine method used for the (possibly) salted hashed password
				$tempValue = $isDeprecatedSaltedHash ? substr($value, 1) : $value;
				$tempObjInstanceSaltedPW = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($tempValue);
				if (!is_object($tempObjInstanceSaltedPW)) {
					$set = TRUE;
					$value = $objInstanceSaltedPW->getHashedPassword($value);
				}
			}
		}
		return $value;
	}

}


?>