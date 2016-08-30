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

use TYPO3\CMS\Extbase\Utility\TypeHandlingUtility;

/**
 * Field provider for Extbase CommandController Scheduler task
 */
class FieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * @var \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager
     */
    protected $commandManager;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Extbase\Reflection\ReflectionService
     */
    protected $reflectionService;

    /**
     * @var \TYPO3\CMS\Extbase\Scheduler\Task
     */
    protected $task;

    /**
     * Constructor
     *
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager
     * @param \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService
     */
    public function __construct(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager = null, \TYPO3\CMS\Extbase\Mvc\Cli\CommandManager $commandManager = null, \TYPO3\CMS\Extbase\Reflection\ReflectionService $reflectionService = null)
    {
        $this->objectManager = $objectManager !== null ? $objectManager : \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\Object\ObjectManager::class);
        $this->commandManager = $commandManager !== null ? $commandManager : $this->objectManager->get(\TYPO3\CMS\Extbase\Mvc\Cli\CommandManager::class);
        $this->reflectionService = $reflectionService !== null ? $reflectionService : $this->objectManager->get(\TYPO3\CMS\Extbase\Reflection\ReflectionService::class);
    }

    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array &$taskInfo Array information of task to return
     * @param mixed $task \TYPO3\CMS\Scheduler\Task\AbstractTask or \TYPO3\CMS\Scheduler\Execution instance
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule Reference to the calling object (BE module of the Scheduler)
     * @return array Additional fields
     * @see \TYPO3\CMS\Scheduler\AdditionalFieldProvider#getAdditionalFields($taskInfo, $task, $schedulerModule)
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        $this->task = $task;
        if ($this->task !== null) {
            $this->task->setScheduler();
        }
        $fields = [];
        $fields['action'] = $this->getCommandControllerActionField();
        if ($this->task !== null && $this->task->getCommandIdentifier()) {
            $command = $this->commandManager->getCommandByIdentifier($this->task->getCommandIdentifier());
            $fields['description'] = $this->getCommandControllerActionDescriptionField();
            $argumentFields = $this->getCommandControllerActionArgumentFields($command->getArgumentDefinitions());
            $fields = array_merge($fields, $argumentFields);
            $this->task->save();
        }
        return $fields;
    }

    /**
     * Validates additional selected fields
     *
     * @param array &$submittedData
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule
     * @return bool
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $schedulerModule)
    {
        return true;
    }

    /**
     * Saves additional field values
     *
     * @param array $submittedData
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task
     * @return bool
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        $task->setCommandIdentifier($submittedData['task_extbase']['action']);
        $task->setArguments((array)$submittedData['task_extbase']['arguments']);
        return true;
    }

    /**
     * Get description of selected command
     *
     * @return string
     */
    protected function getCommandControllerActionDescriptionField()
    {
        $command = $this->commandManager->getCommandByIdentifier($this->task->getCommandIdentifier());
        return [
            'code' => '',
            'label' => '<strong>' . $command->getDescription() . '</strong>'
        ];
    }

    /**
     * Gets a select field containing all possible CommandController actions
     *
     * @return array
     */
    protected function getCommandControllerActionField()
    {
        $commands = $this->commandManager->getAvailableCommands();
        $options = [];
        foreach ($commands as $command) {
            if ($command->isInternal() === true || $command->isCliOnly() === true) {
                continue;
            }
            $className = $command->getControllerClassName();
            if (strpos($className, '\\')) {
                $classNameParts = explode('\\', $className);
                // Skip vendor and product name for core classes
                if (strpos($className, 'TYPO3\\CMS\\') === 0) {
                    $classPartsToSkip = 2;
                } else {
                    $classPartsToSkip = 1;
                }
                $classNameParts = array_slice($classNameParts, $classPartsToSkip);
                $extensionName = $classNameParts[0];
                $controllerName = $classNameParts[2];
            } else {
                $classNameParts = explode('_', $className);
                $extensionName = $classNameParts[1];
                $controllerName = $classNameParts[3];
            }
            $identifier = $command->getCommandIdentifier();
            $options[$identifier] = $extensionName . ' ' . str_replace('CommandController', '', $controllerName) . ': ' . $command->getControllerCommandName();
        }
        $name = 'action';
        $currentlySelectedCommand = $this->task !== null ? $this->task->getCommandIdentifier() : null;
        return [
            'code' => $this->renderSelectField($name, $options, $currentlySelectedCommand),
            'label' => $this->getActionLabel()
        ];
    }

    /**
     * Gets a set of fields covering arguments which must be sent to $currentControllerAction.
     * Also registers the default values of those fields with the Task, allowing
     * them to be read upon execution.
     *
     * @param array $argumentDefinitions
     * @return array
     */
    protected function getCommandControllerActionArgumentFields(array $argumentDefinitions)
    {
        $fields = [];
        $argumentValues = $this->task->getArguments();
        foreach ($argumentDefinitions as $argument) {
            $name = $argument->getName();
            $defaultValue = $this->getDefaultArgumentValue($argument);
            $this->task->addDefaultValue($name, $defaultValue);
            $value = isset($argumentValues[$name]) ? $argumentValues[$name] : $defaultValue;
            $fields[$name] = [
                'code' => $this->renderField($argument, $value),
                'label' => $this->getArgumentLabel($argument)
            ];
        }
        return $fields;
    }

    /**
     * Gets a label for $key based on either provided extension or currently
     * selected CommandController extension,´
     *
     * @param string $localLanguageKey
     * @param string $extensionName
     * @return string
     */
    protected function getLanguageLabel($localLanguageKey, $extensionName = null)
    {
        if (!$extensionName) {
            list($extensionName, $commandControllerName, $commandName) = explode(':', $this->task->getCommandIdentifier());
        }
        $label = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($localLanguageKey, $extensionName);
        return $label;
    }

    /**
     * Gets the data type required for the argument value
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument
     * @return string the argument type
     */
    protected function getArgumentType(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument)
    {
        $command = $this->commandManager->getCommandByIdentifier($this->task->getCommandIdentifier());
        $controllerClassName = $command->getControllerClassName();
        $methodName = $command->getControllerCommandName() . 'Command';
        $tags = $this->reflectionService->getMethodTagsValues($controllerClassName, $methodName);
        foreach ($tags['param'] as $tag) {
            list($argumentType, $argumentVariableName) = explode(' ', $tag);
            if (substr($argumentVariableName, 1) === $argument->getName()) {
                return $argumentType;
            }
        }
        return '';
    }

    /**
     * Get a human-readable label for a command argument
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument
     * @return string
     */
    protected function getArgumentLabel(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument)
    {
        $argumentName = $argument->getName();
        list($extensionName, $commandControllerName, $commandName) = explode(':', $this->task->getCommandIdentifier());
        $path = ['command', $commandControllerName, $commandName, 'arguments', $argumentName];
        $labelNameIndex = implode('.', $path);
        $label = $this->getLanguageLabel($labelNameIndex);
        if (!$label) {
            $label = 'Argument: ' . $argumentName;
        }
        $descriptionIndex = $labelNameIndex . '.description';
        $description = $this->getLanguageLabel($descriptionIndex);
        if ((string)$description === '') {
            $description = $argument->getDescription();
        }
        if ((string)$description !== '') {
            $label .= '. <em>' . htmlspecialchars($description) . '</em>';
        }
        return $label;
    }

    /**
     * Gets the default value of argument
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument
     * @return mixed
     */
    protected function getDefaultArgumentValue(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument)
    {
        $type = $this->getArgumentType($argument);
        $argumentName = $argument->getName();
        $command = $this->commandManager->getCommandByIdentifier($this->task->getCommandIdentifier());
        $argumentReflection = $this->reflectionService->getMethodParameters($command->getControllerClassName(), $command->getControllerCommandName() . 'Command');
        $defaultValue = $argumentReflection[$argumentName]['defaultValue'];
        if (TypeHandlingUtility::normalizeType($type) === 'boolean') {
            $defaultValue = (bool)$defaultValue ? 1 : 0;
        }
        return $defaultValue;
    }

    /**
     * Get a human-readable label for the action field
     *
     * @return string
     */
    protected function getActionLabel()
    {
        $index = 'task.action';
        $label = $this->getLanguageLabel($index, 'extbase');
        if (!$label) {
            $label = 'CommandController Command. <em>Save and reopen to define command arguments</em>';
        }
        return $label;
    }

    /**
     * Render a select field with name $name and options $options
     *
     * @param string $name
     * @param array $options
     * @param string $selectedOptionValue
     * @return string
     */
    protected function renderSelectField($name, array $options, $selectedOptionValue)
    {
        $html = [
            '<select class="form-control" name="tx_scheduler[task_extbase][' . htmlspecialchars($name) . ']">'
        ];
        foreach ($options as $optionValue => $optionLabel) {
            $selected = $optionValue === $selectedOptionValue ? ' selected="selected"' : '';
            array_push($html, '<option title="test" value="' . htmlspecialchars($optionValue) . '"' . $selected . '>' . htmlspecialchars($optionLabel) . '</option>');
        }
        array_push($html, '</select>');
        return implode(LF, $html);
    }

    /**
     * Renders a field for defining an argument's value
     *
     * @param \TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument
     * @param mixed $currentValue
     * @return string
     */
    protected function renderField(\TYPO3\CMS\Extbase\Mvc\Cli\CommandArgumentDefinition $argument, $currentValue)
    {
        $type = $this->getArgumentType($argument);
        $name = $argument->getName();
        $fieldName = 'tx_scheduler[task_extbase][arguments][' . htmlspecialchars($name) . ']';
        if (TypeHandlingUtility::normalizeType($type) === 'boolean') {
            // checkbox field for boolean values.
            $html = '<input type="hidden" name="' . $fieldName . '" value="0">';
            $html .= '<div class="checkbox"><label><input type="checkbox" name="' . $fieldName . '" value="1" ' . ((bool)$currentValue ? ' checked="checked"' : '') . '></label></div>';
        } else {
            // regular string, also the default field type
            $html = '<input class="form-control" type="text" name="' . $fieldName . '" value="' . htmlspecialchars($currentValue) . '"> ';
        }
        return $html;
    }
}
