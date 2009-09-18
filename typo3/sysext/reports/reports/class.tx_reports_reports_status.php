<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Ingo Renner <ingo@typo3.org>
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
 * The status report
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_reports_Status implements tx_reports_Report {

	protected $statusProviders = array();

	/**
	 * constructor for class tx_reports_report_Status
	 */
	public function __construct() {
		$this->getStatusProviders();

		$GLOBALS['LANG']->includeLLFile('EXT:reports/reports/locallang.xml');
	}

	/**
	 * Takes care of creating / rendering the status report
	 *
	 * @return	string	The status report as HTML
	 */
	public function getReport() {
		$status  = array();
		$content = '';

		foreach ($this->statusProviders as $statusProvider) {
			$status += $statusProvider->getStatus();
		}

		$content .= '<p class="help">'
			. $GLOBALS['LANG']->getLL('status_report_explanation')
			. '</p>';

		return $content . $this->renderStatus($status);
	}

	/**
	 * Gets all registered status providers and creates instances of them.
	 *
	 * @return	void
	 */
	protected function getStatusProviders() {
		ksort($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status']);

		foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['reports']['tx_reports']['status'] as $key => $statusProvider) {
			if (t3lib_div::inList('title,description,report', $key)) {
				continue; // skip (for this report) unneccessary data
			}

			$statusProviderInstance = t3lib_div::makeInstance($statusProvider);
			if ($statusProviderInstance instanceof tx_reports_StatusProvider) {
				$this->statusProviders[$key] = $statusProviderInstance;
			}
		}
	}

	/**
	 * Renders a the system's status
	 *
	 * @param	array	An array of statuses as returned by the available status providers
	 * @return	string	The system status as an HTML table
	 */
	protected function renderStatus(array $statusCollection) {
		$content = '<table class="system-status-report">';
		$classes = array(
			tx_reports_reports_status_Status::NOTICE  => 'notice',
			tx_reports_reports_status_Status::INFO    => 'information',
			tx_reports_reports_status_Status::OK      => 'ok',
			tx_reports_reports_status_Status::WARNING => 'warning',
			tx_reports_reports_status_Status::ERROR   => 'error',
		);

		foreach ($statusCollection as $status) {
			$class = 'typo3-message message-' . $classes[$status->getSeverity()];
			$description = $status->getMessage();

			if (empty($description)) {
				$content .= '<tr><th class="'. $class .'">'. $status->getTitle() .'</th><td class="'. $class .'">'. $status->getValue() .'</td></tr>';
			} else {
				$content .= '<tr><th class="'. $class .' merge-down">'. $status->getTitle() .'</th><td class="'. $class .' merge-down">'. $status->getValue() .'</td></tr>';
				$content .= '<tr><td class="'. $class .' merge-up" colspan="2">'. $description .'</td></tr>';
			}
		}

		return $content . '</table>';
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/class.tx_reports_reports_status.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/class.tx_reports_reports_status.php']);
}

?>