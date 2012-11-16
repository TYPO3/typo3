<?php
namespace TYPO3\CMS\Reports\Report\Status;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
	 * PHP modules which are required. Can be changed by hook in getMissingPhpModules()
	 *
	 * @var array
	 */
	protected $requiredPhpModules = array(
		'fileinfo',
		'filter',
		'gd',
		'json',
		'mysql',
		'pcre',
		'session',
		'SPL',
		'standard',
		'openssl',
		'xml',
		'zlib',
		'soap',
		'zip'
	);

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @return array List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$this->executeAdminCommand();
		$statuses = array(
			'Php' => $this->getPhpStatus(),
			'PhpMemoryLimit' => $this->getPhpMemoryLimitStatus(),
			'PhpPeakMemory' => $this->getPhpPeakMemoryStatus(),
			'Webserver' => $this->getWebserverStatus(),
			'PhpModules' => $this->getMissingPhpModules()
		);
		return $statuses;
	}

	/**
	 * Checks the current PHP version against a minimum required version.
	 *
	 * @return \TYPO3\CMS\Reports\Status A status of whether a minimum PHP version requirement is met
	 */
	protected function getPhpStatus() {
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if (version_compare(phpversion(), TYPO3_REQUIREMENTS_MINIMUM_PHP) < 0) {
			$message = $GLOBALS['LANG']->getLL('status_phpTooOld');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_phpVersion'), phpversion(), $message, $severity);
	}

	/**
	 * Checks the current memory limit against a minimum required version.
	 *
	 * @return \TYPO3\CMS\Reports\Status A status of whether a minimum memory limit requirement is met
	 */
	protected function getPhpMemoryLimitStatus() {
		$memoryLimit = ini_get('memory_limit');
		$memoryLimitBytes = \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement($memoryLimit);
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if ($memoryLimitBytes > 0) {
			if ($memoryLimitBytes < \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT)) {
				$message = sprintf($GLOBALS['LANG']->getLL('status_phpMemoryRecommendation'), $memoryLimit, TYPO3_REQUIREMENTS_RECOMMENDED_PHP_MEMORY_LIMIT);
				$severity = \TYPO3\CMS\Reports\Status::WARNING;
			}
			if ($memoryLimitBytes < \TYPO3\CMS\Core\Utility\GeneralUtility::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT)) {
				$message = sprintf($GLOBALS['LANG']->getLL('status_phpMemoryRequirement'), $memoryLimit, TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT);
				$severity = \TYPO3\CMS\Reports\Status::ERROR;
			}
			if ($severity > \TYPO3\CMS\Reports\Status::OK) {
				if ($php_ini_path = get_cfg_var('cfg_file_path')) {
					$message .= ' ' . sprintf($GLOBALS['LANG']->getLL('status_phpMemoryEditLimit'), $php_ini_path);
				} else {
					$message .= ' ' . $GLOBALS['LANG']->getLL('status_phpMemoryContactAdmin');
				}
			}
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_phpMemory'), $memoryLimitBytes > 0 ? $memoryLimit : $GLOBALS['LANG']->getLL('status_phpMemoryUnlimited'), $message, $severity);
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
	 * @return tx_reports_reports_status_Status	A status of whether the memory limit was approached by one of the requests
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
	 * Reports the webserver TYPO3 is running on.
	 *
	 * @return \TYPO3\CMS\Reports\Status The server software as a status
	 */
	protected function getWebserverStatus() {
		$value = $_SERVER['SERVER_SOFTWARE'];
		$message = '';
		// The additional information are only important on a Windows system with Apache running.
		// Even with lowest Apache ServerTokens (Prod[uctOnly]) the name is returned.
		if (TYPO3_OS === 'WIN' && substr($value, 0, 6) === 'Apache') {
			$message .= '<p>' . $GLOBALS['LANG']->getLL('status_webServer_infoText') . '</p>';
			$message .= '<div class="typo3-message message-warning">' . $GLOBALS['LANG']->getLL('status_webServer_threadStackSize') . '</div>';
		}
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_webServer'), $value, $message);
	}

	/**
	 * Reports whether any of the required PHP modules are missing
	 *
	 * @return \TYPO3\CMS\Reports\Status A status of missing PHP modules
	 */
	protected function getMissingPhpModules() {
		// Hook to adjust the required PHP modules
		$modules = $this->requiredPhpModules;
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