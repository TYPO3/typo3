<?php
namespace TYPO3\CMS\Backend\Console;

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
 * Entry point for the TYPO3 Command Line for Backend calls
 */
class Application implements ApplicationInterface {

	/**
	 * @var Bootstrap
	 */
	protected $bootstrap;

	/**
	 *
	 * @var string
	 */
	protected $entryPointPath = 'typo3/';

	/**
	 * All available request handlers that can deal with a CLI Request
	 * @var array
	 */
	protected $availableRequestHandlers = array(
		\TYPO3\CMS\Backend\Console\CliRequestHandler::class
	);

	/**
	 * Constructor setting up legacy constants and register available Request Handlers
	 *
	 * @param \Composer\Autoload\ClassLoader|\Helhum\ClassAliasLoader\Composer\ClassAliasLoader $classLoader an instance of the class loader
	 */
	public function __construct($classLoader) {
		$this->defineLegacyConstants();

		\TYPO3\CMS\Core\Core\CliBootstrap::checkEnvironmentOrDie();

		$this->bootstrap = Bootstrap::getInstance()
			->initializeClassLoader($classLoader);

		foreach ($this->availableRequestHandlers as $requestHandler) {
			$this->bootstrap->registerRequestHandlerImplementation($requestHandler);
		}
	}

	/**
	 * Set up the application and shut it down afterwards
	 *
	 * @param callable $execute
	 * @return void
	 */
	public function run(callable $execute = NULL) {
		$this->bootstrap->run();

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
		define('TYPO3_cliMode', TRUE);
	}
}
