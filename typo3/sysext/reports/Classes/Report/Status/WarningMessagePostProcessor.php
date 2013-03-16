<?php
namespace TYPO3\CMS\Reports\Report\Status;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Ingo Renner <ingo@typo3.org>
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
 * @author Ingo Renner <ingo@typo3.org>
 */
class WarningMessagePostProcessor {

	/**
	 * Tries to get the highest severity of the system's status first, if
	 * something is found it is asumed that the status update task is set up
	 * properly or the status report has been checked manually and we take over
	 * control over the system warning messages.
	 *
	 * @param array $warningMessages An array of messages related to already found issues.
	 */
	public function displayWarningMessages_postProcess(array &$warningMessages) {
		// Get highest severity
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$highestSeverity = $registry->get('tx_reports', 'status.highestSeverity', NULL);
		if (!is_null($highestSeverity)) {
			// Status update has run, so taking over control over the core messages
			unset($warningMessages['install_password'], $warningMessages['backend_admin'], $warningMessages['install_enabled'], $warningMessages['install_encryption'], $warningMessages['file_deny_pattern'], $warningMessages['file_deny_htaccess'], $warningMessages['install_update'], $warningMessages['backend_reference'], $warningMessages['memcached']);
			if ($highestSeverity > \TYPO3\CMS\Reports\Status::OK) {
				// Display a message that there's something wrong and that
				// the admin should take a look at the detailed status report
				$GLOBALS['LANG']->includeLLFile('EXT:reports/reports/locallang.xml');
				$reportModuleIdentifier = 'tools_ReportsTxreportsm1';
				$reportModuleParameters = array(
					'tx_reports_tools_reportstxreportsm1[extension]=tx_reports',
					'tx_reports_tools_reportstxreportsm1[report]=status',
					'tx_reports_tools_reportstxreportsm1[action]=detail',
					'tx_reports_tools_reportstxreportsm1[controller]=Report',
				);
				$warningMessages['tx_reports_status_notification'] = sprintf(
					$GLOBALS['LANG']->getLL('status_problemNotification'),
					'<a href="javascript:top.goToModule(\'' . $reportModuleIdentifier . '\', 1, \'&' . implode('&', $reportModuleParameters) .  '\');">', '</a>'
				);
			}
		}
	}

}


?>