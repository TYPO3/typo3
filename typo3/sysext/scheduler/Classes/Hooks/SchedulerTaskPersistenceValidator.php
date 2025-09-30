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

namespace TYPO3\CMS\Scheduler\Hooks;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Exception\InvalidDateException;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\SchedulerManagementAction;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * DataHandler hook to validate incoming task parameters and execution_details
 * when creating or updating a task.
 *
 * @internal This is an internal hook implementation and is not considered part of the Public TYPO3 API.
 */
#[Autoconfigure(public: true)]
final readonly class SchedulerTaskPersistenceValidator
{
    private FlashMessageQueue $flashMessageQueue;

    public function __construct(
        private TaskService $taskService,
        private CommandRegistry $commandRegistry,
        private SchedulerTaskRepository $taskRepository,
        private SchedulerModuleController $schedulerModuleController,
        FlashMessageService $flashMessageService,
    ) {
        $this->flashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
    }

    /**
     * If the task is not valid, this hook will create an error log message AND make the incomingFieldArray
     * a non-array (e.g. false) to skip saving this record.
     */
    public function processDatamap_preProcessFieldArray(&$incomingFieldArray, $table, $id, DataHandler $dataHandler): void
    {
        if ($table !== 'tx_scheduler_task') {
            return;
        }
        $isNewTask = false;
        if (MathUtility::canBeInterpretedAsInteger($id)) {
            // Only execution is updated, do not validate anything else
            if (count($incomingFieldArray) === 3 && isset($incomingFieldArray['nextexecution'], $incomingFieldArray['disable'], $incomingFieldArray['execution_details'])) {
                return;
            }

            // Update process
            $fullRecord = BackendUtility::getRecord($table, $id);
            $changedTaskType = ($incomingFieldArray['tasktype'] ?? false) !== ($fullRecord['tasktype'] ?? false);
            if (!isset($incomingFieldArray['tasktype'])) {
                $taskType = $fullRecord['tasktype'];
                $this->schedulerModuleController->setCurrentAction(SchedulerManagementAction::EDIT);
            } else {
                $taskType = $incomingFieldArray['tasktype'];
                $this->schedulerModuleController->setCurrentAction(SchedulerManagementAction::ADD);
            }
            if (!empty($fullRecord['serialized_executions'])) {
                // If there's a registered execution, the task should not be edited. May happen if a cron started the task meanwhile.
                $this->addErrorMessage($dataHandler, $id, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.maynotEditRunningTask');
            }
        } else {
            $isNewTask = true;
            $changedTaskType = true;
            $taskType = $incomingFieldArray['tasktype'];
            $this->schedulerModuleController->setCurrentAction(SchedulerManagementAction::ADD);
        }
        $decodedAndExtractedFieldArray = $this->decodeValues($incomingFieldArray);
        if (!$this->isSubmittedTaskDataValid($dataHandler, $id, $decodedAndExtractedFieldArray, $taskType)) {
            // Custom AdditionalFieldProvider may have added error messages via the FlashMessageQueue (as recommended)
            // which is needed to render them properly in FormEngine via $dataHandler->printLogErrorMessages();
            $this->convertErrorMessagesToDataHandlerLog($dataHandler, $id);
            // Setting this to a "non-array" will skip further persistence chain
            $incomingFieldArray = false;
            return;
        }
        if ($isNewTask) {
            try {
                $task = $this->taskService->createNewTask($taskType);
            } catch (InvalidTaskException $e) {
                // Task can not be further processed since task type is not valid
                $dataHandler->log('tx_scheduler_task', $id, 1, null, SystemLogErrorClassification::WARNING, 'Task can not be further processed since task type ' . $taskType . ' is not valid');
                $incomingFieldArray = false;
                return;
            }
            $this->taskService->setTaskDataFromRequest($task, $decodedAndExtractedFieldArray);
            $incomingFieldArray = array_replace_recursive($incomingFieldArray, $this->taskService->getFieldsForRecord($task));
            $incomingFieldArray['parameters'] = $incomingFieldArray['parameters'] ?? [];
            $incomingFieldArray['pid'] = 0;
        } else {
            // Now let's transform our data
            $task = $this->taskRepository->findByUid((int)$id);
            $this->taskService->setTaskDataFromRequest($task, $decodedAndExtractedFieldArray);
            $incomingFieldArray = array_replace_recursive($incomingFieldArray, $this->taskService->getFieldsForRecord($task));
            if ($changedTaskType) {
                $incomingFieldArray['parameters'] = [];
                $incomingFieldArray['tasktype'] = $taskType;
            }
        }
    }

    protected function convertErrorMessagesToDataHandlerLog(DataHandler $dataHandler, string|int $taskId): void
    {
        $messages = $this->flashMessageQueue->getAllMessagesAndFlush();
        foreach ($messages as $message) {
            $messageError = match ($message->getSeverity()) {
                ContextualFeedbackSeverity::WARNING => SystemLogErrorClassification::WARNING,
                ContextualFeedbackSeverity::OK => SystemLogErrorClassification::MESSAGE,
                default => SystemLogErrorClassification::USER_ERROR,
            };
            $dataHandler->log(
                'tx_scheduler_task',
                $taskId,
                MathUtility::canBeInterpretedAsInteger($taskId) ? 2 : 1,
                null,
                $messageError,
                $message->getMessage()
            );
        }
    }

    protected function addErrorMessage(DataHandler $dataHandler, string|int $taskId, string $message, ...$args): void
    {
        $languageService = $this->getLanguageService();
        $message = $languageService->sL($message);
        $dataHandler->log(
            'tx_scheduler_task',
            $taskId,
            MathUtility::canBeInterpretedAsInteger($taskId) ? 2 : 1,
            null,
            SystemLogErrorClassification::USER_ERROR,
            $message,
            null,
            $args,
            0
        );
    }

    protected function isSubmittedTaskDataValid(DataHandler $dataHandler, string|int $taskId, array $parsedBody, string $taskType): bool
    {
        $startTime = $parsedBody['start'] ?? 0;
        $endTime = $parsedBody['end'] ?? 0;
        $frequency = $parsedBody['frequency'] ?? $parsedBody['cronCmd'] ?? '';
        $runningType = (int)($parsedBody['runningType'] ?? ($frequency ? AbstractTask::TYPE_RECURRING : AbstractTask::TYPE_SINGLE));
        $result = true;
        if ($runningType !== AbstractTask::TYPE_SINGLE && $runningType !== AbstractTask::TYPE_RECURRING) {
            $result = false;
            $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidTaskType');
        }
        if (empty($startTime)) {
            $result = false;
            $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noStartDate');
        } else {
            try {
                $startTime = $this->getTimestampFromDateString($startTime);
            } catch (InvalidDateException) {
                $result = false;
                $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidStartDate');
            }
        }
        if ($runningType === AbstractTask::TYPE_RECURRING && !empty($endTime)) {
            try {
                $endTime = $this->getTimestampFromDateString($endTime);
            } catch (InvalidDateException) {
                $result = false;
                $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidStartDate');
            }
        }
        if ($runningType === AbstractTask::TYPE_RECURRING && $endTime > 0 && $endTime < $startTime) {
            $result = false;
            $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.endDateSmallerThanStartDate');
        }
        if ($runningType === AbstractTask::TYPE_RECURRING) {
            if (empty(trim($frequency))) {
                $result = false;
                $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noFrequency');
            } elseif (!is_numeric(trim($frequency))) {
                try {
                    NormalizeCommand::normalize(trim($frequency));
                } catch (\InvalidArgumentException $e) {
                    $result = false;
                    $this->addErrorMessage($dataHandler, $taskId, 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.frequencyError', $e->getMessage(), $e->getCode());
                }
            }
        }
        $provider = $this->taskService->getAdditionalFieldProviderForTask($taskType);
        if ($provider !== null) {
            // Providers should add messages for failed validations on their own.
            $result = $result && $provider->validateAdditionalFields($parsedBody, $this->schedulerModuleController);
        }
        if ($this->commandRegistry->has($taskType)
            && (is_array($parsedBody['arguments'] ?? false) || is_array($parsedBody['options'] ?? false))
        ) {
            // If this is a registered console command, validate given arguments / options
            $command = $this->commandRegistry->get($taskType);
            foreach ($command->getDefinition()->getArguments() as $argument) {
                foreach (($parsedBody['arguments'] ?? []) as $argumentName => $argumentValue) {
                    if ($argument->getName() !== $argumentName) {
                        continue;
                    }
                    if ($argument->isRequired() && trim($argumentValue) === '') {
                        $this->addErrorMessage($dataHandler, $taskId, sprintf(
                            $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.mandatoryArgumentMissing'),
                            $argumentName
                        ));
                        $result = false;
                    }
                }
            }
            foreach ($command->getDefinition()->getOptions() as $optionDefinition) {
                $optionEnabled = $parsedBody['options'][$optionDefinition->getName()] ?? false;
                $optionValue = $parsedBody['optionValues'][$optionDefinition->getName()] ?? $optionDefinition->getDefault();
                if ($optionEnabled && $optionDefinition->isValueRequired()) {
                    if ($optionDefinition->isArray()) {
                        $testValues = is_array($optionValue) ? $optionValue : GeneralUtility::trimExplode(',', $optionValue, false);
                    } else {
                        $testValues = [$optionValue];
                    }
                    foreach ($testValues as $testValue) {
                        if ($testValue === null || trim($testValue) === '') {
                            // An option that requires a value is used with an empty value
                            $this->addErrorMessage($dataHandler, $taskId, sprintf(
                                $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.mandatoryArgumentMissing'),
                                $optionDefinition->getName()
                            ));
                            $result = false;
                        }
                    }
                }
            }
        }
        return $result;
    }

    /**
     * Convert input to DateTime and retrieve timestamp.
     *
     * @throws InvalidDateException
     */
    protected function getTimestampFromDateString(int|string $input): int
    {
        if ($input === '' || $input === 0) {
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
            throw new InvalidDateException($e->getMessage(), 1747813335);
        }
        return $value;
    }

    protected function decodeValues(array $fieldArray): array
    {
        foreach (['execution_details', 'parameters'] as $possibleEncodedValueKey) {
            $value = $fieldArray[$possibleEncodedValueKey] ?? [];
            if (is_string($value) && $value !== '') {
                try {
                    $value = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
                    $fieldArray[$possibleEncodedValueKey] = $value;
                } catch (\JsonException) {
                    // Skip failed decoding
                }
            }
            if (is_array($value) && $value !== []) {
                // Extract "values" so additional field providers can directly
                // access the values without going via the json field.
                $fieldArray = array_merge($value, $fieldArray);
            }
        }

        return $fieldArray;
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
