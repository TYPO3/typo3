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
use TYPO3\CMS\Core\Core\ApplicationInterface;
use TYPO3\CMS\Core\Core\Bootstrap;


/**
 * Entry point for the TYPO3 Backend (HTTP requests)
 */
class Application implements ApplicationInterface {

	/**
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 * @var string
	 */
	protected $entryPointPath = 'typo3/';

	/**
	 * All available request handlers that can handle backend requests (non-CLI)
	 * @var array
	 */
	protected $availableRequestHandlers = array(
		\TYPO3\CMS\Backend\Http\RequestHandler::class,
		\TYPO3\CMS\Backend\Http\BackendModuleRequestHandler::class,
		\TYPO3\CMS\Backend\Http\AjaxRequestHandler::class
	);

	/**
	 * Constructor setting up legacy constant and register available Request Handlers
	 *
	 * @param \Composer\Autoload\ClassLoader|\Helhum\ClassAliasLoader\Composer\ClassAliasLoader $classLoader an instance of the class loader
	 */
	public function __construct($classLoader) {
		$this->defineLegacyConstants();

		$this->bootstrap = Bootstrap::getInstance()
			->initializeClassLoader($classLoader)
			->baseSetup($this->entryPointPath);

		// can be done here after the base setup is done
		$this->defineAdditionalEntryPointRelatedConstants();

		// Redirect to install tool if base configuration is not found
		if (!$this->bootstrap->checkIfEssentialConfigurationExists()) {
			$this->bootstrap->redirectToInstallTool($this->entryPointPath);
		}

		foreach ($this->availableRequestHandlers as $requestHandler) {
			$this->bootstrap->registerRequestHandlerImplementation($requestHandler);
		}

		$this->bootstrap->configure();
	}

	/**
	 * Set up the application and shut it down afterwards
	 *
	 * @param callable $execute
	 * @return void
	 */
	public function run(callable $execute = NULL) {
		$this->bootstrap->handleRequest(\TYPO3\CMS\Core\Http\ServerRequestFactory::fromGlobals());

		if ($execute !== NULL) {
			if ($execute instanceof \Closure) {
				$execute->bindTo($this);
			}
			call_user_func($execute);
		}

		$this->bootstrap->shutdown();
	}

	/**
	 * Define constants and variables
	 */
	protected function defineLegacyConstants() {
		define('TYPO3_MODE', 'BE');
	}

	/**
	 * Define values that are based on the current script
	 */
	protected function defineAdditionalEntryPointRelatedConstants() {
		$currentScript = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('SCRIPT_NAME');

		// activate "AJAX" handler when called via ajax.php
		if (substr($currentScript, -15) === '/typo3/ajax.php') {
			$GLOBALS['TYPO3_AJAX'] = TRUE;
		}
		// allow backend login to work
		if (substr($currentScript, -16) === '/typo3/index.php') {
			define('TYPO3_PROCEED_IF_NO_USER', 1);
		}
	}
}
