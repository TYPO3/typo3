<?php
namespace TYPO3\CMS\Reports\Report\Status;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Ingo Renner <ingo@typo3.org>
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
 */
class Typo3Status implements \TYPO3\CMS\Reports\StatusProviderInterface {

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
			'registeredXclass' => $this->getRegisteredXclassStatus(),
		);
		return $statuses;
	}

	/**
	 * Simply gets the current TYPO3 version.
	 *
	 * @return \TYPO3\CMS\Reports\Status
	 */
	protected function getTypo3VersionStatus() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', 'TYPO3', TYPO3_version, '', \TYPO3\CMS\Reports\Status::NOTICE);
	}

	/**
	 * Check for usage of old way of implementing XCLASSes
	 *
	 * @return \TYPO3\CMS\Reports\Status
	 */
	protected function getOldXclassUsageStatus() {
		$message = '';
		$value = $GLOBALS['LANG']->getLL('status_none');
		$severity = \TYPO3\CMS\Reports\Status::OK;

		$xclasses = array_merge(
			(array) $GLOBALS['TYPO3_CONF_VARS']['BE']['XCLASS'],
			(array) $GLOBALS['TYPO3_CONF_VARS']['FE']['XCLASS']
		);

		$numberOfXclasses = count($xclasses);
		if ($numberOfXclasses > 0) {
			$value = sprintf($GLOBALS['LANG']->getLL('status_oldXclassUsageFound'), $numberOfXclasses);
			$message = $GLOBALS['LANG']->getLL('status_oldXclassUsageFound_message') . '<br />';
			$message .= '<ol><li>' . implode('</li><li>', $xclasses) . '</li></ol>';
			$severity = \TYPO3\CMS\Reports\Status::NOTICE;
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Reports\\Status',
			$GLOBALS['LANG']->getLL('status_oldXclassUsage'),
			$value,
			$message,
			$severity
		);
	}

	/**
	 * List any Xclasses registered in the stystem
	 *
	 * @return \TYPO3\CMS\Reports\Status
	 */
	protected function getRegisteredXclassStatus() {
		$message = '';
		$value = $GLOBALS['LANG']->getLL('status_none');
		$severity = \TYPO3\CMS\Reports\Status::OK;

		$xclassFoundArray = array();
		if (array_key_exists('Objects', $GLOBALS['TYPO3_CONF_VARS']['SYS'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'] as $originalClass => $override) {
				if (array_key_exists('className', $override)) {
					$xclassFoundArray[$originalClass] = $override['className'];
				}
			}
		}
		if (count($xclassFoundArray) > 0) {
			$value = $GLOBALS['LANG']->getLL('status_xclassUsageFound');
			$message = $GLOBALS['LANG']->getLL('status_xclassUsageFound_message') . '<br />';
			$message .= '<ol>';
			foreach ($xclassFoundArray as $originalClass => $xClassName) {
				$messageDetail = sprintf(
					$GLOBALS['LANG']->getLL('status_xclassUsageFound_message_detail'),
					$originalClass,
					$xClassName
				);
				$message .= '<li>' . $messageDetail . '</li>';
			}
			$message .= '</ol>';
			$severity = \TYPO3\CMS\Reports\Status::NOTICE;
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Reports\\Status',
			$GLOBALS['LANG']->getLL('status_xclassUsage'),
			$value,
			$message,
			$severity
		);
	}

}


?>