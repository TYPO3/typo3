<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010 Steffen Kamper <steffen@typo3.org>
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

class tx_em_reports_ExtensionStatus  implements tx_reports_StatusProvider {

	/**
	 * @var string
	 */
	protected $ok = '';

	/**
	 * @var string
	 */
	protected $error = '';

	/**
	 * Determines the status of extension manager
	 *
	 * @return	array	List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$this->ok = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:translation_status_ok');
		$this->error = t3lib_div::strtoupper($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:msg_error'));
		$status = $this->getInsecuredExtensionsInSystem();

		$statuses = array(
			'mainRepositoryCheck' => $this->checkMainRepositoryCheck(),
			'extensionsSecurityStatusNotInstalled' => $status[0],
			'extensionsSecurityStatusInstalled' => $status[1],
		);

		return $statuses;
	}

	/**
	 * Checks main repository in sys_ter (existance, has extensions / update older tha 7 days
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether
	 */
	protected function checkMainRepositoryCheck() {
		$value = $this->ok;
		$severity = tx_reports_reports_status_Status::OK;
		$message = '';

		$tables = array_keys($GLOBALS{'TYPO3_DB'}->admin_get_tables());
		if (!in_array('sys_ter', $tables)) {
			$value = $this->error;
			$severity = tx_reports_reports_status_Status::ERROR;
			$message = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_sysTerNotFound');
		} else {
			$row = $GLOBALS{'TYPO3_DB'}->exec_SELECTgetSingleRow(
				'*', 'sys_ter', 'uid=1'
			);
			if (!is_array($row) || $row['title'] !== 'TYPO3.org Main Repository') {
				$value = $this->error;
				$severity = tx_reports_reports_status_Status::ERROR;
				$message = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_MainRepositoryNotFound');
			} else {
				if ($row['extCount'] == 0) {
				 	$value = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_NoExtensionsFound');
					$severity = tx_reports_reports_status_Status::WARNING;
					$message = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_MainRepositoryNoExtensions');
				} else {
					if ($row['lastUpdated'] < $GLOBALS['EXEC_TIME'] - (3600*24*7)) {
					 	$value = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_ExtensionsNotUpToDate');
						$severity = tx_reports_reports_status_Status::NOTICE;
						$message = $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_MainRepositoryOldList');
					}
				}
			}
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_StatusMainRepository'),
			$value,
			$message,
			$severity
		);
	}


	/**
	 * Checks if there are insecure extensions in system
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether
	 */
	protected function getInsecuredExtensionsInSystem() {
		$value    = array(
			$this->ok,
			$this->ok
		);
		$message  = array('', '');
		$severity = array(tx_reports_reports_status_Status::OK, tx_reports_reports_status_Status::OK);
	   $initialMessage = array(
		   $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_insecureInstalledExtensions') . '<br><br>',
		   $GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_insecureExistingExtensions') . '<br><br>',
	   );
		$extensionList = array();
		$installedExtensionList = array();
		$extensionCompareList = array();
		$unsecureList = array();

		/** @var $list tx_em_Extensions_List */
		$list = t3lib_div::makeInstance('tx_em_Extensions_List');
		$extList = $list->getInstalledExtensions(TRUE);

		foreach ($extList as $extension) {
			$extensionList[] = '"' . $extension['extkey'] . '"';
			$extensionCompareList[] = $extension['extkey'] . '|' . $extension['version'];
			if ($extension['installed']) {
				$installedExtensionList[] = $extension['extkey'];
			}
		}

			// prepare flat list of extensions for sql
		$flatList = implode(',', $extensionList);
			// get insecure extensions from database
		$insecureListRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'extkey, version',
			'cache_extensions',
			'reviewstate=-1 and extkey IN(' . $flatList . ')'
		);
		foreach ($insecureListRows as $row) {
			$unsecureList[] = $row['extkey'] . '|' . $row['version'];
		}

		$resultArray = array_intersect($extensionCompareList, $unsecureList);

		if (count($resultArray) > 0) {
			$count = array(0, 0);
			foreach ($resultArray as $result) {
				$temp = explode('|', $result);
				$index = in_array($temp[0], $installedExtensionList) ? 0 : 1;
				$severity[$index] = $index === 0 ? tx_reports_reports_status_Status::ERROR : tx_reports_reports_status_Status::WARNING;
				$count[$index]++;
				if ($message[$index] === '') {
					$message[$index] = $initialMessage[$index];
				}
				$message[$index] .= '<strong>' . $temp[0] . '</strong> (version ' . $temp[1] . ')<br>';
			}
			if ($count[0]) {
				$value[0] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_insecureExtensionsFound'), $count[0]);
			}
			if ($count[1]) {
				$value[1] = sprintf($GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_insecureExtensionsFound'), $count[1]);
			}
		}

		$status[0] = t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_StatusInstalledExtensions'),
			$value[0],
			$message[0],
			$severity[0]
		);

		$status[1] = t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->sL('LLL:EXT:em/language/locallang.xml:reports_StatusExistingExtensions'),
			$value[1],
			$message[1],
			$severity[1]
		);

		return $status;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/em/classes/reports/class.tx_em_reports_extensionstatus.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/em/classes/reports/class.tx_em_reports_extensionstatus.php']);
}

?>