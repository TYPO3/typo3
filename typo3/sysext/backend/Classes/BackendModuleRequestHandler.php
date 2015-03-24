<?php
namespace TYPO3\CMS\Backend;

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
	 * @param Bootstrap $bootstrap The TYPO3 core bootstrap
	 */
	public function __construct(Bootstrap $bootstrap) {
		$this->bootstrap = $bootstrap;
	}

	/**
	 * Handles the request, evaluating the configuration and executes the module accordingly
	 *
	 * @throws Exception
	 */
	public function handleRequest() {
		$this->boot();

		$this->moduleRegistry = $GLOBALS['TBE_MODULES'];

		if (!$this->isValidModuleRequest()) {
			throw new Exception('The CSRF protection token for the requested module is missing or invalid', 1417988921);
		}

		// Set to empty as it is not needed / always coming from typo3/mod.php
		$GLOBALS['BACK_PATH'] = '';

		$this->backendUserAuthentication = $GLOBALS['BE_USER'];

		$moduleName = (string)GeneralUtility::_GET('M');
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
		$this->bootstrap->checkLockedBackendAndRedirectOrDie()
			->checkBackendIpOrDie()
			->checkSslBackendAndRedirectIfNeeded()
			->checkValidBrowserOrDie()
			->loadExtensionTables(TRUE)
			->initializeSpriteManager()
			->initializeBackendUser()
			->initializeBackendAuthentication()
			->initializeLanguageObject()
			->initializeBackendTemplate()
			->endOutputBufferingAndCleanPreviousOutput()
			->initializeOutputCompression()
			->sendHttpHeaders();
	}

	/**
	 * This request handler can handle any backend request coming from mod.php
	 *
	 * @return bool
	 */
	public function canHandleRequest() {
		return (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_BE) && !empty((string)GeneralUtility::_GET('M'));
	}

	/**
	 * Checks if all parameters are met.
	 *
	 * @return bool
	 */
	protected function isValidModuleRequest() {
		return $this->getFormProtection()->validateToken((string)GeneralUtility::_GP('moduleToken'), 'moduleCall', (string)GeneralUtility::_GET('M'));
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
