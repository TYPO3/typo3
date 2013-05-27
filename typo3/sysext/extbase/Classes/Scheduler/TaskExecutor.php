<?php
namespace TYPO3\CMS\Extbase\Scheduler;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
/**
 * Task Executor
 *
 * Takes a \TYPO3\CMS\Extbase\Scheduler\Task and executes the CommandController command
 * defined therein.
 */
class TaskExecutor implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Request
	 */
	protected $request;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Cli\Response
	 */
	protected $response;

	/**
	 * @var \TYPO3\CMS\Extbase\Mvc\Dispatcher
	 */
	protected $dispatcher;

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
	 * Initializes Request, Response and Dispatcher
	 */
	public function initializeObject() {
		$this->request = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Request');
		$this->response = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Cli\\Response');
		$this->dispatcher = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Mvc\\Dispatcher');
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
		// execute command
		$command = $this->commandManager->getCommandByIdentifier($commandIdentifier);
		$this->request->setControllerObjectName($command->getControllerClassName());
		$this->request->setControllerCommandName($command->getControllerCommandName());
		$this->request->setArguments($task->getArguments());
		$this->dispatcher->dispatch($this->request, $this->response);
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