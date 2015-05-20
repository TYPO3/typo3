<?php
namespace TYPO3\CMS\Backend\Http;

/*
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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Handles the request for backend modules and wizards
 */
class BackendModuleRequestHandler implements \TYPO3\CMS\Core\Core\RequestHandlerInterface {

	/**
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var array
	 */
	protected $moduleRegistry = array();

	/**
	 * @var BackendUserAuthentication
	 */
	protected $backendUserAuthentication;

	/**
	 * Instance of the current Http Request
	 * @var ServerRequestInterface
	 */
	protected $request;

	/**
	 * Constructor handing over the bootstrap and the original request
	 *
	 * @param Bootstrap $bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles the request, evaluating the configuration and executes the module accordingly
	 *
	 * @param ServerRequestInterface $request
	 * @return NULL|\Psr\Http\Message\ResponseInterface
	 * @throws Exception
	 */
	public function handleRequest(ServerRequestInterface $request) {
		$this->request = $request;
		$this->boot();

		$this->moduleRegistry = $GLOBALS['TBE_MODULES'];

		if (!$this->isValidModuleRequest()) {
			throw new Exception('The CSRF protection token for the requested module is missing or invalid', 1417988921);
		}

		// Set to empty as it is not needed / always coming from typo3/mod.php
		$GLOBALS['BACK_PATH'] = '';

		$this->backendUserAuthentication = $GLOBALS['BE_USER'];

		$moduleName = (string)$this->request->getQueryParams()['M'];
		if ($this->isDispatchedModule($moduleName)) {
			$isDispatched = $this->dispatchModule($moduleName);
		} else {
			$isDispatched = $this->callTraditionalModule($moduleName);
		}
		if ($isDispatched === FALSE) {
			throw new Exception('No module "' . $moduleName . '" could be found.', 1294585070);
		}
	}

	/**
	 * Execute TYPO3 bootstrap
	 */
	protected function boot() {
		// Evaluate the constant for skipping the BE user check for the bootstrap, will be done without the constant at a later point
		if (defined('TYPO3_PROCEED_IF_NO_USER') && TYPO3_PROCEED_IF_NO_USER) {
			$proceedIfNoUserIsLoggedIn = TRUE;
		} else {
			$proceedIfNoUserIsLoggedIn = FALSE;
		}

		$this->bootstrap->checkLockedBackendAndRedirectOrDie()
			->checkBackendIpOrDie()
			->checkSslBackendAndRedirectIfNeeded()
			->checkValidBrowserOrDie()
			->loadExtensionTables(TRUE)
			->initializeSpriteManager()
			->initializeBackendUser()
			->initializeBackendAuthentication($proceedIfNoUserIsLoggedIn)
			->initializeLanguageObject()
			->initializeBackendTemplate()
			->endOutputBufferingAndCleanPreviousOutput()
			->initializeOutputCompression()
			->sendHttpHeaders();
	}

	/**
	 * This request handler can handle any backend request coming from mod.php
	 *
	 * @param ServerRequestInterface $request
	 * @return bool
	 */
	public function canHandleRequest(ServerRequestInterface $request) {
		return (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE) && !empty((string)$request->getQueryParams()['M']);
	}

	/**
	 * Checks if all parameters are met.
	 *
	 * @return bool
	 */
	protected function isValidModuleRequest() {
		return $this->getFormProtection()->validateToken((string)$this->request->getQueryParams()['moduleToken'], 'moduleCall', (string)$this->request->getQueryParams()['M']);
	}

	/**
	 * A dispatched module, currently only Extbase modules are dispatched,
	 * traditional modules have a module path set.
	 *
	 * @param string $moduleName
	 * @return bool
	 */
	protected function isDispatchedModule($moduleName) {
		return empty($this->moduleRegistry['_PATHS'][$moduleName]);
	}

	/**
	 * Executes the module dispatcher which calls the module appropriately.
	 * Currently only used by Extbase
	 *
	 * @param string $moduleName
	 * @return bool
	 */
	protected function dispatchModule($moduleName) {
		if (is_array($this->moduleRegistry['_dispatcher'])) {
			foreach ($this->moduleRegistry['_dispatcher'] as $dispatcherClassName) {
				$dispatcher = GeneralUtility::makeInstance(ObjectManager::class)->get($dispatcherClassName);
				if ($dispatcher->callModule($moduleName) === TRUE) {
					return TRUE;
					break;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Calls traditional modules which are identified by having a index.php in their directory
	 * and were previously located within the global scope.
	 *
	 * @param string $moduleName
	 * @return bool
	 */
	protected function callTraditionalModule($moduleName) {
		$moduleBasePath = $this->moduleRegistry['_PATHS'][$moduleName];
		$GLOBALS['MCONF'] = $moduleConfiguration = $this->getModuleConfiguration($moduleName);
		if (!empty($moduleConfiguration['access'])) {
			$this->backendUserAuthentication->modAccess($moduleConfiguration, TRUE);
		}
		if (file_exists($moduleBasePath . 'index.php')) {
			global $SOBE;
			require $moduleBasePath . 'index.php';
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Returns the module configuration which is either provided in a conf.php file
	 * or during module registration
	 *
	 * @param string $moduleName
	 * @return array
	 */
	protected function getModuleConfiguration($moduleName) {
		$moduleBasePath = $this->moduleRegistry['_PATHS'][$moduleName];
		if (file_exists($moduleBasePath . 'conf.php')) {
			// Some modules still rely on this global configuration array in a conf.php file
			require $moduleBasePath . 'conf.php';
			$moduleConfiguration = $MCONF;
		} else {
			$moduleConfiguration = $this->moduleRegistry['_configuration'][$moduleName];
		}
		return $moduleConfiguration;
	}


	/**
	 * Returns the priority - how eager the handler is to actually handle the request.
	 *
	 * @return int The priority of the request handler.
	 */
	public function getPriority() {
		return 90;
	}

	/**
	 * Wrapper method for static form protection utility
	 *
	 * @return \TYPO3\CMS\Core\FormProtection\AbstractFormProtection
	 */
	protected function getFormProtection() {
		return FormProtectionFactory::get();
	}

}
