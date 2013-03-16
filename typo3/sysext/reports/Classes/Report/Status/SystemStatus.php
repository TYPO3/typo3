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
 * Performs several checks about the system's health
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class SystemStatus implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @return array List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$this->executeAdminCommand();
		$statuses = array(
			'PhpPeakMemory' => $this->getPhpPeakMemoryStatus(),
			'PhpModules' => $this->getMissingPhpModulesOfExtensions()
		);
		return $statuses;
	}

	/**
	 * Executes commands like clearing the memory status flag
	 *
	 * @return void
	 */
	protected function executeAdminCommand() {
		$command = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('adminCmd');
		switch ($command) {
		case 'clear_peak_memory_usage_flag':
			/** @var $registry \TYPO3\CMS\Core\Registry */
			$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
			$registry->remove('core', 'reports-peakMemoryUsage');
			break;
		}
	}

	/**
	 * Checks if there was a request in the past which approached the memory limit
	 *
	 * @return \TYPO3\CMS\Reports\Status A status of whether the memory limit was approached by one of the requests
	 */
	protected function getPhpPeakMemoryStatus() {
		/** @var $registry \TYPO3\CMS\Core\Registry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$peakMemoryUsage = $registry->get('core', 'reports-peakMemoryUsage');
		$memoryLimit = \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement(ini_get('memory_limit'));
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		$bytesUsed = $peakMemoryUsage['used'];
		$percentageUsed = $memoryLimit ? number_format($bytesUsed / $memoryLimit * 100, 1) . '%' : '?';
		$dateOfPeak = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $peakMemoryUsage['tstamp']);
		$urlOfPeak = '<a href="' . htmlspecialchars($peakMemoryUsage['url']) . '">' . htmlspecialchars($peakMemoryUsage['url']) . '</a>';
		$clearFlagUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&amp;adminCmd=clear_peak_memory_usage_flag';
		if ($peakMemoryUsage['used']) {
			$message = sprintf($GLOBALS['LANG']->getLL('status_phpPeakMemoryTooHigh'), \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($peakMemoryUsage['used']), $percentageUsed, \TYPO3\CMS\Core\Utility\GeneralUtility::formatSize($memoryLimit), $dateOfPeak, $urlOfPeak);
			$message .= ' <a href="' . $clearFlagUrl . '">' . $GLOBALS['LANG']->getLL('status_phpPeakMemoryClearFlag') . '</a>.';
			$severity = \TYPO3\CMS\Reports\Status::WARNING;
			$value = $percentageUsed;
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_phpPeakMemory'), $value, $message, $severity);
	}

	/**
	 * Reports whether extensions need additional PHP modules different from standard core requirements
	 *
	 * @return \TYPO3\CMS\Reports\Status A status of missing PHP modules
	 */
	protected function getMissingPhpModulesOfExtensions() {
		$modules = array();
		if (is_array(${$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules']})) {
			foreach (${$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install/mod/class.tx_install.php']['requiredPhpModules']} as $classData) {
				$hookObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
				$modules = $hookObject->setRequiredPhpModules($modules, $this);
			}
		}
		$missingPhpModules = array();
		foreach ($modules as $module) {
			if (is_array($module)) {
				$detectedSubmodules = FALSE;
				foreach ($module as $submodule) {
					if (extension_loaded($submodule)) {
						$detectedSubmodules = TRUE;
					}
				}
				if ($detectedSubmodules === FALSE) {
					$missingPhpModules[] = sprintf($GLOBALS['LANG']->getLL('status_phpModulesGroup'), '(' . implode(', ', $module) . ')');
				}
			} elseif (!extension_loaded($module)) {
				$missingPhpModules[] = $module;
			}
		}
		if (count($missingPhpModules) > 0) {
			$value = $GLOBALS['LANG']->getLL('status_phpModulesMissing');
			$message = sprintf($GLOBALS['LANG']->getLL('status_phpModulesList'), implode(', ', $missingPhpModules));
			$message .= ' ' . $GLOBALS['LANG']->getLL('status_phpModulesInfo');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
		} else {
			$value = $GLOBALS['LANG']->getLL('status_phpModulesPresent');
			$message = '';
			$severity = \TYPO3\CMS\Reports\Status::OK;
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_phpModules'), $value, $message, $severity);
	}

}


?>