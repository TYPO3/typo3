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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\EnableFileService;

/**
 * Controller abstract for shared parts of Tool, Step and Ajax controller
 */
class AbstractController {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager = NULL;

	/**
	 * @var \TYPO3\CMS\Install\Service\SessionService
	 */
	protected $session = NULL;

	/**
	 * @var array List of valid action names that need authentication
	 */
	protected $authenticationActions = array();

	/**
	 * @return bool
	 */
	protected function isInstallToolAvailable() {
		/** @var \TYPO3\CMS\Install\Service\EnableFileService $installToolEnableService */
		$installToolEnableService = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\EnableFileService');
		if ($installToolEnableService->isFirstInstallAllowed()) {
			return TRUE;
		}
		return $installToolEnableService->checkInstallToolEnableFile();
	}

	/**
	 * Guard method checking typo3conf/ENABLE_INSTALL_TOOL
	 *
	 * Checking ENABLE_INSTALL_TOOL validity is simple:
	 * As soon as there is a typo3conf directory at all (not step 1 of "first install"),
	 * the file must be there and valid in order to proceed.
	 *
	 * @return void
	 */
	protected function outputInstallToolNotEnabledMessageIfNeeded() {
		if (!$this->isInstallToolAvailable()) {
			if (!EnableFileService::isFirstInstallAllowed() && !is_dir(PATH_typo3conf)) {
				/** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
				$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Common\\AccessNotAllowedAction');
				$action->setAction('accessNotAllowed');
			} else {
				/** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
				$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Common\\InstallToolDisabledAction');
				$action->setAction('installToolDisabled');
			}
			$action->setController('common');
			$this->output($action->handle());
		}
	}

	/**
	 * Guard method checking for valid install tool password
	 *
	 * If installation is completed - LocalConfiguration exists and
	 * installProcess is not running, and installToolPassword must be set
	 */
	protected function outputInstallToolPasswordNotSetMessageIfNeeded() {
		if (!$this->isInitialInstallationInProgress()
			&& (empty($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword']))
		) {
			/** @var \TYPO3\CMS\Install\Controller\Action\ActionInterface $action */
			$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Common\\InstallToolPasswordNotSetAction');
			$action->setController('common');
			$action->setAction('installToolPasswordNotSet');
			$this->output($action->handle());
		}
	}

	/**
	 * Use form protection API to find out if protected POST forms are ok.
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function checkSessionToken() {
		$postValues = $this->getPostValues();
		$tokenOk = FALSE;
		if (count($postValues) > 0) {
			// A token must be given as soon as there is POST data
			if (isset($postValues['token'])) {
				/** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
				$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
					'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'
				);
				$action = $this->getAction();
				if ($action === '') {
					throw new Exception(
						'No POST action given for token check',
						1369326593
					);
				}
				$tokenOk = $formProtection->validateToken($postValues['token'], 'installTool', $action);
			}
		} else {
			$tokenOk = TRUE;
		}

		$this->handleSessionTokenCheck($tokenOk);
	}

	/**
	 * If session token was not ok, the session is reset and either
	 * a redirect is initialized (will load the same step step controller again) or
	 * if in install tool, the login form is displayed.
	 *
	 * @param boolean $tokenOk
	 * @return void
	 */
	protected function handleSessionTokenCheck($tokenOk) {
		if (!$tokenOk) {
			$this->session->resetSession();
			$this->session->startSession();

			if ($this->isInitialInstallationInProgress()) {
				$this->redirect();
			} else {
				/** @var $message \TYPO3\CMS\Install\Status\ErrorStatus */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Invalid form token');
				$message->setMessage(
					'The form protection token was invalid. You have been logged out, please log in and try again.'
				);
				$this->output($this->loginForm($message));
			}
		}
	}

	/**
	 * Check if session expired.
	 *
	 * @return void
	 */
	protected function checkSessionLifetime() {
		if ($this->session->isExpired()) {
			// Session expired, log out user, start new session
			$this->session->resetSession();
			$this->session->startSession();

			$this->handleSessionLifeTimeExpired();
		}
	}

	/**
	 * If session expired, the current step of step controller is reloaded
	 * (if first installation is running) - or the login form is displayed.
	 *
	 * @return void
	 */
	protected function handleSessionLifeTimeExpired() {
		if ($this->isInitialInstallationInProgress()) {
			$this->redirect();
		} else {
			/** @var $message \TYPO3\CMS\Install\Status\ErrorStatus */
			$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
			$message->setTitle('Session expired');
			$message->setMessage(
				'Your Install Tool session has expired. You have been logged out, please log in and try again.'
			);
			$this->output($this->loginForm($message));
		}
	}

	/**
	 * Show login form
	 *
	 * @param \TYPO3\CMS\Install\Status\StatusInterface $message Optional status message from controller
	 * @return string Rendered HTML
	 */
	protected function loginForm(\TYPO3\CMS\Install\Status\StatusInterface $message = NULL) {
		/** @var \TYPO3\CMS\Install\Controller\Action\Common\LoginForm $action */
		$action = $this->objectManager->get('TYPO3\\CMS\\Install\\Controller\\Action\\Common\\LoginForm');
		$action->setController('common');
		$action->setAction('login');
		$action->setToken($this->generateTokenForAction('login'));
		$action->setPostValues($this->getPostValues());
		if ($message) {
			$action->setMessages(array($message));
		}
		$content = $action->handle();
		return $content;
	}

	/**
	 * Validate install tool password and login user if requested
	 *
	 * @return void
	 */
	protected function loginIfRequested() {
		$action = $this->getAction();
		$postValues = $this->getPostValues();
		if ($action === 'login') {
			$password = '';
			$validPassword = FALSE;
			if (isset($postValues['values']['password'])) {
				$password = $postValues['values']['password'];
				$installToolPassword = $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'];
				$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance($installToolPassword);
				if (is_object($saltFactory)) {
					$validPassword = $saltFactory->checkPassword($password, $installToolPassword);
				} elseif (md5($password) === $installToolPassword) {
					// Update install tool password
					$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL, 'BE');
					$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
					$configurationManager->setLocalConfigurationValueByPath(
						'BE/installToolPassword',
						$saltFactory->getHashedPassword($password)
					);
					$validPassword = TRUE;
				}
			}
			if ($validPassword) {
				$this->session->setAuthorized();
				$this->sendLoginSuccessfulMail();
				$this->redirect();
			} else {
				$saltFactory = \TYPO3\CMS\Saltedpasswords\Salt\SaltFactory::getSaltingInstance(NULL, 'BE');
				$hashedPassword = $saltFactory->getHashedPassword($password);
				/** @var $message \TYPO3\CMS\Install\Status\ErrorStatus */
				$message = $this->objectManager->get('TYPO3\\CMS\\Install\\Status\\ErrorStatus');
				$message->setTitle('Login failed');
				$message->setMessage('Given password does not match the install tool login password. ' .
					'Calculated hash: ' . $hashedPassword);
				$this->sendLoginFailedMail();
				$this->output($this->loginForm($message));
			}
		}
	}

	/**
	 * Show login for if user is not authorized yet and if
	 * not in first installation process.
	 *
	 * @return void
	 */
	protected function outputLoginFormIfNotAuthorized() {
		if (!$this->session->isAuthorized()
			&& !$this->isInitialInstallationInProgress()
		) {
			$this->output($this->loginForm());
		} else {
			$this->session->refreshSession();
		}
	}

	/**
	 * If install tool login mail is set, send a mail for a successful login.
	 *
	 * @return void
	 */
	protected function sendLoginSuccessfulMail() {
		$warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
		if ($warningEmailAddress) {
			/** @var \TYPO3\CMS\Core\Mail\MailMessage $mailMessage */
			$mailMessage = $this->objectManager->get('TYPO3\\CMS\\Core\\Mail\\MailMessage');
			$mailMessage
				->addTo($warningEmailAddress)
				->setSubject('Install Tool Login at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
				->addFrom($this->getSenderEmailAddress(), 'TYPO3 Install Tool WARNING')
				->setBody('There has been an Install Tool login at TYPO3 site'
				. ' \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\''
				. ' (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ')'
				. ' from remote address \'' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\''
				. ' (' . GeneralUtility::getIndpEnv('REMOTE_HOST') . ')')
				->send();
		}
	}

	/**
	 * If install tool login mail is set, send a mail for a failed login.
	 *
	 * @return void
	 */
	protected function sendLoginFailedMail() {
		$formValues = GeneralUtility::_GP('install');
		$warningEmailAddress = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
		if ($warningEmailAddress) {
			/** @var \TYPO3\CMS\Core\Mail\MailMessage $mailMessage */
			$mailMessage = $this->objectManager->get('TYPO3\\CMS\\Core\\Mail\\MailMessage');
			$mailMessage
				->addTo($warningEmailAddress)
				->setSubject('Install Tool Login ATTEMPT at \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\'')
				->addFrom($this->getSenderEmailAddress(), 'TYPO3 Install Tool WARNING')
				->setBody('There has been an Install Tool login attempt at TYPO3 site'
				. ' \'' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] . '\''
				. ' (' . GeneralUtility::getIndpEnv('HTTP_HOST') . ')'
				. ' The last 5 characters of the MD5 hash of the password tried was \'' . substr(md5($formValues['password']), -5) . '\''
				. ' remote address was \'' . GeneralUtility::getIndpEnv('REMOTE_ADDR') . '\''
				. ' (' . GeneralUtility::getIndpEnv('REMOTE_HOST') . ')')
				->send();
		}
	}

	/**
	 * Generate token for specific action
	 *
	 * @param string $action Action name
	 * @return string Form protection token
	 * @throws Exception
	 */
	protected function generateTokenForAction($action = NULL) {
		if (!$action) {
			$action = $this->getAction();
		}
		if ($action === '') {
			throw new Exception(
				'Token must have a valid action name',
				1369326592
			);
		}
		/** @var $formProtection \TYPO3\CMS\Core\FormProtection\InstallToolFormProtection */
		$formProtection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get(
			'TYPO3\\CMS\\Core\\FormProtection\\InstallToolFormProtection'
		);
		return $formProtection->generateToken('installTool', $action);
	}

	/**
	 * First installation is in progress, if LocalConfiguration does not exist,
	 * or if isInitialInstallationInProgress is not set or FALSE.
	 *
	 * @return boolean TRUE if installation is in progress
	 */
	protected function isInitialInstallationInProgress() {
		/** @var \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');

		$localConfigurationFileLocation = $configurationManager->getLocalConfigurationFileLocation();
		$localConfigurationFileExists = @is_file($localConfigurationFileLocation);
		$result = FALSE;
		if (!$localConfigurationFileExists
			|| !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['isInitialInstallationInProgress'])
		) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Initialize session object.
	 * Subclass will throw exception if session can not be created or if
	 * preconditions like a valid encryption key are not set.
	 *
	 * @return void
	 */
	protected function initializeSession() {
		/** @var \TYPO3\CMS\Install\Service\SessionService $session */
		$this->session = $this->objectManager->get('TYPO3\\CMS\\Install\\Service\\SessionService');
		if (!$this->session->hasSession()) {
			$this->session->startSession();
		}
	}

	/**
	 * Add status messages to session.
	 * Used to output messages between requests, especially in step controller
	 *
	 * @param array<\TYPO3\CMS\Install\Status\StatusInterface> $messages
	 */
	protected function addSessionMessages(array $messages) {
		foreach ($messages as $message) {
			$this->session->addMessage($message);
		}
	}

	/**
	 * Initialize extbase object manager for fluid rendering
	 *
	 * @return void
	 */
	protected function initializeObjectManager() {
		/** @var \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager */
		$objectManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\ObjectManager');
		$this->objectManager = $objectManager;
	}

	/**
	 * Require dbal ext_localconf if extension is loaded
	 * Required extbase + fluid ext_localconf
	 * Set caching to null, we do not want dbal, fluid or extbase to cache anything
	 *
	 * @return void
	 */
	protected function loadBaseExtensions() {
		if ($this->isDbalEnabled()) {
			require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('dbal') . 'ext_localconf.php');
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal']['backend']
				= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
			$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['dbal']['options'] = array();
		}

		require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('extbase') . 'ext_localconf.php');
		require(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('fluid') . 'ext_localconf.php');

		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_datamapfactory_datamap']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_datamapfactory_datamap']['options'] = array();
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_object']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_object']['options'] = array();
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_reflection']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_reflection']['options'] = array();
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_typo3dbbackend_tablecolumns']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['extbase_typo3dbbackend_tablecolumns']['options'] = array();
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['backend']
			= 'TYPO3\\CMS\\Core\\Cache\\Backend\\NullBackend';
		$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['fluid_template']['options'] = array();

		/** @var $cacheManager \TYPO3\CMS\Core\Cache\CacheManager */
		$cacheManager = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Cache\\CacheManager');
		$cacheManager->setCacheConfigurations($GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']);
	}

	/**
	 * Return TRUE if dbal and adodb extension is loaded.
	 *
	 * @return boolean TRUE if dbal and adodb is loaded
	 */
	protected function isDbalEnabled() {
		if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('adodb')
			&& \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('dbal')
		) {
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Check given action name is one of the allowed actions.
	 *
	 * @param string $action Given action to validate
	 * @throws Exception
	 */
	protected function validateAuthenticationAction($action) {
		if (!in_array($action, $this->authenticationActions)) {
			throw new Exception(
				$action . ' is not a valid authentication action',
				1369345838
			);
		}
	}

	/**
	 * Retrieve parameter from GET or POST and sanitize
	 *
	 * @throws Exception
	 * @return string Empty string if no action is given or sanitized action string
	 */
	protected function getAction() {
		$formValues = GeneralUtility::_GP('install');
		$action = '';
		if (isset($formValues['action'])) {
			$action = $formValues['action'];
		}
		if ($action !== ''
			&& $action !== 'login'
			&& $action !== 'loginForm'
			&& $action !== 'logout'
			&& !in_array($action, $this->authenticationActions)
		) {
			throw new Exception(
				'Invalid action ' . $action,
				1369325619
			);
		}
		return $action;
	}

	/**
	 * Get POST form values of install tool.
	 * All POST data is secured by form token protection, except in very installation step.
	 *
	 * @return array
	 */
	protected function getPostValues() {
		$postValues = GeneralUtility::_POST('install');
		if (!is_array($postValues)) {
			$postValues = array();
		}
		return $postValues;
	}

	/**
	 * HTTP redirect to self, preserving allowed GET variables.
	 * WARNING: This exits the script execution!
	 *
	 * @param string $controller Can be set to 'tool' to redirect from step to tool controller
	 * @param string $action Set specific action for next request, used in step controller to specify next step
	 * @return void
	 */
	protected function redirect($controller = '', $action = '') {
		$getPostValues = GeneralUtility::_GP('install');

		$parameters = array();

		// Current redirect count
		if (isset($getPostValues['redirectCount'])) {
			$redirectCount = (int)$getPostValues['redirectCount'] + 1;
		} else {
			$redirectCount = 0;
		}
		if ($redirectCount >= 10) {
			// Abort a redirect loop by throwing an exception. Calling this method
			// some times in a row is ok, but break a loop if this happens too often.
			throw new Exception\RedirectLoopException(
				'Redirect loop aborted. If this message is shown again after a reload,' .
					' your setup is so weird that the install tool is unable to handle it.' .
					' Please make sure to remove the "install[redirectCount]" parameter from your request or' .
					' restart the install tool from the backend navigation.',
				1380581244
			);
		}
		$parameters[] = 'install[redirectCount]=' . $redirectCount;

		// Add context parameter in case this script was called within backend scope
		$context = 'install[context]=standalone';
		if (isset($getPostValues['context']) && $getPostValues['context'] === 'backend') {
			$context = 'install[context]=backend';
		}
		$parameters[] = $context;

		// Add controller parameter
		$controllerParameter = 'install[controller]=step';
		if ((isset($getPostValues['controller']) && $getPostValues['controller'] === 'tool')
			|| $controller === 'tool'
		) {
			$controllerParameter = 'install[controller]=tool';
		}
		$parameters[] = $controllerParameter;

		// Add action if specified
		if (strlen($action) > 0) {
			$parameters[] = 'install[action]=' . $action;
		}

		$redirectLocation = 'Install.php?' . implode('&', $parameters);

		\TYPO3\CMS\Core\Utility\HttpUtility::redirect(
			$redirectLocation,
			\TYPO3\CMS\Core\Utility\HttpUtility::HTTP_STATUS_303
		);
	}

	/**
	 * Output content.
	 * WARNING: This exits the script execution!
	 *
	 * @param string $content Content to output
	 */
	protected function output($content = '') {
		header('Content-Type: text/html; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		echo $content;
		die;
	}

	/**
	 * Get sender address from configuration
	 * ['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
	 * If this setting is empty fall back to 'no-reply@example.com'
	 *
	 * @return string Returns an email address
	 */
	protected function getSenderEmailAddress() {
		return !empty($GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress'])
			? $GLOBALS['TYPO3_CONF_VARS']['MAIL']['defaultMailFromAddress']
			: 'no-reply@example.com';
	}
}
