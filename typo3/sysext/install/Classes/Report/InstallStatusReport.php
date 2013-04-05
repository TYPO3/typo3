<?php
namespace TYPO3\CMS\Install\Report;

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
 * Provides an installation status report
 *
 * @author 		Ingo Renner <ingo@typo3.org>
 */
class InstallStatusReport implements \TYPO3\CMS\Reports\StatusProviderInterface {

	protected $reportList = 'FileSystem,RemainingUpdates';

	/**
	 * Compiles a collection of system status checks as a status report.
	 *
	 * @return array<\TYPO3\CMS\Reports\Status>
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
	 * Checks for several directories being writable.
	 *
	 * @return \TYPO3\CMS\Reports\Status Indicates status of the file system
	 */
	protected function getFileSystemStatus() {
		$value = $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_writable');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		// Requirement level
		// -1 = not required, but if it exists may be writable or not
		//  0 = not required, if it exists the dir should be writable
		//  1 = required, don't has to be writable
		//  2 = required, has to be writable
		$checkWritable = array(
			'typo3temp/' => 2,
			'typo3temp/pics/' => 2,
			'typo3temp/temp/' => 2,
			'typo3temp/llxml/' => 2,
			'typo3temp/cs/' => 2,
			'typo3temp/GB/' => 2,
			'typo3temp/locks/' => 2,
			'typo3conf/' => 2,
			'typo3conf/ext/' => 0,
			'typo3conf/l10n/' => 0,
			TYPO3_mainDir . 'ext/' => -1,
			'uploads/' => 2,
			'uploads/pics/' => 0,
			'uploads/media/' => 0,
			$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] => -1,
			$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '_temp_/' => 0
		);
		foreach ($checkWritable as $relPath => $requirementLevel) {
			if (!@is_dir((PATH_site . $relPath))) {
				// If the directory is missing, try to create it
				\TYPO3\CMS\Core\Utility\GeneralUtility::mkdir(PATH_site . $relPath);
			}
			if (!@is_dir((PATH_site . $relPath))) {
				if ($requirementLevel > 0) {
					// directory is required
					$value = $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_missingDirectory');
					$message .= sprintf($GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_directoryDoesNotExistCouldNotCreate'), $relPath) . '<br />';
					$severity = \TYPO3\CMS\Reports\Status::ERROR;
				} else {
					$message .= sprintf($GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_directoryDoesNotExist'), $relPath);
					if ($requirementLevel == 0) {
						$message .= ' ' . $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_directoryShouldAlsoBeWritable');
					}
					$message .= '<br />';
					if ($severity < \TYPO3\CMS\Reports\Status::WARNING) {
						$value = $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_nonExistingDirectory');
						$severity = \TYPO3\CMS\Reports\Status::WARNING;
					}
				}
			} else {
				if (!is_writable((PATH_site . $relPath))) {
					switch ($requirementLevel) {
					case 0:
						$message .= sprintf($GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_directoryShouldBeWritable'), (PATH_site . $relPath)) . '<br />';
						if ($severity < \TYPO3\CMS\Reports\Status::WARNING) {
							$value = $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_recommendedWritableDirectory');
							$severity = \TYPO3\CMS\Reports\Status::WARNING;
						}
						break;
					case 2:
						$value = $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_requiredWritableDirectory');
						$message .= sprintf($GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_directoryMustBeWritable'), (PATH_site . $relPath)) . '<br />';
						$severity = \TYPO3\CMS\Reports\Status::ERROR;
						break;
					}
				}
			}
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_fileSystem'), $value, $message, $severity);
	}

	/**
	 * Checks if there are still updates to perform
	 *
	 * @return \TYPO3\CMS\Reports\Status Represents whether the installation is completely updated yet
	 */
	protected function getRemainingUpdatesStatus() {
		$value = $GLOBALS['LANG']->getLL('status_updateComplete');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if (!\TYPO3\CMS\Core\Utility\GeneralUtility::compat_version(TYPO3_branch)) {
			$value = $GLOBALS['LANG']->getLL('status_updateIncomplete');
			$severity = \TYPO3\CMS\Reports\Status::WARNING;
			$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=update');
			$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_update'), '<a href="' . $url . '">', '</a>');
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->sL('LLL:EXT:install/report/locallang.xml:status_remainingUpdates'), $value, $message, $severity);
	}

}


?>