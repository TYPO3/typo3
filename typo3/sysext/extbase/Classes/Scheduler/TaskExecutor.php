<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Claus Due, Wildside A/S <claus@wildside.dk>
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
 * Task Executor
 *
 * Takes a Tx_Extbase_Scheduler_Task and executes the CommandController command
 * defined therein.
 *
 * @package Extbase
 * @subpackage Scheduler
 */
class Tx_Extbase_Scheduler_TaskExecutor implements t3lib_Singleton {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var Tx_Extbase_MVC_CLI_CommandManager
	 */
	protected $commandManager;

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param Tx_Extbase_Object_ObjectManager $objectManager
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param Tx_Extbase_MVC_CLI_CommandManager $commandManager
	 */
	public function injectCommandManager(Tx_Extbase_MVC_CLI_CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * @param Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(Tx_Extbase_Configuration_ConfigurationManagerInterface $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Initializes configuration manager, object container and reflection service
	 *
	 * @param array $configuration
	 * @return void
	 */
	protected function initialize(array $configuration) {
			// initialize configuration
		$this->configurationManager->setContentObject(t3lib_div::makeInstance('tslib_cObj'));
		$this->configurationManager->setConfiguration($configuration);

			// configure object container
		$typoScriptSetup = $this->configurationManager->getConfiguration(Tx_Extbase_Configuration_ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		if (isset($typoScriptSetup['config.']['tx_extbase.']['objects.'])) {
			$objectContainer = t3lib_div::makeInstance('Tx_Extbase_Object_Container_Container');
			foreach ($typoScriptSetup['config.']['tx_extbase.']['objects.'] as $classNameWithDot => $classConfiguration) {
				if (isset($classConfiguration['className'])) {
					$originalClassName = rtrim($classNameWithDot, '.');
					$objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
				}
			}
		}

			// initialize reflection
		$reflectionService = $this->objectManager->get('Tx_Extbase_Reflection_Service');
		$reflectionService->setDataCache($GLOBALS['typo3CacheManager']->getCache('extbase_reflection'));
		if (!$reflectionService->isInitialized()) {
			$reflectionService->initialize();
		}
	}

	/**
	 * Execute Task
	 *
	 * If errors occur during Task execution they are thrown as Exceptions which
	 * must be caught manually if you manually execute Tasks through your code.
	 *
	 * @param Tx_Extbase_Scheduler_Task $task the task to execute
	 * @return void
	 */
	public function execute(Tx_Extbase_Scheduler_Task $task) {
		$commandIdentifier = $task->getCommandIdentifier();
		list ($extensionKey, $controllerName, $commandName) = explode(':', $commandIdentifier);
		$extensionName = t3lib_div::underscoredToUpperCamelCase($extensionKey);
		$this->initialize(array('extensionName' => $extensionName));

		$request = $this->objectManager->create('Tx_Extbase_MVC_CLI_Request');
		$dispatcher = $this->objectManager->get('Tx_Extbase_MVC_Dispatcher');
		$response = $this->objectManager->create('Tx_Extbase_MVC_CLI_Response');

			// execute command
		$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
		$request->setControllerObjectName($command->getControllerClassName());
		$request->setControllerCommandName($command->getControllerCommandName());
		$request->setArguments($task->getArguments());
		$dispatcher->dispatch($request, $response);

		$this->shutdown();
	}

	/**
	 * Resets framework singletons
	 *
	 * @return void
	 */
	protected function shutdown() {
			// shutdown
		$persistenceManager = $this->objectManager->get('Tx_Extbase_Persistence_Manager');
		$persistenceManager->persistAll();
		$reflectionService = $this->objectManager->get('Tx_Extbase_Reflection_Service');
		$reflectionService->shutdown();
	}

}

?>