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
 * Performs some checks about the install tool protection status
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage reports
 */
class tx_reports_reports_status_InstallToolStatus implements tx_reports_StatusProvider {

	/**
	 * Determines the Install Tool's status, mainly concerning its protection.
	 *
	 * @see typo3/sysext/reports/interfaces/tx_reports_StatusProvider::getStatus()
	 */
	public function getStatus() {
		$this->executeAdminCommand();

		return array(
			'installToolEnabled'  => $this->getInstallToolProtectionStatus(),
			'installToolPassword' => $this->getInstallToolPasswordStatus(),
			'adminUserAccount'    => $this->getAdminAccountStatus()
		);
	}

	/**
	 * Executes commands like removing the Install Tool enable file.
	 *
	 * @return	void
	 */
	protected function executeAdminCommand() {
		$command = t3lib_div::_GET('adminCmd');

		switch($command) {
			case 'remove_ENABLE_INSTALL_TOOL':
				unlink(PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL');
			break;
		}
	}

	/**
	 * Checks whether the Install Tool password is set to its default value.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing the security of the install tool password
	 */
	protected function getInstallToolPasswordStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'] == md5('joh316')) {
			$value    = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;

			$changeInstallToolPasswordUrl = 'install/index.php?redirect_url=index.php'
				. urlencode('?TYPO3_INSTALL[type]=about');

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_password'),
				'<a href="' . $changeInstallToolPasswordUrl . '">',
				'</a>'
			);
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Install Tool Password', $value, $message, $severity
		);
	}

	/**
	 * Checks whether a an BE user account named admin with default password exists.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether a default admin account exists
	 */
	protected function getAdminAccountStatus() {
		$value    = $GLOBALS['LANG']->getLL('status_ok');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		$whereClause = 'username = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('admin', 'be_users')
			. ' AND password = ' . $GLOBALS['TYPO3_DB']->fullQuoteStr('5f4dcc3b5aa765d61d8327deb882cf99', 'be_users')
			. t3lib_BEfunc::deleteClause('be_users');
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'uid, username, password',
			'be_users',
			$whereClause
		);
		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$value    = $GLOBALS['LANG']->getLL('status_insecure');
			$severity = tx_reports_reports_status_Status::ERROR;

			$editUserAccountUrl = 'alt_doc.php?returnUrl=index.php&edit[be_users][' . $row['uid'] . ']=edit';
			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.backend_admin'),
				'<a href="' . $editUserAccountUrl . '">',
				'</a>'
			);
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Admin User Account', $value, $message, $severity
		);
	}

	/**
	 * Checks for the existance of the ENABLE_INSTALL_TOOL file.
	 *
	 * @return	tx_reports_reports_status_Status	An tx_reports_reports_status_Status object representing whether ENABLE_INSTALL_TOOL exists
	 */
	protected function getInstallToolProtectionStatus() {
		$enableInstallToolFile = PATH_site . 'typo3conf/ENABLE_INSTALL_TOOL';
		$value    = $GLOBALS['LANG']->getLL('status_disabled');
		$message  = '';
		$severity = tx_reports_reports_status_Status::OK;

		$enableInstallToolFileExists = is_file($enableInstallToolFile);

		if ($enableInstallToolFileExists || ($enableInstallToolFileExists && trim(file_get_contents($enableInstallToolFile)) === 'KEEP_FILE')) {
			$value    = $GLOBALS['LANG']->getLL('status_enabled');
			$severity = tx_reports_reports_status_Status::WARNING;

			$disableInstallToolUrl = t3lib_div::getIndpEnv('TYPO3_REQUEST_URL')
				. '&adminCmd=remove_ENABLE_INSTALL_TOOL';

			$message = sprintf(
				$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_enabled'),
				'<span style="white-space: nowrap;">' . $enableInstallToolFile . '</span>');
			$message .= ' <a href="' . $disableInstallToolUrl . '">'
				. $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:warning.install_enabled_cmd')
				. '</a>';
		}

		return t3lib_div::makeInstance('tx_reports_reports_status_Status',
			'Install Tool', $value, $message, $severity
		);
	}
}


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_installtoolstatus.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/reports/reports/status/class.tx_reports_reports_status_installtoolstatus.php']);
}

?>