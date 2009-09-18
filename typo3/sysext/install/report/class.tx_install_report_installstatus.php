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


require_once(t3lib_extMgm::extPath('install', 'requirements.php'));


/**
 * Provides an installation status report
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage tx_install
 */
class tx_install_report_InstallStatus implements tx_reports_StatusProvider {

	protected $reportList = 'Typo3Version,FileSystem,Php,PhpMemoryLimit,PhpRegisterGlobals,Webserver';

	/**
	 * Compiles a collection of system status checks as a status report.
	 *
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
	 * Simply gets the current TYPO3 version.
	 *
	 * @return	tx_reports_reports_status_Status
	 */
	protected function getTypo3VersionStatus() {
		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'TYPO3',
			TYPO3_version,
			'',
			tx_reports_reports_status_Status::NOTICE
		);
	}

	/**
	 * Checks for several directoris being writable.
	 *
	 * @return unknown_type
	 */
	protected function getFileSystemStatus() {
		$value    = 'Writable';
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

			// Requirement level
			// -1 = not required, but if it exists may be writable or not
			//  0 = not required, if it exists the dir should be writable
			//  1 = required, don't has to be writable
			//  2 = required, has to be writable

		$checkWritable = array(
			'typo3temp/'           => 2,
			'typo3temp/pics/'      => 2,
			'typo3temp/temp/'      => 2,
			'typo3temp/llxml/'     => 2,
			'typo3temp/cs/'        => 2,
			'typo3temp/GB/'        => 2,
			'typo3temp/locks/'     => 2,
			'typo3conf/'           => 2,
			'typo3conf/ext/'       => 0,
			'typo3conf/l10n/'      => 0,
			TYPO3_mainDir . 'ext/' => -1,
			'uploads/'             => 2,
			'uploads/pics/'        => 0,
			'uploads/media/'       => 0,
			'uploads/tf/'          => 0,
			'fileadmin/'           => -1,
			'fileadmin/_temp_/'    => 0,
		);

		foreach ($checkWritable as $relPath => $requirementLevel) {
			if (!@is_dir(PATH_site . $relPath)) {
					// If the directory is missing, try to create it
				t3lib_div::mkdir(PATH_site . $relPath);
			}

			if (!@is_dir(PATH_site . $relPath)) {
				if ($requirementLevel > 0) {
						// directory is required
					$value = 'Required Directory Missing';
					$message .= $relPath . ' does not exist and could not be created.<br />';
					$severity = tx_reports_reports_status_Status::ERROR;
				} else {
					if ($requirementLevel == 0) {
						$message .= $relPath . ' does not exist but should be writable if it does exist.<br />';
					} else {
						$message .= $relPath . ' does not exist.<br />';
					}

					if ($severity < tx_reports_reports_status_Status::WARNING) {
						$value = 'Directory not existing';
						$severity = tx_reports_reports_status_Status::WARNING;
					}
				}
			} else {
				if (!is_writable(PATH_site . $relPath)) {
					switch ($requirementLevel) {
						case 0:
							$message .= PATH_site . $relPath . ' should be writable.<br />';

							if ($severity < tx_reports_reports_status_Status::WARNING) {
								$value = 'Directory should be writable';
								$severity = tx_reports_reports_status_Status::WARNING;
							}
							break;
						case 2:
							$value = 'Directory not writable';
							$message .= PATH_site . $relPath . ' must be writable.<br />';
							$severity = tx_reports_reports_status_Status::ERROR;
							break;
					}
				}
			}
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'File System',
			$value,
			$message,
			$severity
		);
	}

	/**
	 * Reports the webserver TYPO3 is running on.
	 *
	 * @return	tx_reports_reports_status_Status	The server software as a status
	 */
	protected function getWebserverStatus() {
		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Web Server',
			$_SERVER['SERVER_SOFTWARE']
		);
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
			$message  = 'Your PHP installation is too old.';
			$severity = tx_reports_reports_status_Status::ERROR;
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'PHP',
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
			$message = 'Depending on your configuration, TYPO3 can run with a ' . $memoryLimit . ' PHP memory limit. However, a ' . TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT . ' PHP memory limit or above is recommended, especially if your site uses additional extensions.';
			$severity = tx_reports_reports_status_Status::WARNING;
		}

		if ($memoryLimit && t3lib_div::getBytesFromSizeMeasurement($memoryLimit) < t3lib_div::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT)) {
			$message = 'Depending on your configuration, TYPO3 can run with a ' . $memoryLimit . ' PHP memory limit. However, a ' . TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT . ' PHP memory limit or above is required, especially if your site uses additional extensions.';
			$severity = tx_reports_reports_status_Status::ERROR;
		}

		if ($severity > tx_reports_reports_status_Status::OK) {
			if ($php_ini_path = get_cfg_var('cfg_file_path')) {
				$message .= ' Increase the memory limit by editing the memory_limit parameter in the file ' . $php_ini_path . ' and then restart your web server (or contact your system administrator or hosting provider for assistance).';
			} else {
				$message .= ' Contact your system administrator or hosting provider for assistance with increasing your PHP memory limit.';
			}
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'PHP Memory Limit', $memoryLimit, $message, $severity
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
			$message = '<em>register_globals</em> is enabled. TYPO3 requires this configuration directive to be disabled. Your site may not be secure when <em>register_globals</em> is enabled. The PHP manual has instructions for <a href="http://php.net/configuration.changes">how to change configuration settings</a>.';
			$severity = tx_reports_reports_status_Status::ERROR;
			$value = $GLOBALS['LANG']->getLL('status_enabled')
				. ' (\'' . $registerGlobals . '\')';
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'PHP Register Globals', $value, $message, $severity
		);
	}

}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/report/class.tx_install_report_installstatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/report/class.tx_install_report_installstatus.php']);
}

?>