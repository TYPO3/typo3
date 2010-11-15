<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Jochen Rau <jochen.rau@typoplanet.de>
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
 * This class is the main entry point for extbase extensions.
 *
 * @package Extbase
 * @version $ID:$
 */
class Tx_Extbase_Core_Bootstrap {

	/**
	 * Back reference to the parent content object
	 * This has to be public as it is set directly from TYPO3
	 *
	 * @var tslib_cObj
	 */
	public $cObj;

	/**
	 * The application context
	 * @var string
	 */
	protected $context;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManager
	 */
	protected $configurationManager;

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var t3lib_cache_Manager
	 */
	protected $cacheManager;

	/**
	 * @var Tx_Extbase_Reflection_Service
	 */
	protected $reflectionService;

	/**
	 * @var Tx_Extbase_Persistence_Manager
	 */
	protected $persistenceManager;

	/**
	 * @var boolean
	 */
	protected $isInitialized = FALSE;

	/**
	 * Explicitly initializes all necessary Extbase objects by invoking the various initialize* methods.
	 *
	 * Usually this method is only called from unit tests or other applications which need a more fine grained control over
	 * the initialization and request handling process. Most other applications just call the run() method.
	 *
	 * @param array $configuration The TS configuration array
	 * @return void
	 * @see run()
	 * @api
	 */
	public function initialize($configuration) {
		$this->initializeClassLoader();
		$this->initializeObjectManager();
		$this->initializeConfiguration($configuration);
		$this->configureObjectManager();
		$this->initializeCache();
		$this->initializeReflection();
		$this->initializePersistence();
		$this->initializeBackwardsCompatibility();
		$this->isInitialized = TRUE;
	}

	/**
	 * Initializes the autoload mechanism of Extbase. This is supplement to the core autoloader.
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeClassLoader() {
		if (!class_exists('Tx_Extbase_Utility_ClassLoader', FALSE)) {
			require(t3lib_extmgm::extPath('extbase') . 'Classes/Utility/ClassLoader.php');
		}

		$classLoader = new Tx_Extbase_Utility_ClassLoader();
		spl_autoload_register(array($classLoader, 'loadClass'));
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeObjectManager() {
		$this->objectManager = t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
	}

	/**
	 * Initializes the Object framework.
	 *
	 * @return void
	 * @see initialize()
	 */
	public function initializeConfiguration($configuration) {
		$this->configurationManager = $this->objectManager->get('Tx_Extbase_Configuration_ConfigurationManagerInterface');
		$contentObject = isset($this->cObj) ? $this->cObj : t3lib_div::makeInstance('tslib_cObj');
		$this->configurationManager->setContentObject($contentObject);
		$this->configurationManager->setConfiguration($configuration);
	}

	/**
	 * Configures the object manager object configuration from
	 * config.tx_extbase.objects
	 *
	 * @return void
	 * @see initialize()
	 */
	public function configureObjectManager() {
		$typoScriptSetup = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		if (isset($typoScriptSetup['config.']['tx_extbase.']['objects.']) && is_array($typoScriptSetup['config.']['tx_extbase.']['objects.'])) {
			$objectConfiguration = $typoScriptSetup['config.']['tx_extbase.']['objects.'];

			foreach ($objectConfiguration as $classNameWithDot => $classConfiguration) {
				if (isset($classConfiguration['className'])) {
					$originalClassName = substr($classNameWithDot, 0, -1);
					Tx_Extbase_Object_Container_Container::getContainer()->registerImplementation($originalClassName, $classConfiguration['className']);
				}
			}
		}
	}

	/**
	 * Initializes the cache framework
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeCache() {
		t3lib_cache::initializeCachingFramework();
		$this->cacheManager = $GLOBALS['typo3CacheManager'];
		try {
			$this->cacheManager->getCache('cache_extbase_reflection');
		} catch (t3lib_cache_exception_NoSuchCache $exception) {
			$GLOBALS['typo3CacheFactory']->create(
				'cache_extbase_reflection',
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['frontend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['backend'],
				$GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations']['cache_extbase_reflection']['options']
			);
		}
	}

	/**
	 * Initializes the Reflection Service
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeReflection() {
		$this->reflectionService = $this->objectManager->get('Tx_Extbase_Reflection_Service');
		$this->reflectionService->setDataCache($this->cacheManager->getCache('cache_extbase_reflection'));
		$this->reflectionService->initialize();
	}

	/**
	 * Initializes the persistence framework
	 *
	 * @return void
	 * @see initialize()
	 */
	public function initializePersistence() {
		$this->persistenceManager = $this->objectManager->get('Tx_Extbase_Persistence_Manager'); // singleton
	}

	/**
	 * Initializes the backwards compatibility. This is necessary because the
	 * old Dispatcher provided several static methods.
	 *
	 * @return void
	 * @see initialize()
	 */
	protected function initializeBackwardsCompatibility() {
		$dispatcher = t3lib_div::makeInstance('Tx_Extbase_Dispatcher');
		$dispatcher->injectConfigurationManager($this->configurationManager);
		$dispatcher->injectPersistenceManager($this->persistenceManager);
	}

	/**
	 * Runs the the Extbase Framework by resolving an appropriate Request Handler and passing control to it.
	 * If the Framework is not initialized yet, it will be initialized.
	 *
	 * @param string $content The content
	 * @param array $configuration The TS configuration array
	 * @return string $content The processed content
	 * @api
	 */
	public function run($content, $configuration) {
		//var_dump(Tx_Extbase_Utility_Extension::createAutoloadRegistryForExtension('extbase', t3lib_extMgm::extPath('extbase'), array(
		//	'tx_extbase_basetestcase' => '$extensionClassesPath . \'../Tests/BaseTestCase.php\''
		//)));
		//die("autoload registry");

		$this->initialize($configuration);

		$requestHandlerResolver = $this->objectManager->get('Tx_Extbase_MVC_RequestHandlerResolver');
		$requestHandler = $requestHandlerResolver->resolveRequestHandler();

		$response = $requestHandler->handleRequest();

		// If response is NULL after handling the request we need to stop
		// This happens for instance, when a USER object was converted to a USER_INT
		// @see Tx_Extbase_MVC_Web_FrontendRequestHandler::handleRequest()
		if ($response === NULL) {
			$this->reflectionService->shutdown();
			return;
		}
		if (count($response->getAdditionalHeaderData()) > 0) {
			$GLOBALS['TSFE']->additionalHeaderData[] = implode(chr(10), $response->getAdditionalHeaderData());
		}
		$response->sendHeaders();
		$content = $response->getContent();

		$this->resetSingletons();
		return $content;
	}

	/**
	 * Resets global singletons for the next plugin
	 *
	 * @return void
	 */
	protected function resetSingletons() {
		$this->persistenceManager->persistAll();
		$this->objectManager->get('Tx_Extbase_MVC_Controller_FlashMessages')->persist();
		$this->reflectionService->shutdown();
	}

	 /**
	  * This method forwards the call to run(). This method is invoked by the mod.php
	  * function of TYPO3.
	  *
	  * @return TRUE
	  * @see run()
	  **/
	public function callModule($moduleName) {
		$configuration = array();
		$configuration['module.']['tx_extbase.']['moduleName'] = $moduleName;
		$this->run('', $configuration);
		return TRUE;
	}
}
?>