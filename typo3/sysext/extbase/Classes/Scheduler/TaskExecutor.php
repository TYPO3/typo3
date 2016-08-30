<?php
namespace TYPO3\CMS\Extbase\Scheduler;

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

/**
 * Task Executor
 *
 * Takes a \TYPO3\CMS\Extbase\Scheduler\Task and executes the CommandController command
 * defined therein.
 */
class TaskExecutor implements \TYPO3\CMS\Core\SingletonInterface
{
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
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager
     */
    public function injectCommandManager(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager)
    {
        $this->commandManager = $commandManager;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager)
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Initialize Dispatcher
     */
    public function initializeObject()
    {
        $this->dispatcher = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Dispatcher::class);
    }

    /**
     * Initializes configuration manager, object container and reflection service
     *
     * @param array $configuration
     * @return void
     */
    protected function initialize(array $configuration)
    {
        // initialize unconsumed Request and Response
        $this->request = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\Request::class);
        $this->response = $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\Response::class);
        // initialize configuration
        $this->configurationManager->setContentObject(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer::class));
        $this->configurationManager->setConfiguration($configuration);
        // configure object container
        $frameworkConfiguration = $this->configurationManager->getConfiguration(\TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);
        if (isset($frameworkConfiguration['objects'])) {
            $objectContainer = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\Container\Container::class);
            foreach ($frameworkConfiguration['objects'] as $classNameWithDot => $classConfiguration) {
                if (isset($classConfiguration['className'])) {
                    $originalClassName = rtrim($classNameWithDot, '.');
                    $objectContainer->registerImplementation($originalClassName, $classConfiguration['className']);
                }
            }
        }
        // initialize reflection
        $reflectionService = $this->objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $reflectionService->setDataCache(\TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('extbase_reflection'));
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
    public function execute(\TYPO3\CMS\Extbase\Scheduler\Task $task)
    {
        $commandIdentifier = $task->getCommandIdentifier();
        list($extensionKey, $controllerName, $commandName) = explode(':', $commandIdentifier);
        $extensionName = \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($extensionKey);
        $this->initialize(['extensionName' => $extensionName]);
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
    protected function shutdown()
    {
        // shutdown
        $persistenceManager = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager::class);
        $persistenceManager->persistAll();
        $reflectionService = $this->objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
        $reflectionService->shutdown();
    }
}
