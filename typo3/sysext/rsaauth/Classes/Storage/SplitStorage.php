<?php
namespace TYPO3\CMS\Rsaauth\Storage;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Dmitry Dulepov <dmitry@typo3.org>
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
 * This class contains a "split" storage for the data. It keeps part of the data
 * in the database, part in the database.
 *
 * @author Dmitry Dulepov <dmitry@typo3.org>
 */
class SplitStorage extends \TYPO3\CMS\Rsaauth\Storage\AbstractStorage {

	/**
	 * Creates an instance of this class. It checks and initializes PHP
	 * sessions if necessary.
	 */
	public function __construct() {
		if (session_id() === '') {
			session_start();
		}
	}

	/**
	 * Obtains a key from the database
	 *
	 * @return string The key or NULL
	 * @see tx_rsaauth_abstract_storage::get()
	 */
	public function get() {
		$result = NULL;
		list($keyId, $keyPart1) = $_SESSION['tx_rsaauth_key'];
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($keyId)) {
			// Remove expired keys (more than 30 minutes old)
			$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_rsaauth_keys', 'crdate<' . ($GLOBALS['EXEC_TIME'] - 30 * 60));
			// Get our value
			$row = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow('key_value', 'tx_rsaauth_keys', 'uid=' . $keyId);
			if (is_array($row)) {
				$result = $keyPart1 . $row['key_value'];
			}
		}
		return $result;
	}

	/**
	 * Adds a key to the storage or removes existing key
	 *
	 * @param string $key The key
	 * @return void
	 * @see 	tx_rsaauth_abstract_storage::put()
	 */
	public function put($key) {
		if ($key == NULL) {
			// Remove existing key
			list($keyId) = $_SESSION['tx_rsaauth_key'];
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($keyId)) {
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_rsaauth_keys', 'uid=' . $keyId);
				unset($_SESSION['tx_rsaauth_key']);
			}
		} else {
			// Add key
			// Get split point. First part is always smaller than the second
			// because it goes to the file system
			$keyLength = strlen($key);
			$splitPoint = rand(intval($keyLength / 10), intval($keyLength / 2));
			// Get key parts
			$keyPart1 = substr($key, 0, $splitPoint);
			$keyPart2 = substr($key, $splitPoint);
			// Store part of the key in the database
			//
			// Notice: we may not use TCEmain below to insert key part into the
			// table because TCEmain requires a valid BE user!
			$time = $GLOBALS['EXEC_TIME'];
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('tx_rsaauth_keys', array(
				'pid' => 0,
				'crdate' => $time,
				'key_value' => $keyPart2
			));
			$keyId = $GLOBALS['TYPO3_DB']->sql_insert_id();
			// Store another part in session
			$_SESSION['tx_rsaauth_key'] = array($keyId, $keyPart1);
		}
		// Remove expired keys (more than 30 minutes old)
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_rsaauth_keys', 'crdate<' . ($GLOBALS['EXEC_TIME'] - 30 * 60));
	}

}


?>