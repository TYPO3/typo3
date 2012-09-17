<?php
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
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_reports_status_SecurityStatus implements tx_reports_StatusProvider {

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @return array List of statuses
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$this->executeAdminCommand();

		$statuses = array(
			'adminUserAccount' => $this->getAdminAccountStatus(),
			'encryptionKeyEmpty' => $this->getEncryptionKeyStatus(),
			'fileDenyPattern' => $this->getFileDenyPatternStatus(),
			'htaccessUpload' => $this->getHtaccessUploadStatus(),
			'installToolEnabled' => $this->getInstallToolProtectionStatus(),
			'installToolPassword' => $this->getInstallToolPasswordStatus(),
			'saltedpasswords' => $this->getSaltedPasswordsStatus()
		);

		return $statuses;
	}

	/**
	 * Checks whether a an BE user account named admin with default password exists.
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing whether a default admin account exists
	 */
	protected function getAdminAccountStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		$whereClause = 'username = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('admin', 'be_users') .
			t3lib_BEfunc::deleteClause('be_users');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, username, password',
			'be_users',
			$whereClause
		);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$secure = TRUE;

				// Check against salted password
			if (t3lib_extMgm::isLoaded('saltedpasswords')) {
				if (tx_saltedpasswords_div::isUsageEnabled('BE')) {
						/** @var $saltingObject tx_saltedpasswords_salts */
					$saltingObject = tx_saltedpasswords_salts_factory::getSaltingInstance($row['password']);
					if (is_object($saltingObject)) {
						if ($saltingObject->checkPassword('password', $row['password'])) {
							$secure = FALSE;
						}
					}
				}
			}

				// Check against plain MD5
			if ($row['password'] === '5f4dcc3b5aa765d61d8327deb882cf99') {
				$secure = FALSE;
			}

			if (!$secure) {
				$value = $GLOBALS['LANG']->getLL('status_insecure');
				$severity = tx_reports_reports_status_Status::ERROR;

				$editUserAccountUrl = 'alt_doc.php?returnUrl=mod.php?M=tools_txreportsM1&edit[be_users][' . $row['uid'] . ']=edit';
				$message = sprintf(
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.backend_admin'),
					'<a href="' . $editUserAccountUrl . '">',
					'</a>'
				);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_adminUserAccount'), $value, $message, $severity
		);
	}

	/**
	 * Checks whether the encryption key is empty.
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing whether the encryption key is empty or not
	 */
	protected function getEncryptionKeyStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;
			$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_encryption'),
				'<a href="' . $url . '">',
				'</a>'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_encryptionKey'), $value, $message, $severity
		);
	}

	/**
	 * Checks if fileDenyPattern was changed which is dangerous on Apache
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing whether the file deny pattern has changed
	 */
	protected function getFileDenyPatternStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		$defaultParts = t3lib_div::trimExplode('|', FILE_DENY_PATTERN_DEFAULT, TRUE);
		$givenParts = t3lib_div::trimExplode('|', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'], TRUE);
		$result = array_intersect($defaultParts, $givenParts);
		if ($defaultParts !== $result) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;
			$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.file_deny_pattern_partsNotPresent'),
				'<br /><pre>' .	htmlspecialchars(FILE_DENY_PATTERN_DEFAULT) . '</pre><br />'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_fileDenyPattern'), $value, $message, $severity
		);
	}

	/**
	 * Checks if fileDenyPattern allows to upload .htaccess files which is
	 * dangerous on Apache.
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing whether it's possible to upload .htaccess files
	 */
	protected function getHtaccessUploadStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT && t3lib_div::verifyFilenameAgainstDenyPattern('.htaccess')) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.file_deny_htaccess');
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_htaccessUploadProtection'), $value, $message, $severity
		);
	}

	/**
	 * Checks whether memcached is configured, if that's the case we asume it's also used.
	 *
	 * @return boolean TRUE if memcached is used, FALSE otherwise.
	 */
	protected function isMemcachedUsed() {
		$memcachedUsed = FALSE;

		$memcachedServers = $this->getConfiguredMemcachedServers();
		if (count($memcachedServers)) {
			$memcachedUsed = TRUE;
		}

		return $memcachedUsed;
	}


	/**
	 * Executes commands like removing the Install Tool enable file.
	 *
	 * @return void
	 */
	protected function executeAdminCommand() {
		$command = t3lib_div::_GET('adminCmd');

		switch ($command) {
			case 'remove_ENABLE_INSTALL_TOOL':
				unlink(PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL');
			break;
		}
	}

	/**
	 * Checks whether the Install Tool password is set to its default value.
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing the security of the install tool password
	 */
	protected function getInstallToolPasswordStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] == md5('joh316')) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;

			$changeInstallToolPasswordUrl = 'mod.php?M=tools_install';

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_password'),
				'<a href="' . $changeInstallToolPasswordUrl . '">',
				'</a>'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_installToolPassword'), $value, $message, $severity
		);
	}

	/**
	 * Checks whether the Install Tool password is set to its default value.
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing the security of the saltedpassswords extension
	 */
	protected function getSaltedPasswordsStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		if (!t3lib_extMgm::isLoaded('saltedpasswords')) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;
			$message .= $GLOBALS['LANG']->getLL('status_saltedPasswords_notInstalled');
		} else {
				/** @var tx_saltedpasswords_emconfhelper $configCheck */
			$configCheck = t3lib_div::makeInstance('tx_saltedpasswords_emconfhelper');
			$message = '<p>' . $GLOBALS['LANG']->getLL('status_saltedPasswords_infoText') . '</p>';
			$messageDetail = '';
			$flashMessage = $configCheck->checkConfigurationBackend(array(), new t3lib_tsStyleConfig());

			if (strpos($flashMessage, 'message-error') !== FALSE) {
				$value = $GLOBALS['LANG']->getLL('status_insecure');
				$severity = tx_reports_reports_status_Status::ERROR;
				$messageDetail .= $flashMessage;
			}
			if (strpos($flashMessage, 'message-warning') !== FALSE) {
				$severity = tx_reports_reports_status_Status::WARNING;
				$messageDetail .= $flashMessage;
			}
			if (strpos($flashMessage, 'message-information') !== FALSE) {
				$messageDetail .= $flashMessage;
			}

			$unsecureUserCount = tx_saltedpasswords_div::getNumberOfBackendUsersWithInsecurePassword();
			if ($unsecureUserCount > 0) {
				$value = $GLOBALS['LANG']->getLL('status_insecure');
				$severity = tx_reports_reports_status_Status::ERROR;
				$messageDetail .= '<div class="typo3-message message-warning">' .
					$GLOBALS['LANG']->getLL('status_saltedPasswords_notAllPasswordsHashed') . '</div>';
			}

			$message .= $messageDetail;
			if (empty($messageDetail)) {
				$message = '';
			}
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_saltedPasswords'), $value, $message, $severity
		);
	}

	/**
	 * Checks for the existance of the ENABLE_INSTALL_TOOL file.
	 *
	 * @return tx_reports_reports_status_Status An tx_reports_reports_status_Status object representing whether ENABLE_INSTALL_TOOL exists
	 */
	protected function getInstallToolProtectionStatus() {
		$enableInstallToolFile = PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL';
		$value = $GLOBALS['LANG']->getLL('status_disabled');
		$message = '';
		$severity = tx_reports_reports_status_Status::OK;

		$enableInstallToolFileExists = is_file($enableInstallToolFile);

		if ($enableInstallToolFileExists) {
			if (trim(file_get_contents($enableInstallToolFile)) === 'KEEP_FILE') {
				$severity = tx_reports_reports_status_Status::WARNING;
				$disableInstallToolUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL') .
					'&amp;adminCmd=remove_ENABLE_INSTALL_TOOL';
				$value = $GLOBALS['LANG']->getLL('status_enabledPermanently');

				$message = sprintf(
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_enabled'),
					'<span style="white-space: nowrap;">' . $enableInstallToolFile . '</span>');
				$message .= ' <a href="' . $disableInstallToolUrl . '">' .
					$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_enabled_cmd') .
					'</a>';
			} else {
				$enableInstallToolFileTtl = filemtime($enableInstallToolFile) + 3600 - time();
				if ($enableInstallToolFileTtl <= 0) {
					unlink($enableInstallToolFile);
				} else {
					$severity = tx_reports_reports_status_Status::NOTICE;
					$disableInstallToolUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL') .
						'&amp;adminCmd=remove_ENABLE_INSTALL_TOOL';
					$value = $GLOBALS['LANG']->getLL('status_enabledTemporarily');

					$message = sprintf(
						$GLOBALS['LANG']->getLL('status_installEnabledTemporarily'),
						'<span style="white-space: nowrap;">' . $enableInstallToolFile . '</span>', floor($enableInstallToolFileTtl/60) );
					$message .= ' <a href="' . $disableInstallToolUrl . '">' .
						$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_enabled_cmd') .
						'</a>';
				}
			}
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			$GLOBALS['LANG']->getLL('status_installTool'), $value, $message, $severity
		);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_securitystatus.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_securitystatus.php']);
}

?>