<?php
namespace TYPO3\CMS\Install\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Susanne Moog <typo3@susannemoog.de>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Install tool ajax controller, handles ajax requests
 *
 */
class AjaxController extends AbstractController {

	/**
	 * @var string
	 */
	protected $unauthorized = 'unauthorized';

	/**
	 * @var array List of valid action names that need authentication
	 */
	protected $authenticationActions = array(
		'extensionCompatibilityTester',
		'uninstallExtension',
		'clearCache',
		'coreUpdateUpdateVersionMatrix',
		'coreUpdateIsUpdateAvailable',
		'coreUpdateCheckPreConditions',
		'coreUpdateDownload',
		'coreUpdateVerifyChecksum',
		'coreUpdateUnpack',
		'coreUpdateMove',
		'coreUpdateActivate',
		'folderStatus',
		'environmentStatus'
	);

	/**
	 * Main entry point
	 *
	 * @return void
	 */
	public function execute() {
		$this->loadBaseExtensions();
		$this->initializeObjectManager();
		// Warning: Order of these methods is security relevant and interferes with different access
		// conditions (new/existing installation). See the single method comments for details.
		$this->outputInstallToolNotEnabledMessageIfNeeded();
		$this->checkInstallToolPasswordNotSet();
		$this->initializeSession();
		$this->checkSessionToken();
		$this->checkSessionLifetime();
		$this->checkLogin();
		$this->dispatchAuthenticationActions();
	}

	/**
	 * Check whether the install tool is enabled
	 *
	 * @return void
	 */
	protected function outputInstallToolNotEnabledMessageIfNeeded() {
		if (!$this->isInstallToolAvailable()) {
			$this->output($this->unauthorized);
		}
	}

	/**
	 * Check if the install tool password is set
	 *
	 * @return void
	 */
	protected function checkInstallToolPasswordNotSet() {
		if (empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'])) {
			$this->output($this->unauthorized);
		}
	}

	/**
	 * Check login status
	 *
	 * @return void
	 */
	protected function checkLogin() {
		if (!$this->session->isAuthorized()) {
			$this->output($this->unauthorized);
		} else {
			$this->session->refreshSession();
		}
	}

	/**
	 * Overwrites abstract method
	 * In contrast to abstract method, a response "you are not authorized is outputted"
	 *
	 * @param boolean $tokenOk
	 * @return void
	 */
	protected function handleSessionTokenCheck($tokenOk) {
		if (!$tokenOk) {
			$this->output($this->unauthorized);
		}
	}

	/**
	 * Overwrites abstract method
	 * In contrast to abstract method, a response "you are not authorized is outputted"
	 *
	 * @return void
	 */
	protected function handleSessionLifeTimeExpired() {
		$this->output($this->unauthorized);
	}

	/**
	 * Call an action that needs authentication
	 *
	 * @throws Exception
	 * @return string Rendered content
	 */
	protected function dispatchAuthenticationActions() {
		$action = $this->getAction();
		if ($action === '') {
			$this->output('noAction');
		}
		$this->validateAuthenticationAction($action);
		$actionClass = ucfirst($action);
		/** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
		$toolAction = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Ajax\\' . $actionClass);
		if (!($toolAction instanceof Action\ActionInterface)) {
			throw new Exception(
				$action . ' does not implement ActionInterface',
				1369474308
			);
		}
		$toolAction->setController('ajax');
		$toolAction->setAction($action);
		$toolAction->setToken($this->generateTokenForAction($action));
		$toolAction->setPostValues($this->getPostValues());
		$this->output($toolAction->handle());
	}

	/**
	 * Output content.
	 * WARNING: This exits the script execution!
	 *
	 * @param string $content JSON encoded content to output
	 */
	protected function output($content = '') {
		ob_clean();
		header('Content-Type: application/json; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		echo $content;
		die;
	}
}
