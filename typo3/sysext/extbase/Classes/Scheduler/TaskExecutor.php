<?php
namespace TYPO3\CMS\Extbase\Scheduler;

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
 * Takes a \TYPO3\CMS\Extbase\Scheduler\Task and executes the CommandController command
 * defined therein.
 */
class TaskExecutor implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
	 */
	protected $commandManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
	 */
	protected $configurationManager;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager
	 */
	public function injectCommandManager(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager) {
		$this->commandManager = $commandManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager) {
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
		$this->configurationManager->setContentObject(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Frontend\\ContentObject\\ContentObjectRenderer'));
		$this->configurationManager->setConfiguration($configuration);
		// configure object container
		$typoScriptSetup = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
		if (isset($typoScriptSetup['config.']['tx_extbase.']['objects.'])) {
			$objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Extbase\\Object\\Container\\Container');
			foreach ($typoScriptSetup['config.']['tx_extbase.']['objects.'] as $classNameWithDot => $classConfiguration) {
				if (isset($classConfiguration['className'])) {
					$originalClassName = rtrim($classNameWithDot, '.');
					$objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
				}
			}
		}
		// initialize reflection
		$reflectionService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService');
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
	 * @param \TYPO3\CMS\Extbase\Scheduler\Task $task the task to execute
	 * @return void
	 */
	public function execute(\TYPO3\CMS\Extbase\Scheduler\Task $task) {
		$commandIdentifier = $task->getCommandIdentifier();
		list($extensionKey, $controllerName, $commandName) = explode(':', $commandIdentifier);
		$extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extensionKey);
		$this->initialize(array('extensionName' => $extensionName));
		$request = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Request');
		$dispatcher = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher');
		$response = $this->objectManager->create('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
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
		$persistenceManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\PersistenceManager');
		$persistenceManager->persistAll();
		$reflectionService = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Reflection\\ReflectionService');
		$reflectionService->shutdown();
	}

}


?>