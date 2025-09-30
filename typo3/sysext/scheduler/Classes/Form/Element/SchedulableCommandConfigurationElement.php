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

namespace TYPO3\CMS\Scheduler\Form\Element;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Exception\InvalidOptionException;
use Symfony\Component\Console\Exception\RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

/**
 * Creates an element and shows the available configuration (arguments and options) for a schedulable commands.
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class SchedulableCommandConfigurationElement extends AbstractFormElement
{
    /**
     * Default field information enabled for this element.
     *
     * @var array
     */
    protected $defaultFieldInformation = [
        'tcaDescription' => [
            'renderType' => 'tcaDescription',
        ],
    ];

    public function __construct(
        protected readonly TaskService $taskService,
        protected readonly CommandRegistry $commandRegistry,
        protected readonly SchedulerTaskRepository $taskRepository,
        protected readonly ViewFactoryInterface $viewFactory,
    ) {}

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $selectedTaskType = $this->data['databaseRow']['tasktype'][0] ?? '';
        if ($selectedTaskType === '') {
            return $resultArray;
        }
        $parameterArray = $this->data['parameterArray'];
        $itemName = $parameterArray['itemFormElName'];

        try {
            $taskObject = $this->taskRepository->findByUid((int)$this->data['databaseRow']['uid']);
        } catch (\OutOfBoundsException) {
            // This happens for new tasks when 'uid' is set to "0" because we have a Task Type from defVals
            try {
                $taskObject = $this->taskService->createNewTask($selectedTaskType);
            } catch (InvalidTaskException) {
                // Given task type is not registered - skip this element
                return $resultArray;
            }
        }

        if ($taskObject instanceof ExecuteSchedulableCommandTask === false) {
            // Task is not an executable schedulable command task
            return $resultArray;
        }

        try {
            $command = $this->commandRegistry->get($selectedTaskType);
        } catch (CommandNotFoundException) {
            // Command not found
            return $resultArray;
        }

        $argumentFields = $this->getCommandArgumentFields($command->getDefinition(), $taskObject);
        $optionFields = $this->getCommandOptionFields($command->getDefinition(), $taskObject);

        if ($argumentFields !== [] || $optionFields !== []) {

            $fieldInformationResult = $this->renderFieldInformation();
            $fieldInformationHtml = $fieldInformationResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] =     $fieldInformationHtml;
            $html[] =     '<div class="form-wizards-wrap">';
            $html[] =         $this->renderCommandConfiguration(array_merge($argumentFields, $optionFields), $selectedTaskType, $itemName);
            $html[] =     '</div>';
            $html[] =     $this->getRunOnCliInfo($taskObject, $command);
            $html[] = '</div>';

            $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        }

        return $resultArray;
    }

    protected function getCommandArgumentFields(InputDefinition $inputDefinition, ExecuteSchedulableCommandTask $task): array
    {
        $fields = [];
        $argumentValues = $task->getArguments();
        foreach ($inputDefinition->getArguments() as $argument) {
            $name = $argument->getName();
            $defaultValue = $argument->getDefault();
            $task->addDefaultValue($name, $defaultValue);
            $value = $argumentValues[$name] ?? $defaultValue;

            if (is_array($value) && $argument->isArray()) {
                $value = implode(',', $value);
            }

            $fields['arguments'][$name] = [
                'label' => 'Argument "' . $argument->getName() . '"',
                'description' => $argument->getDescription(),
                'value' => $value,
                'required' => $argument->isRequired(),
            ];
        }

        return $fields;
    }

    protected function getCommandOptionFields(InputDefinition $inputDefinition, ExecuteSchedulableCommandTask $task): array
    {
        $fields = [];
        $enabledOptions = $task->getOptions();
        $optionValues = $task->getOptionValues();
        foreach ($inputDefinition->getOptions() as $option) {
            $name = $option->getName();
            $defaultValue = $option->getDefault();
            $task->addDefaultValue($name, $defaultValue);
            $enabled = $enabledOptions[$name] ?? false;
            $value = $optionValues[$name] ?? $defaultValue;

            if (is_array($value) && $option->isArray()) {
                $value = implode(',', $value);
            }

            $fields['options'][$name] = [
                'label' => 'Option "' . $option->getName() . '"',
                'description' => $option->getDescription(),
                'enabled' => $enabled,
                'value' => $value,
                'valueOption' => $option->isValueRequired() || $option->isValueOptional() || $option->isArray(),
            ];
        }

        return $fields;
    }

    protected function getRunOnCliInfo(ExecuteSchedulableCommandTask $taskObject, Command $command): string
    {
        $options = [];
        foreach ($taskObject->getOptions() as $name => $enabled) {
            if ($enabled) {
                $value = $taskObject->getOptionValues()[$name] ?? null;
                $options['--' . $name] = ($value === true) ? '' : $value;
            }
        }

        $parameters = array_merge($taskObject->getArguments(), $options);

        try {
            $input = new ArrayInput($parameters, $command->getDefinition());
            $arguments = $input->__toString();
            $cliCommand = '<pre class="language-bash mt-2 mb-0"><code class="language-bash">' . $command->getName() . ' ' . $arguments . '</code></pre>';
        } catch (RuntimeException|InvalidArgumentException $e) {
            $cliCommand = '<div class="badge badge-warning mt-2">' . htmlspecialchars(sprintf($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.errorParsingArguments'), $e->getMessage())) . '</div>';
        } catch (InvalidOptionException $e) {
            $cliCommand = '<div class="badge badge-warning mt-2">' . htmlspecialchars(sprintf($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.errorParsingOptions'), $e->getMessage())) . '</div>';
        }

        return '
            <div class="card mt-3 mb-0">
                <div class="card-header">
                    <div class="card-header-body">
                        <h2 class="card-title">' . htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.runOnCli')) . '</h2>
                        ' . $cliCommand . '
                    </div>
                </div>
            </div>
        ';
    }

    protected function renderCommandConfiguration(array $fields, string $taskType, string $itemName): string
    {
        return $this->viewFactory->create(
            new ViewFactoryData(
                templateRootPaths: ['EXT:scheduler/Resources/Private/Templates'],
                partialRootPaths: ['EXT:scheduler/Resources/Private/Partials'],
                layoutRootPaths: ['EXT:scheduler/Resources/Private/Layouts'],
                request: $this->data['request'],
                format: 'html',
            )
        )->assignMultiple([
            'taskType' => $taskType,
            'fields' => $fields,
            'itemName' => $itemName,
            'renderDebug' => $this->getBackendUser()->shallDisplayDebugInformation(),
        ])->render('CommandConfiguration');
    }
}
