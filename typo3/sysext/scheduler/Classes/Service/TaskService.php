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

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Field\NoneFieldType;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Exception\InvalidDateException;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Execution;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Service class helping to retrieve data for EXT:scheduler
 * @internal This is not a public API method, do not use in own extensions
 */
#[Autoconfigure(public: true)]
readonly class TaskService
{
    public function __construct(
        protected CommandRegistry $commandRegistry,
        protected TcaSchemaFactory $tcaSchemaFactory,
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
    protected function getAvailableTaskTypes(bool $includeNativeTypes = true): array
    {
        $languageService = $this->getLanguageService();
        $list = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'] ?? [] as $className => $registrationInformation) {
            $title = isset($registrationInformation['title']) ? ($languageService?->sL($registrationInformation['title']) ?? $registrationInformation['title']) : '';
            $description = isset($registrationInformation['description']) ? ($languageService?->sL($registrationInformation['description']) ?? $registrationInformation['description']) : '';
            $list[$className] = [
                'className' => $className,
                'extension' => $registrationInformation['extension'] ?? '',
                'icon' => $registrationInformation['icon'] ?? '',
                'title' => $title,
                'description' => $description,
                'provider' => $registrationInformation['additionalFields'] ?? '',
                'isNativeTask' => false,
                'additionalFields' => [],
            ];
        }
        if ($includeNativeTypes) {
            $schema = $this->tcaSchemaFactory->get('tx_scheduler_task');
            $defaultFields = ['tasktype', 'task_group', 'description', 'parameters', 'execution_details', 'nextexecution', 'lastexecution_context', 'lastexecution_time', 'lastexecution_failure', 'disable'];
            // Loop over TCA items, and check if the task type is registered via TCA
            foreach ($schema->getField('tasktype')->getConfiguration()['items'] ?? [] as $item) {
                if (is_array($item) && $item['value'] !== 'div') {
                    $taskType = $className = $item['value'];

                    $additionalFields = [];
                    if ($schema->hasSubSchema($taskType)) {
                        $subSchema = $schema->getSubSchema($taskType);
                        if ($subSchema->getRawConfiguration()['taskOptions']['className'] ?? false) {
                            $className = $subSchema->getRawConfiguration()['taskOptions']['className'];
                        }
                        $additionalFields = $subSchema->getFields(fn($field) => !in_array($field->getName(), $defaultFields, true) && $field instanceof NoneFieldType === false);
                        $additionalFields = $additionalFields->getNames();
                    }

                    $list[$taskType] = [
                        'taskType' => $taskType,
                        'className' => $className,
                        'extension' => $item['group'] ?? '',
                        'icon' => $item['icon'] ?? '',
                        'iconOverlay' => $item['iconOverlay'] ?? '',
                        'title' => $languageService?->sL($item['label'] ?? '') ?? $item['label'] ?? '',
                        'description' => $languageService?->sL($item['description'] ?? '') ?? $item['description'] ?? '',
                        'provider' => '',
                        'isNativeTask' => true,
                        'additionalFields' => $additionalFields,
                    ];
                }
            }
        }
        return $list;
    }

    public function getAllTaskTypes(bool $includeNativeTypes = true): array
    {
        $taskTypes = [];
        foreach ($this->getAvailableTaskTypes($includeNativeTypes) as $taskType => $registrationInformation) {
            $taskTypes[$taskType] = [
                'className' => $registrationInformation['className'],
                'taskType' => $taskType,
                'category' => $registrationInformation['extension'],
                'icon' => $registrationInformation['icon'],
                // @todo Remove null coalescing once definition via $GLOBALS['TYPO3_CONF_VARS'] is removed
                'iconOverlay' => $registrationInformation['iconOverlay'] ?? '',
                'title' => $registrationInformation['title'],
                'fullTitle' => $registrationInformation['title'] . ' [' . $registrationInformation['extension'] . ']',
                'description' => $registrationInformation['description'],
                'provider' => $registrationInformation['provider'] ?? '',
                'isNativeTask' => $registrationInformation['isNativeTask'],
                'additionalFields' => $registrationInformation['additionalFields'],
            ];
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

    public function getTaskDetailsFromTask(AbstractTask $taskObject): ?array
    {
        $allTaskTypes = $this->getAllTaskTypes();
        if (isset($allTaskTypes[$taskObject->getTaskType()])) {
            return $allTaskTypes[$taskObject->getTaskType()];
        }
        if (isset($allTaskTypes[get_class($taskObject)])) {
            return $allTaskTypes[get_class($taskObject)];
        }
        foreach ($allTaskTypes as $taskInformation) {
            if ($taskInformation['className'] === get_class($taskObject)) {
                return $taskInformation;
            }
        }
        return null;
    }

    public function getTaskDetailsFromTaskType(string $taskType): ?array
    {
        $allTaskTypes = $this->getAllTaskTypes();
        if (isset($allTaskTypes[$taskType])) {
            return $allTaskTypes[$taskType];
        }
        foreach ($allTaskTypes as $taskInformation) {
            if ($taskInformation['className'] === $taskType) {
                return $taskInformation;
            }
        }
        return null;
    }

    public function isTaskTypeRegistered(string $taskType): bool
    {
        $allTaskTypes = $this->getAllTaskTypes();
        if (isset($allTaskTypes[$taskType])) {
            return true;
        }
        foreach ($allTaskTypes as $taskInformation) {
            if ($taskInformation['className'] === $taskType) {
                return true;
            }
        }

        return false;
    }

    /**
     * Native fields are added / managed via FormEngine + dataHandler,
     * so this only returns additional fields from the task object that are needed.
     */
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
        $fields = [
            'nextexecution' => $executionTime,
            'disable' => (int)$task->isDisabled(),
            'description' => $task->getDescription(),
            'task_group' => $task->getTaskGroup(),
            'tasktype' => $task->getTaskType(),
            'execution_details' => $task->getExecution()->toArray(),
        ];
        $taskDetails = $this->getTaskDetailsFromTask($task);
        // Put the parameters in a separate field
        if (!($taskDetails['isNativeTask'] ?? false)) {
            $fields['parameters'] = $task->getTaskParameters();
        }
        return $fields;
    }

    public function getAdditionalFieldProviderForTask(string $taskType): ?AdditionalFieldProviderInterface
    {
        $taskInformation = $this->getTaskDetailsFromTaskType($taskType);
        $provider = null;
        if (!empty($taskInformation['provider'])) {
            trigger_error(
                $taskInformation['provider'] . ' is using AdditionalFieldProviderInterface, which is deprecated and will be removed in TYPO3 v15.0. Use native TCA-based scheduler tasks instead.',
                E_USER_DEPRECATED
            );
            /** @var AdditionalFieldProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($taskInformation['provider']);
        }
        return $provider;
    }

    public function createNewTask(string $taskType): AbstractTask
    {
        if (!$this->isTaskTypeRegistered($taskType)) {
            throw new InvalidTaskException('Can not create task for unknown type ' . $taskType . '.', 1758885935);
        }
        /** @var AbstractTask $task */
        $task = GeneralUtility::makeInstance($this->getTaskDetailsFromTaskType($taskType)['className']);
        if ($task instanceof ExecuteSchedulableCommandTask) {
            $task->setTaskType($taskType);
        }
        return $task;
    }

    public function getHumanReadableTaskName(AbstractTask $task): string
    {
        if (!$this->isTaskTypeRegistered($task->getTaskType())) {
            throw new \RuntimeException('Task Type ' . $task->getTaskType() . ' not found in list of registered tasks', 1641658569);
        }
        return $this->getAllTaskTypes()[$task->getTaskType()]['fullTitle'];
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
                'langLabel' => $this->getLanguageService()?->sL($fieldInfo['label'] ?? ''),
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

    public function setTaskDataFromRequest(AbstractTask $task, array $incomingData): void
    {
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
     * Used in FormEngine. Actually, this is only needed to task types can be "validated" by form data providers.
     * There is no possibility to "select" a task type in FormEngine. The field is a readonly information.
     */
    public function getTaskTypesForTcaItems(array &$config, mixed $_ = null, bool $includeNativeItems = false): array
    {
        $taskTypes = $this->getAllTaskTypes($includeNativeItems);
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
            $groupComparison = strnatcasecmp($a->getGroup(), $b->getGroup());
            if ($groupComparison !== 0) {
                return $groupComparison;
            }
            return strnatcasecmp($a->getLabel(), $b->getLabel());
        });
        return $config;
    }

    private function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
