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

use TYPO3\CMS\Install\Service\EnableFileService;

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
		'configuration',
		'folderStructure',
		'testSetup',
		'upgradeWizard',
		'allConfiguration',
		'cleanUp',
		'loadExtensions',
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
		$this->registerExtensionConfigurationErrorHandler();
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
			if (!EnableFileService::isInstallToolEnableFilePermanent()) {
				EnableFileService::removeInstallToolEnableFile();
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

	/**
	 * This function registers a shutdown function, which is called even if a fatal error occurs.
	 * The request either gets redirected to an action where all extension configurations are checked for compatibility or
	 * an information with a link to that action.
	 *
	 * @return void
	 */
	protected function registerExtensionConfigurationErrorHandler() {
		register_shutdown_function(function() {
			$error = error_get_last();
			if ($error !== NULL) {
				$errorType = $error["type"];

				if ($errorType & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR)) {
					$getPostValues = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('install');

					$parameters = array();

					// Add context parameter in case this script was called within backend scope
					$context = 'install[context]=standalone';
					if (isset($getPostValues['context']) && $getPostValues['context'] === 'backend') {
						$context = 'install[context]=backend';
					}
					$parameters[] = $context;

					// Add controller parameter
					$parameters[] = 'install[controller]=tool';

					// Add action if specified
					$parameters[] = 'install[action]=loadExtensions';

					// Add error to display a message what triggered the check
					$errorEncoded = json_encode($error);
					$parameters[] = 'install[lastError]=' . rawurlencode($errorEncoded);
					// We do not use GeneralUtility here to be sure that hash generation works even if that class might not exist any more.
					$parameters[] = 'install[lastErrorHash]=' . hash_hmac('sha1', $errorEncoded, $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . 'InstallToolError');

					$redirectLocation = 'Install.php?' . implode('&', $parameters);

					if (!headers_sent()) {
						\TYPO3\CMS\Core\Utility\HttpUtility::redirect(
							$redirectLocation,
							\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303
						);
					} else {
						echo '
<p><strong>
	The system detected a fatal error during script execution.
	Please use the <a href="' . $redirectLocation . '">extension check tool</a> to find incompatible extensions.
</strong></p>';
					}
				}
			}
		});
	}

	/**
	 * Get last error values of install tool.
	 *
	 * @return array
	 */
	protected function getLastError() {
		$getVars = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('install');
		$lastError = array();
		if (isset($getVars['lastError']) && isset($getVars['lastErrorHash']) && !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'])) {
			$calculatedHash = hash_hmac('sha1', $getVars['lastError'], $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] . 'InstallToolError');
			if ($calculatedHash === $getVars['lastErrorHash']) {
				$lastError = json_decode($getVars['lastError'], TRUE);
			}
		}
		return $lastError;
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
			$action = 'welcome';
		}
		$this->validateAuthenticationAction($action);
		$actionClass = ucfirst($action);
		/** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $toolAction */
		$toolAction = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Tool\\' . $actionClass);
		if (!($toolAction instanceof Action\ActionInterface)) {
			throw new Exception(
				$action . ' does not implement ActionInterface',
				1369474309
			);
		}
		$toolAction->setController('tool');
		$toolAction->setAction($action);
		$toolAction->setToken($this->generateTokenForAction($action));
		$toolAction->setPostValues($this->getPostValues());
		$toolAction->setLastError($this->getLastError());
		$this->output($toolAction->handle());
	}
}
