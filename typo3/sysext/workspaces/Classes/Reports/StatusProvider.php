<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
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
 * a status provider for the reports module to display whether only 
 * element version is used.
 * 
 * @author Workspaces Team (http://forge.typo3.org/projects/show/typo3v4-workspaces)
 * @package Workspaces
 * @subpackage Service
 */
class Tx_Workspaces_Reports_StatusProvider implements tx_reports_StatusProvider {

	protected $reportList = 'ElementVersioningOnly';

	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @return	array	List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$reports = array();
		$reportMethods = explode(',', $this->reportList);

		foreach ($reportMethods as $reportMethod) {
			$reports[$reportMethod] = $this->{'get' . $reportMethod . 'Status'}();
		}

		return $reports;
	}


	/**
	 * Checks if there are still updates to perform
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether the element versioning is in use or not
	 */
	protected function getElementVersioningOnlyStatus() {
		$severity = tx_reports_reports_status_Status::OK;
		$value    = 'Element Versioning is in use.';
		$message  = 'All Configuration options have been set correctly';

		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['elementVersioningOnly'] || $GLOBALS['TYPO3_CONF_VARS']['BE']['newPagesVersioningType'] != -1) {
			$severity = tx_reports_reports_status_Status::WARNING;
			$value    = 'System not configured correctly.';
			$message  = 'This TYPO3 installation is configured to use Element versioning. Page and Branch versioning are deprecated since TYPO3 4.4.<br />If you are sure that you don\'t use the Workspaces functionality right now (or if you don\'t have any versionized records right now), you can safely change these options in the Install Tool by setting ["BE"]["newPagesVersioningType"] = -1 and ["BE"]["elementVersioningOnly"] = 1 under "All Configuration".';
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Workspaces: Ensure that Element Versioning is used.',
			$value,
			$message,
			$severity
		);
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Reports/StatusProvider.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/workspaces/Classes/Reports/StatusProvider.php']);
}

?>