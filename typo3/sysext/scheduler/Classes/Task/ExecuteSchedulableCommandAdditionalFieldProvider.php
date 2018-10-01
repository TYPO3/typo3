<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Scheduler\Task;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
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
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var ExecuteSchedulableCommandTask
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
     * @param array &$taskInfo Array information of task to return
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (BE module of the Scheduler)
     * @return array Additional fields
     * @see \TYPO3\CMS\Scheduler\AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $this->task = $task;
        if ($this->task !== null) {
            $this->task->setScheduler();
        }

        $fields = [];
        $fields['action'] = $this->getActionField();

        if ($this->task !== null && isset($this->schedulableCommands[$this->task->getCommandIdentifier()])) {
            $command = $this->schedulableCommands[$this->task->getCommandIdentifier()];
            $fields['description'] = $this->getCommandDescriptionField($command->getDescription());
            $argumentFields = $this->getCommandArgumentFields($command->getDefinition());
            $fields = array_merge($fields, $argumentFields);
            $this->task->save(); // todo: this seems to be superfluous
        }

        return $fields;
    }

    /**
     * Validates additional selected fields
     *
     * @param array &$submittedData
     * @param SchedulerModuleController $schedulerModule
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        if (!isset($this->schedulableCommands[$submittedData['task_executeschedulablecommand']['command']])) {
            return false;
        }

        $command = $this->schedulableCommands[$submittedData['task_executeschedulablecommand']['command']];

        /** @var FlashMessageService $flashMessageService */
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);

        $hasErrors = false;
        foreach ($command->getDefinition()->getArguments() as $argument) {
            foreach ((array)$submittedData['task_executeschedulablecommand']['arguments'] as $argumentName => $argumentValue) {
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
                            FlashMessage::ERROR
                        )
                    );
                    $hasErrors = true;
                }
            }
        }
        return $hasErrors === false;
    }

    /**
     * Saves additional field values
     *
     * @param array $submittedData
     * @param AbstractTask $task
     * @return bool
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): bool
    {
        $command = $this->schedulableCommands[$submittedData['task_executeschedulablecommand']['command']];

        /** @var ExecuteSchedulableCommandTask $task */
        $task->setCommandIdentifier($submittedData['task_executeschedulablecommand']['command']);

        $arguments = [];
        foreach ((array)$submittedData['task_executeschedulablecommand']['arguments'] as $argumentName => $argumentValue) {
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

        $task->setArguments($arguments);
        return true;
    }

    /**
     * Get description of selected command
     *
     * @param string $description
     * @return array
     */
    protected function getCommandDescriptionField(string $description): array
    {
        return [
            'code' => '',
            'label' => '<strong>' . $description . '</strong>'
        ];
    }

    /**
     * Gets a select field containing all possible schedulable commands
     *
     * @return array
     */
    protected function getActionField(): array
    {
        $currentlySelectedCommand = $this->task !== null ? $this->task->getCommandIdentifier() : '';
        $options = [];
        foreach ($this->schedulableCommands as $commandIdentifier => $command) {
            $options[$commandIdentifier] = $commandIdentifier . ': ' . $command->getDescription();
        }
        return [
            'code' => $this->renderSelectField($options, $currentlySelectedCommand),
            'label' => $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.schedulableCommandName')
        ];
    }

    /**
     * Gets a set of fields covering arguments which can or must be used.
     * Also registers the default values of those fields with the Task, allowing
     * them to be read upon execution.
     *
     * @param InputDefinition $inputDefinition
     * @return array
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

            $fields[$name] = [
                'code' => $this->renderField($argument, (string)$value),
                'label' => $this->getArgumentLabel($argument)
            ];
        }

        return $fields;
    }

    /**
     * Get a human-readable label for a command argument
     *
     * @param InputArgument $argument
     * @return string
     */
    protected function getArgumentLabel(InputArgument $argument): string
    {
        return 'Argument: ' . $argument->getName() . '. <em>' . htmlspecialchars($argument->getDescription()) . '</em>';
    }

    /**
     * @param array $options
     * @param string $selectedOptionValue
     * @return string
     */
    protected function renderSelectField(array $options, string $selectedOptionValue): string
    {
        $selectTag = new TagBuilder();
        $selectTag->setTagName('select');
        $selectTag->forceClosingTag(true);
        $selectTag->addAttribute('class', 'form-control');
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
     *
     * @param InputArgument $argument
     * @param mixed $currentValue
     * @return string
     */
    protected function renderField(InputArgument $argument, string $currentValue): string
    {
        $name = $argument->getName();
        $fieldName = 'tx_scheduler[task_executeschedulablecommand][arguments][' . $name . ']';

        $inputTag = new TagBuilder();
        $inputTag->setTagName('input');
        $inputTag->addAttribute('type', 'text');
        $inputTag->addAttribute('name', $fieldName);
        $inputTag->addAttribute('value', $currentValue);
        $inputTag->addAttribute('class', 'form-control');

        return $inputTag->render();
    }

    /**
     * @return LanguageService
     */
    public function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
