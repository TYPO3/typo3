<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010-2011 Ingo Renner <ingo@typo3.org>
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
 * Post processes the warning messages found in about modules.
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_reports_status_WarningMessagePostProcessor {

	/**
	 * Tries to get the highest severity of the system's status first, if
	 * something is found it is asumed that the status update task is set up
	 * properly or the status report has been checked manually and we take over
	 * control over the system warning messages.
	 *
	 * @param	array	An array of messages related to already found issues.
	 */
	public function displayWarningMessages_postProcess(array &$warningMessages) {

			// get highest severity
		$registry = t3lib_div::makeInstance('t3lib_Registry');
		$highestSeverity = $registry->get(
			'tx_reports',
			'status.highestSeverity',
			NULL
		);

		if (!is_null($highestSeverity)) {
				// status update has run, so taking over control over the core messages
			unset(
				$warningMessages['install_password'],
				$warningMessages['backend_admin'],
				$warningMessages['install_enabled'],
				$warningMessages['install_encryption'],
				$warningMessages['file_deny_pattern'],
				$warningMessages['file_deny_htaccess'],
				$warningMessages['install_update'],
				$warningMessages['backend_reference'],
				$warningMessages['memcached']
			);

			if ($highestSeverity > tx_reports_reports_status_Status::OK) {
					// display a message that there's something wrong and that
					// the admin should take a look at the detailed status report
				$GLOBALS['LANG']->includeLLFile('EXT:reports/reports/locallang.xml');

				$warningMessages['tx_reports_status_notification'] = sprintf(
					$GLOBALS['LANG']->getLL('status_problemNotification'),
					'<a href="javascript:top.goToModule(\'tools_txreportsM1\', 1, \'&SET[function]=tx_reports.status\');">',
					'</a>'
				);
			}
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_warningmessagepostprocessor.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_warningmessagepostprocessor.php']);
}

?>