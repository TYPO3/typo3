<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
 * Creates a request an dispatches it to the controller which was specified
 * by TS Setup, Flexform and returns the content to the v4 framework.
 *
 * @package Extbase
 * @subpackage extbase
 * @version $ID:$
 */
class Tx_Extbase_Dispatcher {
	
	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_Persistence_Manager
	 */
	private static $persistenceManager;

	/**
	 * Creates a request an dispatches it to a controller.
	 *
	 * @param string $content The content
	 * @param array|NULL $configuration The TS configuration array
	 * @return string $content The processed content
	 */
	public function dispatch($content, $configuration) {
		if (!is_array($configuration)) {
			t3lib_div::sysLog('Extbase was not able to dispatch the request. No configuration.', 'extbase', t3lib_div::SYSLOG_SEVERITY_ERROR);
			return $content;
		}
		
		$requestBuilder = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_RequestBuilder');
		$request = $requestBuilder->initialize($configuration);
		$request = $requestBuilder->build();
		$response = t3lib_div::makeInstance('Tx_Extbase_MVC_Web_Response');

		$persistenceManager = self::getPersistenceManager($configuration, $this->extractStoragePageId());

		$dispatchLoopCount = 0;
		while (!$request->isDispatched()) {
			if ($dispatchLoopCount++ > 99) throw new Tx_Extbase_MVC_Exception_InfiniteLoop('Could not ultimately dispatch the request after '  . $dispatchLoopCount . ' iterations.', 1217839467);
			$controller = $this->getPreparedController($request);
			try {
				$controller->processRequest($request, $response);
			} catch (Tx_Extbase_Exception_StopAction $ignoredException) {
			} catch (Tx_Extbase_Exception_InvalidArgumentValue $exception) {
				return '';
			}
		}

		$persistenceManager->persistAll();
		$this->reflectionService->shutdown();
		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[$request->getControllerExtensionName()] = implode("\n", $response->getAdditionalHeaderData());
		}
		$response->sendHeaders();
		return $response->getContent();
	}

	/**
	 * Extracts the storage PID from $this->cObj->data['pages']. ONLY ALLOWS ONE STORAGE PID!
	 * If this one is empty, tries to use $this->cObj->parentRecord->data['storage_pid']. If both are empty, uses current page.
	 * 
	 * @return integer
	 * @throws InvalidArgumentException if more than one storage page ID is given
	 */
	protected function extractStoragePageId() {
		if (is_string($this->cObj->data['pages'])) {
			if (count(explode(',', $this->cObj->data['pages'])) > 1) {
				throw new InvalidArgumentException('More than one storage page ID given. This is currently not supported.', 1247597243);
			}
			return (int)$this->cObj->data['pages'];
		}
		
		if ($this->cObj->parentRecord->data['storage_pid'] > 0) {
			return (int)$this->cObj->parentRecord->data['storage_pid'];
		}
		return $GLOBALS['TSFE']->id;
	}
	
	/**
	 * Builds and returns a controller
	 *
	 * @param Tx_Extbase_MVC_Web_Request $request
	 * @return Tx_Extbase_MVC_Controller_ControllerInterface The prepared controller
	 */
	protected function getPreparedController(Tx_Extbase_MVC_Web_Request $request) {
		$controllerObjectName = $request->getControllerObjectName();
		$controller = t3lib_div::makeInstance($controllerObjectName);
		if (!$controller instanceof Tx_Extbase_MVC_Controller_ControllerInterface) {
			throw new Tx_Extbase_Exception_InvalidController('Invalid controller "' . $request->getControllerObjectName() . '". The controller must implement the Tx_Extbase_MVC_Controller_ControllerInterface.', 1202921619);
		}
		$propertyMapper = t3lib_div::makeInstance('Tx_Extbase_Property_Mapper');
		$controller->injectPropertyMapper($propertyMapper);
		$controller->injectSettings($this->getSettings($request));
		$cacheManager = t3lib_div::makeInstance('t3lib_cache_Manager');
		$this->reflectionService = t3lib_div::makeInstance('Tx_Extbase_Reflection_Service');
		try {
			$this->reflectionService->setCache($cacheManager->getCache('Tx_Extbase_Reflection'));
		} catch (t3lib_cache_exception_NoSuchCache $exception) {
			$cacheFactory = t3lib_div::makeInstance('t3lib_cache_Factory');
			$cacheFactory->create(
				'Tx_Extbase_Reflection',
				't3lib_cache_frontend_VariableFrontend',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['Tx_Extbase_Reflection']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['Tx_Extbase_Reflection']['options']
			);
			$this->reflectionService->setCache($cacheManager->getCache('Tx_Extbase_Reflection'));
		}
		if (!$this->reflectionService->isInitialized()) {
			$this->reflectionService->initialize();
		}
		$validatorResolver = t3lib_div::makeInstance('Tx_Extbase_Validation_ValidatorResolver');
		$validatorResolver->injectObjectManager(t3lib_div::makeInstance('Tx_Extbase_Object_Manager'));
		$validatorResolver->injectReflectionService($this->reflectionService);
		$controller->injectValidatorResolver($validatorResolver);
		$controller->injectReflectionService($this->reflectionService);
		return $controller;
	}

	/**
	 * Builds the settings by overlaying TS Setup with FlexForm values of the extension
	 * and returns them as a plain array (with no trailing dots).
	 *
	 * @param Tx_Extbase_MVC_Web_Request $request
	 * @return array The settings array
	 */
	protected function getSettings(Tx_Extbase_MVC_Web_Request $request) {
		$extensionName = $request->getControllerExtensionName();
		$configurationSources = array();
		$configurationSources[] = t3lib_div::makeInstance('Tx_Extbase_Configuration_Source_TypoScriptSource');
		if (!empty($this->cObj->data['pi_flexform'])) {
			$configurationSource = t3lib_div::makeInstance('Tx_Extbase_Configuration_Source_FlexFormSource');
			$configurationSource->setFlexFormContent($this->cObj->data['pi_flexform']);
			$configurationSources[] = $configurationSource;
		}
		$configurationManager = t3lib_div::makeInstance('Tx_Extbase_Configuration_Manager', $configurationSources);
		return $configurationManager->getSettings($extensionName);
	}

	/**
	 * This function prepares and returns the Persistance Manager
	 *
	 * @param array $configuration The given configuration
	 * @param integer $storagePageId Storage page ID to to read and write records.
	 * @return Tx_Extbase_Persistence_Manager A (singleton) instance of the Persistence Manager
	 */
	public static function getPersistenceManager(array $configuration = array(), $storagePageId = 0) {
		if (self::$persistenceManager === NULL) {
			$queryFactory = t3lib_div::makeInstance('Tx_Extbase_Persistence_QueryFactory'); // singleton
			$queryFactory->setStoragePageId($storagePageId);
			
			$persistenceSession = t3lib_div::makeInstance('Tx_Extbase_Persistence_Session'); // singleton
			$storageBackend = t3lib_div::makeInstance('Tx_Extbase_Persistence_Storage_Typo3DbBackend', $GLOBALS['TYPO3_DB']); // singleton
			if (is_array($configuration) && isset($configuration['enableAutomaticCacheClearing']) && $configuration['enableAutomaticCacheClearing'] === '1') {
				$storageBackend->setAutomaticCacheClearing(TRUE);
			} else {
				$storageBackend->setAutomaticCacheClearing(FALSE);
			}
			$dataMapper = t3lib_div::makeInstance('Tx_Extbase_Persistence_Mapper_DataMapper'); // singleton

			$persistenceBackend = t3lib_div::makeInstance('Tx_Extbase_Persistence_Backend', $persistenceSession, $storageBackend, $storagePageId); // singleton
			$persistenceBackend->injectDataMapper($dataMapper);
			$persistenceBackend->injectIdentityMap(t3lib_div::makeInstance('Tx_Extbase_Persistence_IdentityMap'));
			$persistenceBackend->injectQOMFactory(t3lib_div::makeInstance('Tx_Extbase_Persistence_QOM_QueryObjectModelFactory', $storageBackend, $dataMapper));
			$persistenceBackend->injectValueFactory(t3lib_div::makeInstance('Tx_Extbase_Persistence_ValueFactory'));

			$persistenceManager = t3lib_div::makeInstance('Tx_Extbase_Persistence_Manager'); // singleton
			$persistenceManager->injectBackend($persistenceBackend);
			$persistenceManager->injectSession($persistenceSession);

			self::$persistenceManager = $persistenceManager;
		}

		return self::$persistenceManager;
	}

	/**
	 * Loads php files containing classes or interfaces found in the classes directory of
	 * an extension.
	 *
	 * @param string $className: Name of the class/interface to load
	 * @uses t3lib_extMgm::extPath()
	 * @return void
	 */
	public static function autoloadClass($className) {
		$classNameParts = explode('_', $className);
		$extensionKey = t3lib_div::camelCaseToLowerCaseUnderscored($classNameParts[1]);
		if (t3lib_extMgm::isLoaded($extensionKey)) {
			if ($classNameParts[0] === 'ux') {
				array_shift($classNameParts);
			}
			$className = implode('_', $classNameParts);
			if (count($classNameParts) > 2 && $classNameParts[0] === 'Tx') {
				$classFilePathAndName = t3lib_extMgm::extPath(t3lib_div::camelCaseToLowerCaseUnderscored($classNameParts[1])) . 'Classes/';
				$classFilePathAndName .= implode(array_slice($classNameParts, 2, -1), '/') . '/';
				$classFilePathAndName .= array_pop($classNameParts) . '.php';
			}
			if (isset($classFilePathAndName) && file_exists($classFilePathAndName)) {
				require_once($classFilePathAndName);
			}
		}
	}

}
?>