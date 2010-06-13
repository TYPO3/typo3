<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Ingo Renner <ingo@typo3.org>
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
 * Performs several checks about the system's health
 *
 * @author		Ingo Renner <ingo@typo3.org>
 * @package		TYPO3
 * @subpackage	reports
 */
class tx_reports_reports_status_SystemStatus implements tx_reports_StatusProvider {

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @return	array	List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$statuses = array(
			'Php'                 => $this->getPhpStatus(),
			'PhpMemoryLimit'      => $this->getPhpMemoryLimitStatus(),
			'PhpRegisterGlobals'  => $this->getPhpRegisterGlobalsStatus(),
			'Webserver'           => $this->getWebserverStatus(),
		);

		return $statuses;
	}


	/**
	 * Checks the current PHP version against a minimum required version.
	 *
	 * @return	tx_reports_reports_status_Status	A status of whether a minimum PHP version requirment is met
	 */
	protected function getPhpStatus() {
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		if (version_compare(phpversion(), TYPO3_REQUIREMENTS_MINIMUM_PHP) < 0) {
			$message  = $GLOBALS['LANG']->getLL('status_phpTooOld');
			$severity = tx_reports_reports_status_Status::ERROR;
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_phpVersion'),
			phpversion(),
			$message,
			$severity
		);
	}

	/**
	 * Checks the current memory limit against a minimum required version.
	 *
	 * @return	tx_reports_reports_status_Status	A status of whether a minimum memory limit requirment is met
	 */
	protected function getPhpMemoryLimitStatus() {
		$memoryLimit = ini_get('memory_limit');
		$message     = '';
		$severity    = tx_reports_reports_status_Status::OK;

		if ($memoryLimit && t3lib_div::getBytesFromSizeMeasurement($memoryLimit) < t3lib_div::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT)) {
			$message = sprintf($GLOBALS['LANG']->getLL('status_phpMemoryRecommendation'), $memoryLimit, TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT);
			$severity = tx_reports_reports_status_Status::WARNING;
		}

		if ($memoryLimit && t3lib_div::getBytesFromSizeMeasurement($memoryLimit) < t3lib_div::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT)) {
			$message = sprintf($GLOBALS['LANG']->getLL('status_phpMemoryRequirement'), $memoryLimit, TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT);
			$severity = tx_reports_reports_status_Status::ERROR;
		}

		if ($severity > tx_reports_reports_status_Status::OK) {
			if ($php_ini_path = get_cfg_var('cfg_file_path')) {
				$message .= ' ' . sprintf($GLOBALS['LANG']->getLL('status_phpMemoryEditLimit'), $php_ini_path);
			} else {
				$message .= ' ' . $GLOBALS['LANG']->getLL('status_phpMemoryContactAdmin');
			}
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_phpMemory'), $memoryLimit, $message, $severity
		);
	}

	/**
	 * checks whether register globals is on or off.
	 *
	 * @return	tx_reports_reports_status_Status	A status of whether register globals is on or off
	 */
	protected function getPhpRegisterGlobalsStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_disabled');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		$registerGlobals = trim(ini_get('register_globals'));

			// can't reliably check for 'on', therefore checking for the oposite 'off', '', or 0
		if (!empty($registerGlobals) && strtolower($registerGlobals) != 'off') {
			$registerGlobalsHighlight = '<em>register_globals</em>';
			$phpManualLink .= '<a href="http://php.net/configuration.changes">' . $GLOBALS['LANG']->getLL('status_phpRegisterGlobalsHowToChange') . '</a>';
			$message  = sprintf($GLOBALS['LANG']->getLL('status_phpRegisterGlobalsEnabled'), $registerGlobalsHighlight);
			$message .= ' ' . sprintf($GLOBALS['LANG']->getLL('status_phpRegisterGlobalsSecurity'), $registerGlobalsHighlight);
			$message .= ' ' . sprintf($GLOBALS['LANG']->getLL('status_phpRegisterGlobalsPHPManual'), $phpManualLink);
			$severity = tx_reports_reports_status_Status::ERROR;
			$value = $GLOBALS['LANG']->getLL('status_enabled')
				. ' (\'' . $registerGlobals . '\')';
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_phpRegisterGlobals'), $value, $message, $severity
		);
	}

	/**
	 * Reports the webserver TYPO3 is running on.
	 *
	 * @return	tx_reports_reports_status_Status	The server software as a status
	 */
	protected function getWebserverStatus() {
		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_webServer'),
			$_SERVER['SERVER_SOFTWARE']
		);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_systemstatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_systemstatus.php']);
}

?>