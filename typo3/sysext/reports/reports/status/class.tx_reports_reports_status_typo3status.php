<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
 * Performs basic checks about the TYPO3 install
 *
 * @author Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_reports_status_Typo3Status implements tx_reports_StatusProvider {

	/**
	 * Returns the status for this report
	 *
	 * @return array List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$statuses = array(
			'Typo3Version' => $this->getTypo3VersionStatus(),
			'oldXclassStatus' => $this->getOldXclassUsageStatus(),
		);

		return $statuses;
	}

	/**
	 * Simply gets the current TYPO3 version.
	 *
	 * @return tx_reports_reports_status_Status
	 */
	protected function getTypo3VersionStatus() {
		return t3lib_div::makeInstance(
			'tx_reports_reports_status_Status',
			'TYPO3',
			TYPO3_version,
			'',
			tx_reports_reports_status_Status::NOTICE
		);
	}

	/**
	 * Check for usage of old way of implementing XCLASSes
	 *
	 * @return tx_reports_reports_status_Status
	 */
	protected function getOldXclassUsageStatus() {
		$message = '';
		$value = $GLOBALS['LANG']->getLL('status_none');
		$severity = tx_reports_reports_status_Status::OK;

		$xclasses = array_merge(
			(array) $GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS'],
			(array) $GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']
		);
		$numberOfXclasses = count($xclasses);

		if ($numberOfXclasses > 0) {
			$value = sprintf(
				$GLOBALS['LANG']->getLL('status_oldXclassUsageFound'),
				$numberOfXclasses
			);
			$message = '<ol><li>' . implode('</li><li>', $xclasses) . '</li></ol>';
			$severity = tx_reports_reports_status_Status::WARNING;
		}

		return t3lib_div::makeInstance(
			'tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_oldXclassUsage'),
			$value,
			$message,
			$severity
		);
	}
}
?>