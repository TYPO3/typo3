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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Performs several checks about the system's health
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
class SecurityStatus implements \TYPO3\CMS\Reports\StatusProviderInterface {

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @return array List of statuses
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
	 * @return \TYPO3\CMS\Reports\Status An object representing whether a default admin account exists
	 */
	protected function getAdminAccountStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		$whereClause = 'username = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('admin', 'be_users') . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_users');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid, username, password', 'be_users', $whereClause);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$secure = TRUE;
			/** @var $saltingObject \TYPO3\CMS\Saltedpasswords\Salt\SaltInterface */
			$saltingObject = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($row['password']);
			if (is_object($saltingObject)) {
				if ($saltingObject->checkPassword('password', $row['password'])) {
					$secure = FALSE;
				}
			}
			// Check against plain MD5
			if ($row['password'] === '5f4dcc3b5aa765d61d8327deb882cf99') {
				$secure = FALSE;
			}
			if (!$secure) {
				$value = $GLOBALS['LANG']->getLL('status_insecure');
				$severity = \TYPO3\CMS\Reports\Status::ERROR;
				$editUserAccountUrl = 'alt_doc.php?returnUrl=' . rawurlencode(BackendUtility::getModuleUrl('system_ReportsTxreportsm1')) . '&edit[be_users][' . $row['uid'] . ']=edit';
				$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.backend_admin'), '<a href="' . $editUserAccountUrl . '">', '</a>');
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_adminUserAccount'), $value, $message, $severity);
	}

	/**
	 * Checks whether the encryption key is empty.
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing whether the encryption key is empty or not
	 */
	protected function getEncryptionKeyStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if (empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
			$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');
			$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_encryption'), '<a href="' . $url . '">', '</a>');
		}
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_encryptionKey'), $value, $message, $severity);
	}

	/**
	 * Checks if fileDenyPattern was changed which is dangerous on Apache
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing whether the file deny pattern has changed
	 */
	protected function getFileDenyPatternStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		$defaultParts = GeneralUtility::trimExplode('|', FILE_DENY_PATTERN_DEFAULT, TRUE);
		$givenParts = GeneralUtility::trimExplode('|', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'], TRUE);
		$result = array_intersect($defaultParts, $givenParts);
		if ($defaultParts !== $result) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
			$url = 'install/index.php?redirect_url=index.php' . urlencode('?TYPO3_INSTALL[type]=config#set_encryptionKey');
			$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_pattern_partsNotPresent'), '<br /><pre>' . htmlspecialchars(FILE_DENY_PATTERN_DEFAULT) . '</pre><br />');
		}
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_fileDenyPattern'), $value, $message, $severity);
	}

	/**
	 * Checks if fileDenyPattern allows to upload .htaccess files which is
	 * dangerous on Apache.
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing whether it's possible to upload .htaccess files
	 */
	protected function getHtaccessUploadStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] != FILE_DENY_PATTERN_DEFAULT && GeneralUtility::verifyFilenameAgainstDenyPattern('.htaccess')) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
			$message = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_deny_htaccess');
		}
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_htaccessUploadProtection'), $value, $message, $severity);
	}

	/**
	 * Checks whether memcached is configured, if that's the case we assume it's also used.
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
		$command = GeneralUtility::_GET('adminCmd');
		switch ($command) {
			case 'remove_ENABLE_INSTALL_TOOL':
				EnableFileService::removeInstallToolEnableFile();
				break;
			default:
				// Do nothing
		}
	}

	/**
	 * Checks whether the Install Tool password is set to its default value.
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing the security of the install tool password
	 */
	protected function getInstallToolPasswordStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		$validPassword = TRUE;
		$installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'];
		$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($installToolPassword);
		if (is_object($saltFactory)) {
			$validPassword = !$saltFactory->checkPassword('joh316', $installToolPassword);
		} elseif ($installToolPassword === md5('joh316')) {
			$validPassword = FALSE;
		}
		if (!$validPassword) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
			$changeInstallToolPasswordUrl = BackendUtility::getModuleUrl('system_InstallInstall');
			$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.installtool_default_password'), '<a href="' . $changeInstallToolPasswordUrl . '">', '</a>');
		}
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_installToolPassword'), $value, $message, $severity);
	}

	/**
	 * Checks whether the Install Tool password is set to its default value.
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing the security of the saltedpassswords extension
	 */
	protected function getSaltedPasswordsStatus() {
		$value = $GLOBALS['LANG']->getLL('status_ok');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		/** @var \TYPO3\CMS\Saltedpasswords\Utility\ExtensionManagerConfigurationUtility $configCheck */
		$configCheck = GeneralUtility::makeInstance('TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility');
		$message = '<p>' . $GLOBALS['LANG']->getLL('status_saltedPasswords_infoText') . '</p>';
		$messageDetail = '';
		$flashMessage = $configCheck->checkConfigurationBackend(array(), new \TYPO3\CMS\Core\TypoScript\ConfigurationForm());
		if (strpos($flashMessage, 'message-error') !== FALSE) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
			$messageDetail .= $flashMessage;
		}
		if (strpos($flashMessage, 'message-warning') !== FALSE) {
			$severity = \TYPO3\CMS\Reports\Status::WARNING;
			$messageDetail .= $flashMessage;
		}
		if (strpos($flashMessage, 'message-information') !== FALSE) {
			$messageDetail .= $flashMessage;
		}
		$unsecureUserCount = \TYPO3\CMS\Saltedpasswords\Utility\SaltedPasswordsUtility::getNumberOfBackendUsersWithInsecurePassword();
		if ($unsecureUserCount > 0) {
			$value = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = \TYPO3\CMS\Reports\Status::ERROR;
			$messageDetail .= '<div class="typo3-message message-warning">' . $GLOBALS['LANG']->getLL('status_saltedPasswords_notAllPasswordsHashed') . '</div>';
		}
		$message .= $messageDetail;
		if (empty($messageDetail)) {
			$message = '';
		}
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_saltedPasswords'), $value, $message, $severity);
	}

	/**
	 * Checks for the existence of the ENABLE_INSTALL_TOOL file.
	 *
	 * @return \TYPO3\CMS\Reports\Status An object representing whether ENABLE_INSTALL_TOOL exists
	 */
	protected function getInstallToolProtectionStatus() {
		$enableInstallToolFile = PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL';
		$value = $GLOBALS['LANG']->getLL('status_disabled');
		$message = '';
		$severity = \TYPO3\CMS\Reports\Status::OK;
		if (EnableFileService::installToolEnableFileExists()) {
			if (EnableFileService::isInstallToolEnableFilePermanent()) {
				$severity = \TYPO3\CMS\Reports\Status::WARNING;
				$disableInstallToolUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&amp;adminCmd=remove_ENABLE_INSTALL_TOOL';
				$value = $GLOBALS['LANG']->getLL('status_enabledPermanently');
				$message = sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_enabled'), '<span style="white-space: nowrap;">' . $enableInstallToolFile . '</span>');
				$message .= ' <a href="' . $disableInstallToolUrl . '">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_enabled_cmd') . '</a>';
			} else {
				if (EnableFileService::installToolEnableFileLifetimeExpired()) {
					EnableFileService::removeInstallToolEnableFile();
				} else {
					$severity = \TYPO3\CMS\Reports\Status::NOTICE;
					$disableInstallToolUrl = GeneralUtility::getIndpEnv('TYPO3_REQUEST_URL') . '&amp;adminCmd=remove_ENABLE_INSTALL_TOOL';
					$value = $GLOBALS['LANG']->getLL('status_enabledTemporarily');
					$message = sprintf($GLOBALS['LANG']->getLL('status_installEnabledTemporarily'), '<span style="white-space: nowrap;">' . $enableInstallToolFile . '</span>', floor($enableInstallToolFileTtl / 60));
					$message .= ' <a href="' . $disableInstallToolUrl . '">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.install_enabled_cmd') . '</a>';
				}
			}
		}
		return GeneralUtility::makeInstance('TYPO3\\CMS\\Reports\\Status', $GLOBALS['LANG']->getLL('status_installTool'), $value, $message, $severity);
	}

}
