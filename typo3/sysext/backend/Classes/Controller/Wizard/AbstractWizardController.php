<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Class AbstractWizardController
 */
class AbstractWizardController {

	/**
	 * Checks access for element
	 *
	 * @param string $table Table name
	 * @param int $uid Record uid
	 * @return bool
	 */
	protected function checkEditAccess($table, $uid) {
		$calcPermissionRecord = BackendUtility::getRecord($table, $uid);
		BackendUtility::fixVersioningPid($table, $calcPermissionRecord);
		if (is_array($calcPermissionRecord)) {
			// If pages:
			if ($table === 'pages') {
				$calculatedPermissions = $this->getBackendUserAuthentication()->calcPerms($calcPermissionRecord);
				$hasAccess = $calculatedPermissions & 2;
			} else {
				// Fetching pid-record first.
				$calculatedPermissions = $this->getBackendUserAuthentication()->calcPerms(
					BackendUtility::getRecord('pages', $calcPermissionRecord['pid']));
				$hasAccess = $calculatedPermissions & 16;
			}
			// Check internals regarding access:
			if ($hasAccess) {
				$hasAccess = $this->getBackendUserAuthentication()->recordEditAccessInternals($table, $calcPermissionRecord);
			}
		} else {
			$hasAccess = FALSE;
		}
		return (bool)$hasAccess;
	}

	/**
	 * Returns an instance of BackendUserAuthentication
	 *
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * Returns an instance of LanguageService
	 *
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the backpath
	 *
	 * @return string
	 */
	protected function getBackPath() {
		return $GLOBALS['BACK_PATH'];
	}

	/**
	 * Returns an instance of DocumentTemplate
	 *
	 * @return DocumentTemplate
	 */
	protected function getDocumentTemplate() {
		return $GLOBALS['TBE_TEMPLATE'];
	}

	/**
	 * Returns an instance of DatabaseConnection
	 *
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}
}
