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

	protected $reportList = 'Typo3Version,FileSystem,RemainingUpdates';

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
	 * Checks if there are still updates to perform
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether the installation is not completely updated yet
	 */
	protected function getRemainingUpdatesStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_updateComplete');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		if (!t3lib_div::compat_version(TYPO3_branch)) {
			$value    = $GLOBALS['LANG']->getLL('status_updateIncomplete');
			$severity = tx_reports_reports_status_Status::WARNING;

			$url = 'install/index.php?redirect_url=index.php'
				. urlencode('?TYPO3_INSTALL[type]=update');
			$message  = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_update'),
				'<a href="' . $url . '">',
				'</a>'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Remaining Updates', $value, $message, $severity
		);
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/report/class.tx_install_report_installstatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/report/class.tx_install_report_installstatus.php']);
}

?>