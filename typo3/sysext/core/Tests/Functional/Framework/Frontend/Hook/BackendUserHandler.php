<?php
namespace TYPO3\CMS\Core\Tests\Functional\Framework\Frontend\Hook;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use \TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Handler for backend user
 */
class BackendUserHandler implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @param array $parameters
	 * @param \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController
	 */
	public function initialize(array $parameters, \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController $frontendController) {
		$backendUserId = (int)GeneralUtility::_GP('backendUserId');
		$workspaceId = (int)GeneralUtility::_GP('workspaceId');

		if (empty($backendUserId) || empty($workspaceId)) {
			return;
		}

		$backendUser = $this->createBackendUser();
		$backendUser->user = $this->getDatabaseConnection()->exec_SELECTgetSingleRow('*', 'be_users', 'uid=' . $backendUserId);
		$backendUser->setTemporaryWorkspace($workspaceId);
		$frontendController->beUserLogin = TRUE;

		$parameters['BE_USER'] = $backendUser;
		$GLOBALS['BE_USER'] = $backendUser;
	}

	/**
	 * @return \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
	 */
	protected function createBackendUser() {
		return GeneralUtility::makeInstance(
			'TYPO3\\CMS\\Backend\\FrontendBackendUserAuthentication'
		);
	}

	/**
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
