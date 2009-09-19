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
 * Performs several checks about the system's health
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_reports_status_SystemStatus implements tx_reports_StatusProvider {

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$statuses = array(
			'encryptionKeyEmpty'  => $this->getEncryptionKeyStatus(),
			'fileDenyPattern'     => $this->getFileDenyPatternStatus(),
			'htaccessUpload'      => $this->getHtaccessUploadStatus(),
			'remainingUdates'     => $this->getRemainingUpdatesStatus(),
			'emptyReferenceIndex' => $this->getReferenceIndexStatus()
		);

		if ($this->isMemcachedUsed()) {
			$statuses['memcachedConnection'] = $this->getMemcachedConnectionStatus();
		}

		return $statuses;
	}

	/**
	 * Checks whether the encryption key is empty.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether the encryption key is empty or not
	 */
	protected function getEncryptionKeyStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
			$value    = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;

			$url = 'install/index.php?redirect_url=index.php'
				. urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_encryption'),
				'<a href="' . $url . '">',
				'</a>'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Encryption Key', $value, $message, $severity
		);
	}

	/**
	 * Checks if fileDenyPattern was changed which is dangerous on Apache
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether the file deny pattern has changed
	 */
	protected function getFileDenyPatternStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT) {
			$value    = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;

			$url = 'install/index.php?redirect_url=index.php'
				. urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.file_deny_pattern'),
				'<br /><pre>'
				. htmlspecialchars(FILE_DENY_PATTERN_DEFAULT)
				. '</pre><br />'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'File Deny Pattern', $value, $message, $severity
		);
	}

	/**
	 * Checks if fileDenyPattern allows to upload .htaccess files which is
	 * dangerous on Apache.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether it's possible to upload .htaccess files
	 */
	protected function getHtaccessUploadStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT && t3lib_div::verifyFilenameAgainstDenyPattern('.htaccess')) {
			$value    = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;
			$message  = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.file_deny_htaccess');
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'.htaccess Upload Protection', $value, $message, $severity
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

	/**
	 * Checks if sys_refindex is empty.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether the reference index is empty or not
	 */
	protected function getReferenceIndexStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'sys_refindex');

		if (!$count) {
			$value    = $GLOBALS['LANG']->getLL('status_empty');
			$severity = tx_reports_reports_status_Status::WARNING;

			$url = 'sysext/lowlevel/dbint/index.php?&id=0&SET[function]=refindex';
			$message  = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.backend_reference'),
				'<a href="' . $url . '">',
				'</a>'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Reference Index', $value, $message, $severity
		);
	}

	/**
	 * Checks whether memcached is configured, if that's the case we asume it's also used.
	 *
	 * @return	boolean	True if memcached is used, false otherwise.
	 */
	protected function isMemcachedUsed() {
		$memcachedUsed = false;

		$memcachedServers = $this->getConfiguredMemcachedServers();
		if (count($memcachedServers)) {
			$memcachedUsed = true;
		}

		return $memcachedUsed;
	}

	/**
	 * Gets the configured memcached server connections.
	 *
	 * @return	array 	An array of configured memcached server connections.
	 */
	protected function getConfiguredMemcachedServers() {
		$memcachedServers = array();

		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'] as $table => $conf) {
				if (is_array($conf)) {
					foreach ($conf as $key => $value) {
						if (!is_array($value) && $value === 't3lib_cache_backend_MemcachedBackend') {
							$memcachedServers = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'][$table]['options']['servers'];
							break;
						}
					}
				}
			}
		}

		return $memcachedServers;
	}

	/**
	 * Checks whether TYPO3 can connect to the configured memcached servers.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether TYPO3 can connect to the configured memcached servers
	 */
	protected function getMemcachedConnectionStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		$failedConnections    = array();
		$defaultMemcachedPort = ini_get('memcache.default_port');
		$memcachedServers     = $this->getConfiguredMemcachedServers();

		if (function_exists('memcache_connect') && is_array($memcachedServers)) {
			foreach ($memcachedServers as $testServer) {
				$configuredServer = $testServer;
				if (substr($testServer, 0, 7) == 'unix://') {
					$host = $testServer;
					$port = 0;
				} else {
					if (substr($testServer, 0, 6) === 'tcp://') {
						$testServer = substr($testServer, 6);
					}
					if (strstr($testServer, ':') !== FALSE) {
						list($host, $port) = explode(':', $testServer, 2);
					} else {
						$host = $testServer;
						$port = $defaultPort;
					}
				}
				$memcachedConnection = @memcache_connect($host, $port);
				if ($memcachedConnection != null) {
					memcache_close($memcachedConnection);
				} else {
					$failedConnections[] = $configuredServer;
				}
			}
		}

		if (count($failedConnections)) {
			$value    = $GLOBALS['LANG']->getLL('status_connectionFailed');
			$severity = tx_reports_reports_status_Status::WARNING;

			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.memcache_not_usable')
				. '<br /><br />'
				. '<ul><li>'
				. implode('</li><li>', $failedConnections)
				. '</li></ul>';
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Memcached Configuration', $value, $message, $severity
		);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_systemstatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_systemstatus.php']);
}

?>