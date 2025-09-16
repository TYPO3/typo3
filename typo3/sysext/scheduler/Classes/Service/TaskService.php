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
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Exception\InvalidDateException;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Service class helping to retrieve data for EXT:scheduler
 * @internal This is not a public API method, do not use in own extensions
 */
#[Autoconfigure(public: true)]
class TaskService
{
    public function __construct(
        protected readonly CommandRegistry $commandRegistry,
    ) {}

    /**
     * This method fetches a list of all classes that have been registered with the Scheduler
     * For each item the following information is provided, as an associative array:
     *
     * ['className'] => Name of the task PHP class
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'] ?? [] as $className => $registrationInformation) {
            $title = isset($registrationInformation['title']) ? $languageService->sL($registrationInformation['title']) : '';
            $description = isset($registrationInformation['description']) ? $languageService->sL($registrationInformation['description']) : '';
            $list[$className] = [
                'className' => $className,
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
        return isset($this->getAvailableTaskTypes()[$taskType]);
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
        foreach ($this->getAvailableTaskTypes() as $taskType => $registrationInformation) {
            $data = [
                'className' => $registrationInformation['className'],
                'taskType' => $taskType,
                'category' => $registrationInformation['extension'],
                'title' => $registrationInformation['title'],
                'fullTitle' => $registrationInformation['title'] . ' [' . $registrationInformation['extension'] . ']',
                'description' => $registrationInformation['description'],
                'provider' => $registrationInformation['provider'] ?? '',
            ];
            if ($registrationInformation['className'] === ExecuteSchedulableCommandTask::class) {
                foreach ($this->commandRegistry->getSchedulableCommands() as $commandIdentifier => $command) {
                    $commandData = $data;
                    $commandData['category'] = explode(':', $commandIdentifier)[0];
                    $commandData['title'] = $command->getName();
                    $commandData['description'] = $command->getDescription();
                    // Used for select dropdown and on InfoScreen
                    $commandData['fullTitle'] = $command->getDescription() . ' [' . $command->getName() . ']';
                    $commandData['isCliCommand'] = true;
                    $taskTypes[$commandIdentifier] = $commandData;
                }
            } else {
                $taskTypes[$taskType] = $data;
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

    public function getFieldsForRecord(AbstractTask $task): array
    {
        try {
            if ($task->getRunOnNextCronJob()) {
                $executionTime = time();
            } else {
                $executionTime = $task->getExecution()->getNextExecution();
            }
            $task->setExecutionTime($executionTime);
        } catch (\Exception) {
            $task->setDisabled(true);
            $executionTime = 0;
        }
        return [
            'nextexecution' => $executionTime,
            'disable' => (int)$task->isDisabled(),
            'description' => $task->getDescription(),
            'task_group' => $task->getTaskGroup(),
            'tasktype' => $task->getTaskType(),
            'parameters' => $task->getTaskParameters(),
            'execution_details' => $task->getExecution()->toArray(),
        ];
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
            $this->getAllTaskTypes()[$taskType]['className']
        );
        if ($task instanceof ExecuteSchedulableCommandTask) {
            $task->setTaskType($taskType);
        }
        return $task;
    }

    public function getHumanReadableTaskName(AbstractTask $task): string
    {
        $taskInformation = $this->getAllTaskTypes()[$task->getTaskType()] ?? null;
        if (!is_array($taskInformation)) {
            throw new \RuntimeException('Task Type ' . $task->getTaskType() . ' not found in list of registered tasks', 1641658569);
        }
        return $taskInformation['fullTitle'];
    }

    /**
     * Prepared additional fields from field providers for rendering.
     */
    public function prepareAdditionalFields(string $taskType, array $newAdditionalFields, array $currentAdditionalFields = []): array
    {
        foreach ($newAdditionalFields as $fieldID => $fieldInfo) {
            // fetch the name attribute of the input tag
            $inputName = '';
            if (is_string($fieldInfo['code'] ?? null) && str_contains($fieldInfo['code'] ?? '', '<input')) {
                $matches = [];
                preg_match('/name="([^"]+)"/', $fieldInfo['code'], $matches);
                $inputName = $matches[1];
            }
            $currentAdditionalFields[] = [
                'taskType' => $taskType,
                'fieldID' => $fieldID,
                'htmlClassName' => strtolower(str_replace('\\', '-', $taskType)),
                'code' => $fieldInfo['code'] ?? '',
                'cshKey' => $fieldInfo['cshKey'] ?? '',
                'cshLabel' => $fieldInfo['cshLabel'] ?? '',
                'langLabel' => $this->getLanguageService()->sL($fieldInfo['label'] ?? ''),
                'browser' => $fieldInfo['browser'] ?? '',
                'browserParams' => ($inputName ? $inputName . '|||' . 'pages' . '|' : ''),
                'pageTitle' => $fieldInfo['pageTitle'] ?? '',
                'pageUid' => (int)($fieldInfo['pageUid'] ?? 0),
                'renderType' => $fieldInfo['type'] ?? '',
                'description' => $fieldInfo['description'] ?? '',
            ];
        }
        return $currentAdditionalFields;
    }

    public function setTaskDataFromRequest(AbstractTask $task, array $incomingData): AbstractTask
    {
        $incomingData = array_merge($incomingData, $incomingData['execution_details'] ?? []);
        $incomingData = array_merge($incomingData['parameters'] ?? [], $incomingData);
        $endTime = $incomingData['end'] ?? '';
        $frequency = $incomingData['frequency'] ?? $incomingData['cronCmd'] ?? '';
        $runningType = (int)($incomingData['runningType'] ?? ($frequency ? AbstractTask::TYPE_RECURRING : AbstractTask::TYPE_SINGLE));
        if ($runningType === AbstractTask::TYPE_SINGLE) {
            $execution = Execution::createSingleExecution($this->getTimestampFromDateString($incomingData['start']));
        } else {
            $execution = Execution::createRecurringExecution(
                $this->getTimestampFromDateString($incomingData['start']),
                is_numeric($frequency) ? (int)$frequency : 0,
                !empty($endTime) ? $this->getTimestampFromDateString($endTime) : 0,
                (bool)($incomingData['multiple'] ?? false),
                !is_numeric($frequency) ? $frequency : '',
            );
        }
        $task->setExecution($execution);
        $task->setDisabled($incomingData['disable'] ?? false);
        $task->setDescription($incomingData['description'] ?? '');
        if (str_starts_with((string)$incomingData['task_group'], 'tx_scheduler_task_group_')) {
            $incomingData['task_group'] = (int)substr($incomingData['task_group'], 24);
        }
        $task->setTaskGroup((int)($incomingData['task_group'] ?? 0));
        $provider = $this->getAdditionalFieldProviderForTask($task->getTaskType());
        $provider?->saveAdditionalFields($incomingData, $task);
        return $task;
    }

    /**
     * Convert input to DateTime and retrieve timestamp.
     *
     * @throws InvalidDateException
     */
    protected function getTimestampFromDateString(int|string $input): int
    {
        if ($input === '') {
            return 0;
        }
        if (MathUtility::canBeInterpretedAsInteger($input)) {
            // Already looks like a timestamp
            return (int)$input;
        }
        try {
            // Convert from ISO 8601 dates
            $value = (new \DateTime($input))->getTimestamp();
        } catch (\Exception $e) {
            throw new InvalidDateException($e->getMessage(), 1746744540);
        }
        return $value;
    }

    /**
     * Used in FormEngine and Backend listings
     */
    public function getTaskTypesForTcaItems(array &$config): array
    {
        $taskTypes = $this->getAllTaskTypes();
        foreach ($taskTypes as $taskType => $taskInformation) {
            $config['items'][] = new SelectItem(
                type: 'select',
                label: $taskInformation['fullTitle'],
                value: $taskType,
                group: $taskInformation['category'],
                description: $taskInformation['description'],
            );
        }
        // Sort all items by group, and groups as well
        usort($config['items'], static function (SelectItem $a, SelectItem $b): int {
            if ($a->getGroup() === 'scheduler') {
                return -1;
            }
            $groupComparison = strnatcasecmp($a->getGroup(), $b->getGroup());
            if ($groupComparison !== 0) {
                return $groupComparison;
            }
            return strnatcasecmp($a->getLabel(), $b->getLabel());
        });
        return $config;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
