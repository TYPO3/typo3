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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Cli\CommandManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * Scheduler task to execute CommandController commands
 * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Use symfony/console commands instead.
 */
class Task extends AbstractTask
{
    /**
     * @var string
     */
    protected $commandIdentifier;

    /**
     * @var array
     */
    protected $arguments = [];

    /**
     * @var array
     */
    protected $defaults = [];

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var CommandManager
     */
    protected $commandManager;

    /**
     * @var TaskExecutor
     */
    protected $taskExecutor;

    /**
     * Instantiates the Object Manager
     */
    public function __construct()
    {
        parent::__construct();
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->commandManager = $this->objectManager->get(CommandManager::class);
        $this->taskExecutor = $this->objectManager->get(TaskExecutor::class);
    }

    /**
     * Sleep
     *
     * @return array Properties to serialize
     */
    public function __sleep()
    {
        $properties = get_object_vars($this);
        unset(
            $properties['commandManager'],
            $properties['objectManager'],
            $properties['taskExecutor'],
            $properties['logger']
        );
        return array_keys($properties);
    }

    /**
     * Wakeup
     */
    public function __wakeup()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->commandManager = $this->objectManager->get(CommandManager::class);
        $this->taskExecutor = $this->objectManager->get(TaskExecutor::class);
    }

    /**
     * Function execute from the Scheduler
     *
     * @return bool TRUE on successful execution
     * @throws \Exception If an error occurs
     */
    public function execute()
    {
        try {
            $this->taskExecutor->execute($this);
        } catch (\Exception $e) {
            $this->logException($e);
            // Make sure the Scheduler gets exception details
            throw $e;
        }
        return true;
    }

    /**
     * @param string $commandIdentifier
     */
    public function setCommandIdentifier($commandIdentifier)
    {
        $this->commandIdentifier = $commandIdentifier;
    }

    /**
     * @return string
     */
    public function getCommandIdentifier()
    {
        return $this->commandIdentifier;
    }

    /**
     * @param array $arguments
     */
    public function setArguments($arguments)
    {
        $this->arguments = $arguments;
    }

    /**
     * @return array
     */
    public function getArguments()
    {
        return $this->arguments;
    }

    /**
     * @param array $defaults
     */
    public function setDefaults(array $defaults)
    {
        $this->defaults = $defaults;
    }

    /**
     * @return array
     */
    public function getDefaults()
    {
        return $this->defaults;
    }

    /**
     * @param string $argumentName
     * @param mixed $argumentValue
     */
    public function addDefaultValue($argumentName, $argumentValue)
    {
        if (is_bool($argumentValue)) {
            $argumentValue = (int)$argumentValue;
        }
        $this->defaults[$argumentName] = $argumentValue;
    }

    /**
     * Return a text representation of the selected command and arguments
     *
     * @return string Information to display
     */
    public function getAdditionalInformation()
    {
        $label = $this->commandIdentifier;
        if (!empty($this->arguments)) {
            $arguments = [];
            foreach ($this->arguments as $argumentName => $argumentValue) {
                if (!isset($this->defaults[$argumentName]) || $argumentValue != $this->defaults[$argumentName]) {
                    $arguments[] = $argumentName . '=' . $argumentValue;
                }
            }
            $label .= ' ' . implode(', ', $arguments);
        }
        return $label;
    }

    /**
     * @param \Exception $e
     */
    protected function logException(\Exception $e)
    {
        $this->logger->error('A Task (' . $this->commandIdentifier . ') Exception was captured: ' . $e->getMessage() . ' (' . $e->getCode() . ')', ['exception' => $e]);
    }
}
