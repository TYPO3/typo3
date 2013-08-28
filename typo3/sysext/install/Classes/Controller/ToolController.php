<?php
namespace TYPO3\CMS\Install\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Install tool controller, dispatcher class of the install tool.
 *
 * Handles install tool session, login and login form rendering,
 * calls actions that need authentication and handles form tokens.
 */
class ToolController extends AbstractController {

	/**
	 * @var array List of valid action names that need authentication
	 */
	protected $authenticationActions = array(
		'welcome',
		'importantActions',
		'systemEnvironment',
		'folderStructure',
		'testSetup',
		'updateWizard',
		'allConfiguration',
		'cleanUp',
	);

	/**
	 * Main dispatch method
	 *
	 * @return void
	 */
	public function execute() {
		$this->loadBaseExtensions();
		$this->initializeObjectManager();

		// Warning: Order of these methods is security relevant and interferes with different access
		// conditions (new/existing installation). See the single method comments for details.
		$this->outputInstallToolNotEnabledMessageIfNeeded();
		$this->outputInstallToolPasswordNotSetMessageIfNeeded();
		$this->initializeSession();
		$this->checkSessionToken();
		$this->checkSessionLifetime();
		$this->logoutIfRequested();
		$this->loginIfRequested();
		$this->outputLoginFormIfNotAuthorized();
		$this->moreMagic();
		$this->dispatchAuthenticationActions();
	}

	/**
	 * Logout user if requested
	 *
	 * @return void
	 */
	protected function logoutIfRequested() {
		$action = $this->getAction();
		if ($action === 'logout') {
			// @TODO: This and similar code in step action DefaultConfiguration should be moved to enable install file service
			$enableInstallToolFile = PATH_typo3conf . 'ENABLE_INSTALL_TOOL';
			if (is_file($enableInstallToolFile) && trim(file_get_contents($enableInstallToolFile)) !== 'KEEP_FILE') {
				unlink($enableInstallToolFile);
			}

			/** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
			$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
				'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'
			);
			$formProtection->clean();
			$this->session->destroySession();
			$this->redirect();
		}
	}

	protected function moreMagic() {
		error_reporting(0);
		register_shutdown_function(function() {
				$error = error_get_last();
				if ($error !== NULL) {
					$errorType = $error["type"];
					$errorFile = $error["file"];
					$errorLine = $error["line"];
					$errorMessage = $error["message"];

					if ($errorType === E_ERROR || $errorType === E_PARSE || $errorType === E_COMPILE_ERROR) {
						echo "found";
					} else {
						var_dump($errorType);
					}




//					if (!headers_sent()) {
//						header("HTTP/1.1 301 Moved Permanently");
//						header("Location: http://google.com");
//					} else {
						printf('No.: "%s" File: "%s" Line: "%s" Message: "%s"<br /><br />', $errorType, $errorFile, $errorLine, $errorMessage);
						echo 'go to <a href="http://google.com">google</a>';
//					}
				} else {
					// No error occured
				}


			});

	}

	/**
	 * Call an action that needs authentication
	 *
	 * @throws Exception
	 * @return string Rendered content
	 */
	protected function dispatchAuthenticationActions() {
//		trigger_error('Buh', E_USER_ERROR);
#require __DIR__ . '/test.php';

		$action = $this->getAction();
		if ($action === '') {
			$action = 'welcome';
		}
		$this->validateAuthenticationAction($action);
		$actionClass = ucfirst($action);
		/** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
		$toolAction = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\' . $actionClass);
		if (!($toolAction instanceof \TYPO3\CMS\Install\Controller\Action\ActionInterface)) {
			throw new Exception(
				$action . ' does not implement ActionInterface',
				1369474308
			);
		}
		$toolAction->setController('tool');
		$toolAction->setAction($action);
		$toolAction->setToken($this->generateTokenForAction($action));
		$toolAction->setPostValues($this->getPostValues());
		$this->output($toolAction->handle());
	}
}

?>