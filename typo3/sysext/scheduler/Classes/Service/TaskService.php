<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Scheduler\Service;

use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Service class helping to retrieve data for EXT:scheduler
 * @internal This is not a public API method, do not use in own extensions
 */
class TaskService
{
    public function __construct(
        protected readonly CommandRegistry $commandRegistry,
    ) {}

    /**
     * This method fetches a list of all classes that have been registered with the Scheduler
     * For each item the following information is provided, as an associative array:
     *
     * ['extension'] => Key of the extension which provides the class
     * ['filename'] => Path to the file containing the class
     * ['title'] => String (possibly localized) containing a human-readable name for the class
     * ['provider'] => Name of class that implements the interface for additional fields, if necessary
     *
     * The name of the class itself is used as the key of the list array
     */
    protected function getAvailableTaskTypes(): array
    {
        $languageService = $this->getLanguageService();
        $list = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'] ?? [] as $class => $registrationInformation) {
            $title = isset($registrationInformation['title']) ? $languageService->sL($registrationInformation['title']) : '';
            $description = isset($registrationInformation['description']) ? $languageService->sL($registrationInformation['description']) : '';
            $list[$class] = [
                'extension' => $registrationInformation['extension'],
                'title' => $title,
                'description' => $description,
                'provider' => $registrationInformation['additionalFields'] ?? '',
            ];
        }
        return $list;
    }

    public function hasTaskType(string $taskType): bool
    {
        return isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskType]);
    }

    /**
     * If the "command" task is registered, create a list of available commands to be rendered.
     *
     * @return Command[]
     */
    public function getRegisteredCommands(): array
    {
        $commands = [];
        if (array_key_exists(ExecuteSchedulableCommandTask::class, $this->getAvailableTaskTypes())) {
            foreach ($this->commandRegistry->getSchedulableCommands() as $commandIdentifier => $command) {
                $commands[$commandIdentifier] = $command;
            }
            ksort($commands);
        }
        return $commands;
    }

    public function getAllTaskTypes(): array
    {
        $taskTypes = [];
        foreach ($this->getAvailableTaskTypes() as $taskClass => $registrationInformation) {
            $data = [
                'class' => $taskClass,
                'category' => $registrationInformation['extension'],
                'title' => $registrationInformation['title'],
                'fullTitle' => $registrationInformation['title'] . ' [' . $registrationInformation['extension'] . ']',
                'description' => $registrationInformation['description'],
                'provider' => $registrationInformation['provider'] ?? '',
            ];
            if ($taskClass === ExecuteSchedulableCommandTask::class) {
                foreach ($this->commandRegistry->getSchedulableCommands() as $commandIdentifier => $command) {
                    $commandData = $data;
                    $commandData['category'] = explode(':', $commandIdentifier)[0];
                    $commandData['title'] = $command->getName();
                    $commandData['description'] = $command->getDescription();
                    // Used for select dropdown and on InfoScreen
                    $commandData['fullTitle'] = $command->getDescription() . ' [' . $command->getName() . ']';
                    $taskTypes[$commandIdentifier] = $commandData;
                }
            } else {
                $taskTypes[$taskClass] = $data;
            }
        }
        ksort($taskTypes);
        return $taskTypes;
    }

    public function getCategorizedTaskTypes(): array
    {
        $categorizedTaskTypes = [];
        foreach ($this->getAllTaskTypes() as $taskType => $taskInformation) {
            $categorizedTaskTypes[$taskInformation['category']][$taskType] = $taskInformation;
        }
        ksort($categorizedTaskTypes);
        return $categorizedTaskTypes;
    }

    public function getAdditionalFieldProviderForTask(string $taskType): ?AdditionalFieldProviderInterface
    {
        $taskInformation = $this->getAllTaskTypes()[$taskType];
        $provider = null;
        if (!empty($taskInformation['provider'])) {
            /** @var AdditionalFieldProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($taskInformation['provider']);
        }
        return $provider;
    }

    public function createNewTask(string $taskType): AbstractTask
    {
        /** @var AbstractTask $task */
        $task = GeneralUtility::makeInstance(
            $this->getAllTaskTypes()[$taskType]['class']
        );
        if ($task instanceof ExecuteSchedulableCommandTask) {
            $task->setTaskType($taskType);
        }
        return $task;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
