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

namespace TYPO3\CMS\Scheduler\Task;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3Fluid\Fluid\Core\ViewHelper\TagBuilder;

/**
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class ExecuteSchedulableCommandAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * @var Command[]
     */
    protected $schedulableCommands = [];

    /**
     * @var ExecuteSchedulableCommandTask|null
     */
    protected $task;

    public function __construct()
    {
        $commandRegistry = GeneralUtility::makeInstance(CommandRegistry::class);
        foreach ($commandRegistry->getSchedulableCommands() as $commandIdentifier => $command) {
            $this->schedulableCommands[$commandIdentifier] = $command;
        }
        ksort($this->schedulableCommands);
    }

    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array $txSchedulerPostData Incoming tx_scheduler POST data
     * @param ExecuteSchedulableCommandTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (BE module of the Scheduler)
     * @return array Additional fields
     * @see \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface::getAdditionalFields
     */
    public function getAdditionalFields(array &$txSchedulerPostData, $task, SchedulerModuleController $schedulerModule): array
    {
        $this->task = $task;
        if ($this->task !== null) {
            $this->task->setScheduler();
        }

        $fields = [
            'schedulableCommands' => $this->getSchedulableCommandsField($txSchedulerPostData),
        ];

        if ($this->task !== null && isset($this->schedulableCommands[$this->task->getCommandIdentifier()])) {
            $command = $this->schedulableCommands[$this->task->getCommandIdentifier()];
            $argumentFields = $this->getCommandArgumentFields($command->getDefinition());
            $fields = array_merge($fields, $argumentFields);
            $optionFields = $this->getCommandOptionFields($command->getDefinition());
            $fields = array_merge($fields, $optionFields);
            // @todo: this seems to be superfluous
            GeneralUtility::makeInstance(SchedulerTaskRepository::class)->update($this->task);
        }

        return $fields;
    }

    /**
     * Validates additional selected fields
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        if (!isset($this->schedulableCommands[$submittedData['task_executeschedulablecommand']['command']])) {
            return false;
        }

        $command = $this->schedulableCommands[$submittedData['task_executeschedulablecommand']['command']];

        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $hasErrors = false;
        foreach ($command->getDefinition()->getArguments() as $argument) {
            foreach ((array)($submittedData['task_executeschedulablecommand']['arguments'] ?? []) as $argumentName => $argumentValue) {
                /** @var string $argumentName */
                /** @var string $argumentValue */
                if ($argument->getName() !== $argumentName) {
                    continue;
                }

                if ($argument->isRequired() && trim($argumentValue) === '') {
                    // Argument is required and argument value is empty0
                    $flashMessageService->getMessageQueueByIdentifier()->addMessage(
                        new FlashMessage(
                            sprintf(
                                $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.mandatoryArgumentMissing'),
                                $argumentName
                            ),
                            $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.updateError'),
                            ContextualFeedbackSeverity::ERROR
                        )
                    );
                    $hasErrors = true;
                }
            }
        }

        foreach ($command->getDefinition()->getOptions() as $optionDefinition) {
            $optionEnabled = $submittedData['task_executeschedulablecommand']['options'][$optionDefinition->getName()] ?? false;
            $optionValue = $submittedData['task_executeschedulablecommand']['option_values'][$optionDefinition->getName()] ?? $optionDefinition->getDefault();
            if ($optionEnabled && $optionDefinition->isValueRequired()) {
                if ($optionDefinition->isArray()) {
                    $testValues = is_array($optionValue) ? $optionValue : GeneralUtility::trimExplode(',', $optionValue, false);
                } else {
                    $testValues = [$optionValue];
                }

                foreach ($testValues as $testValue) {
                    if ($testValue === null || trim($testValue) === '') {
                        // An option that requires a value is used with an empty value
                        $flashMessageService->getMessageQueueByIdentifier()->addMessage(
                            new FlashMessage(
                                sprintf(
                                    $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.mandatoryArgumentMissing'),
                                    $optionDefinition->getName()
                                ),
                                $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.updateError'),
                                ContextualFeedbackSeverity::ERROR
                            )
                        );
                        $hasErrors = true;
                    }
                }
            }
        }

        return $hasErrors === false;
    }

    /**
     * Saves additional field values
     *
     * @param ExecuteSchedulableCommandTask $task
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): bool
    {
        $command = $this->schedulableCommands[$submittedData['task_executeschedulablecommand']['command']];

        /** @var ExecuteSchedulableCommandTask $task */
        $task->setCommandIdentifier($submittedData['task_executeschedulablecommand']['command']);

        $arguments = [];
        foreach ((array)($submittedData['task_executeschedulablecommand']['arguments'] ?? []) as $argumentName => $argumentValue) {
            try {
                $argumentDefinition = $command->getDefinition()->getArgument($argumentName);
            } catch (InvalidArgumentException $e) {
                continue;
            }

            if ($argumentDefinition->isArray()) {
                $argumentValue = GeneralUtility::trimExplode(',', $argumentValue, true);
            }

            $arguments[$argumentName] = $argumentValue;
        }

        $options = [];
        $optionValues = [];
        foreach ($command->getDefinition()->getOptions() as $optionDefinition) {
            $optionEnabled = $submittedData['task_executeschedulablecommand']['options'][$optionDefinition->getName()] ?? false;
            $options[$optionDefinition->getName()] = (bool)$optionEnabled;

            if ($optionDefinition->isValueRequired() || $optionDefinition->isValueOptional() || $optionDefinition->isArray()) {
                $optionValue = $submittedData['task_executeschedulablecommand']['option_values'][$optionDefinition->getName()] ?? $optionDefinition->getDefault();
                if ($optionDefinition->isArray() && !is_array($optionValue)) {
                    // Do not remove empty array values.
                    // One empty array element indicates the existence of one occurrence of an array option (InputOption::VALUE_IS_ARRAY) without a value.
                    // Empty array elements are also required for command options like "-vvv" (can be entered as ",,").
                    $optionValue = GeneralUtility::trimExplode(',', $optionValue, false);
                }
            } else {
                // boolean flag: option value must be true if option is added or false otherwise
                $optionValue = (bool)$optionEnabled;
            }
            $optionValues[$optionDefinition->getName()] = $optionValue;
        }

        $task->setArguments($arguments);
        $task->setOptions($options);
        $task->setOptionValues($optionValues);
        return true;
    }

    /**
     * Gets a select field containing all possible schedulable commands
     */
    protected function getSchedulableCommandsField(array $txSchedulerPostData): array
    {
        $currentlySelectedCommand = $this->task !== null
            ? $this->task->getCommandIdentifier()
            // Use value from POST if given. Happens when 'add task' is re-rendered due to
            // broken / incomplete input data, and if such a task is added from the
            // "info" submodule.
            : $txSchedulerPostData['task_executeschedulablecommand']['command'] ?? '';
        $options = [];
        foreach ($this->schedulableCommands as $commandIdentifier => $command) {
            $options[$commandIdentifier] = $commandIdentifier . ': ' . $command->getDescription();
        }
        $fieldCode = $this->renderSelectField($options, $currentlySelectedCommand);
        return [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.schedulableCommandName',
            'type' => 'select',
        ];
    }

    /**
     * Gets a set of fields covering arguments which can or must be used.
     * Also registers the default values of those fields with the Task, allowing
     * them to be read upon execution.
     */
    protected function getCommandArgumentFields(InputDefinition $inputDefinition): array
    {
        $fields = [];
        $argumentValues = $this->task->getArguments();
        foreach ($inputDefinition->getArguments() as $argument) {
            $name = $argument->getName();
            $defaultValue = $argument->getDefault();
            $this->task->addDefaultValue($name, $defaultValue);
            $value = $argumentValues[$name] ?? $defaultValue;

            if (is_array($value) && $argument->isArray()) {
                $value = implode(',', $value);
            }

            $fields['arguments_' . $name] = [
                'code' => $this->renderArgumentField($argument, (string)$value),
                'label' => $this->getArgumentLabel($argument),
                'description' => $this->getArgumentDescription($argument),
                'type' => 'input',
            ];
        }

        return $fields;
    }

    /**
     * Gets a set of fields covering options which can or must be used.
     * Also registers the default values of those fields with the Task, allowing
     * them to be read upon execution.
     */
    protected function getCommandOptionFields(InputDefinition $inputDefinition): array
    {
        $fields = [];
        $enabledOptions = $this->task->getOptions();
        $optionValues = $this->task->getOptionValues();
        foreach ($inputDefinition->getOptions() as $option) {
            $name = $option->getName();
            $defaultValue = $option->getDefault();
            $this->task->addDefaultValue($name, $defaultValue);
            $enabled = $enabledOptions[$name] ?? false;
            $value = $optionValues[$name] ?? $defaultValue;

            if (is_array($value) && $option->isArray()) {
                $value = implode(',', $value);
            }

            $fields['options_' . $name] = [
                'code' => $this->renderOptionField($option, (bool)$enabled, (string)$value),
                'additionalCode' => $this->renderOptionField($option, (bool)$enabled, (string)$value),
                'label' => $this->getOptionLabel($option),
                'description' => $this->getOptionDescription($option),
                'type' => 'commandOption',
            ];
        }

        return $fields;
    }

    /**
     * Get a human-readable label for a command argument
     */
    protected function getArgumentLabel(InputArgument $argument): string
    {
        return 'Argument "' . $argument->getName() . '"';
    }

    /**
     * Get the description of a command argument
     */
    protected function getArgumentDescription(InputArgument $argument): string
    {
        return $argument->getDescription();
    }

    /**
     * Get a human-readable label for a command option
     */
    protected function getOptionLabel(InputOption $option): string
    {
        return 'Option "' . $option->getName() . '"';
    }

    /**
     * Get the description of a command option
     */
    protected function getOptionDescription(InputOption $option): string
    {
        return $option->getDescription();
    }

    protected function renderSelectField(array $options, string $selectedOptionValue): string
    {
        $selectTag = new TagBuilder();
        $selectTag->setTagName('select');
        $selectTag->forceClosingTag(true);
        $selectTag->addAttribute('id', 'schedulableCommands');
        $selectTag->addAttribute('class', 'form-select');
        $selectTag->addAttribute('name', 'tx_scheduler[task_executeschedulablecommand][command]');

        $optionsHtml = '';
        foreach ($options as $value => $label) {
            $optionTag = new TagBuilder();
            $optionTag->setTagName('option');
            $optionTag->forceClosingTag(true);
            $optionTag->addAttribute('title', (string)$label);
            $optionTag->addAttribute('value', (string)$value);
            $optionTag->setContent($label);

            if ($value === $selectedOptionValue) {
                $optionTag->addAttribute('selected', 'selected');
            }

            $optionsHtml .= $optionTag->render();
        }

        $selectTag->setContent($optionsHtml);
        return $selectTag->render();
    }

    /**
     * Renders a field for defining an argument's value
     */
    protected function renderArgumentField(InputArgument $argument, string $currentValue): string
    {
        $name = $argument->getName();
        $fieldName = 'tx_scheduler[task_executeschedulablecommand][arguments][' . $name . ']';
        $inputTagId = 'arguments_' . $name;
        $inputTag = new TagBuilder();
        $inputTag->setTagName('input');
        $inputTag->addAttribute('type', 'text');
        $inputTag->addAttribute('name', $fieldName);
        $inputTag->addAttribute('value', $currentValue);
        $inputTag->addAttribute('class', 'form-control form-control-clearable t3js-clearable');
        $inputTag->addAttribute('id', $inputTagId);

        return $inputTag->render();
    }

    /**
     * Renders a field for defining an option's value
     */
    protected function renderOptionField(InputOption $option, bool $enabled, string $currentValue): array
    {
        $name = $option->getName();

        $checkboxFieldName = 'tx_scheduler[task_executeschedulablecommand][options][' . $name . ']';
        $checkboxId = 'tx_scheduler_task_executeschedulablecommand_options_' . $name;
        $checkboxTag = new TagBuilder();
        $checkboxTag->setTagName('input');
        $checkboxTag->addAttribute('id', $checkboxId);
        $checkboxTag->addAttribute('name', $checkboxFieldName);
        $checkboxTag->addAttribute('type', 'checkbox');
        $checkboxTag->addAttribute('class', 'form-check-input');
        $checkboxTagId = 'options_' . $name;
        $checkboxTag->addAttribute('id', $checkboxTagId);
        if ($enabled) {
            $checkboxTag->addAttribute('checked', 'checked');
        }
        $html = $checkboxTag->render()
            . '<label class="form-check-label" for="' . $checkboxTagId . '">'
            . ' ' . $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.addOptionToCommand')
            . '</label>';

        $optionValueField = '';
        if ($option->isValueRequired() || $option->isValueOptional() || $option->isArray()) {
            $valueFieldName = 'tx_scheduler[task_executeschedulablecommand][option_values][' . $name . ']';
            $inputTag = new TagBuilder();
            $inputTagId = 'options_' . $name . '_values';
            $inputTag->setTagName('input');
            $inputTag->addAttribute('name', $valueFieldName);
            $inputTag->addAttribute('type', 'text');
            $inputTag->addAttribute('value', $currentValue);
            $inputTag->addAttribute('class', 'form-control form-control-clearable t3js-clearable');
            $inputTag->addAttribute('id', $inputTagId);
            $optionValueField = $inputTag->render();
        }

        return [
            'html' => $html,
            'optionValueField' => $optionValueField,
        ];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
