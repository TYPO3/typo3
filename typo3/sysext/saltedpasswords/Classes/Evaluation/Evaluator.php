<?php
namespace TYPO3\CMS\Saltedpasswords\Evaluation;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
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
	 * @param bool $set Determines if the field can be set (value correct) or not, e.g. if input is required but the value is empty, then $set should be set to FALSE. (PASSED BY REFERENCE!)
	 * @return string The new value of the field
	 */
	public function evaluateFieldValue($value, $is_in, &$set) {
		$isEnabled = $this->mode ? \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled($this->mode) : \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::isUsageEnabled();
		if ($isEnabled) {
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
