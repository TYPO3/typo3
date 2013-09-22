<?php
namespace TYPO3\CMS\Core\Utility;

	/***************************************************************
	 * Copyright notice
	 *
	 * (c) 2013 Torben Hansen <derhansen@gmail.com>
	 * All rights reserved
	 *
	 * This script is part of the TYPO3 project. The TYPO3 project is
	 * free software; you can redistribute it and/or modify
	 * it under the terms of the GNU General Public License as published by
	 * the Free Software Foundation; either version 2 of the License, or
	 * (at your option) any later version.
	 *
	 * The GNU General Public License can be found at
	 * http://www.gnu.org/copyleft/gpl.html.
	 * A copy is found in the textfile GPL.txt and important notices to the license
	 * from the author is found in LICENSE.txt distributed with these scripts.
	 *
	 *
	 * This script is distributed in the hope that it will be useful,
	 * but WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	 * GNU General Public License for more details.
	 *
	 * This copyright notice MUST APPEAR in all copies of the script!
	 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class with helper functions for IP addressses
 *
 * @author Torben Hansen <derhansen@gmail.com>
 */
class IpUtility {

	/**
	 * Checks if the remote IP address is internally blacklisted
	 *
	 * @return bool
	 */
	static public function checkIpBlacklisted() {
		static::cleanUpIpBlacklist();

		$blRecord = static::getIpBlacklistRecord();
		if (is_array($blRecord)) {
			return $blRecord['blacklisted'];
		} else {
			return FALSE;
		}
	}

	/**
	 * Increases the blacklist counter for the remote IP address. If max login failures is reached, the
	 * IP address gets blacklisted for a given time.
	 *
	 * @return void
	 */
	static public function increaseIpBlacklistCounter() {
		$blRecord = static::getIpBlacklistRecord();
		if (!is_array($blRecord)) {
			/* First failed authentication attempt - create record */
			$fields = array();
			$fields['ip'] = GeneralUtility::getIndpEnv('REMOTE_ADDR');
			$fields['tstamp'] = time();
			$fields['numfailures'] = 1;
			$fields['blacklisted'] = 0;
			$fields['expires'] = time() + 360; // Failed login attempt expires in 360 seconds
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_ipblacklist', $fields);
		} else {
			$fields = array();
			$maxLoginFailures = 2; // @todo make value configurable in install tool e.g. ['TYPO3_CONF_VARS']['BE']['bfMaxLoginFailures']
			if ($blRecord['numfailures'] + 1 >= $maxLoginFailures) {
				// Blacklist the IP address
				$fields['blacklisted'] = 1;
				$fields['expires'] = time() + 360; // @todo make value configurable in install tool e.g. ['TYPO3_CONF_VARS']['BE']['bfIpDenyTime']
			} else {
				// Just increase counter and expire timestamp. Failed login attempt expires in 360 seconds
				$fields['expires'] = time() + 360;
			}
			$fields['numfailures'] = $blRecord['numfailures'] + 1;
			$where = 'ip="' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '"';
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_ipblacklist', $where, $fields);
		}
	}

	/**
	 * Returns the IP blacklist record for the remote IP address
	 *
	 * @return bool|array
	 */
	static protected function getIpBlacklistRecord() {
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_ipblacklist',
			'ip="' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '"');
		$row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res);
		return $row;
	}

	/**
	 * Removes all expired records from the IP blacklist
	 *
	 * @return void
	 */
	static protected function cleanUpIpBlacklist() {
		$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_ipblacklist', 'expires < ' . time());
	}

}