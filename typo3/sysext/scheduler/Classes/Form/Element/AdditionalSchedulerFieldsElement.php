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

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\SchedulerManagementAction;
use TYPO3\CMS\Scheduler\Service\TaskService;

/**
 * Creates an element and shows additional fields registered via
 * AdditionalFieldProvider objects for specific types.
 *
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class AdditionalSchedulerFieldsElement extends AbstractFormElement
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
        protected readonly SchedulerTaskRepository $taskRepository,
        protected readonly ViewFactoryInterface $viewFactory,
        protected readonly SchedulerModuleController $schedulerModule,
    ) {}

    public function render(): array
    {
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        // Existing values
        $itemValue = $parameterArray['itemFormElValue'];
        $itemName = $parameterArray['itemFormElName'];
        $selectedTaskType = $this->data['databaseRow']['tasktype'][0] ?? '';

        $additionalFields = [];
        if (!$selectedTaskType) {
            // "new", we have to select the first one
            $allTaskTypes = $this->taskService->getAllTaskTypes();
            reset($allTaskTypes);
            $selectedTaskType = key($allTaskTypes);
            $this->schedulerModule->setCurrentAction(SchedulerManagementAction::ADD);
        } else {
            $this->schedulerModule->setCurrentAction(SchedulerManagementAction::EDIT);
        }
        // selected item
        $fieldProvider = $this->taskService->getAdditionalFieldProviderForTask($selectedTaskType);
        if ($fieldProvider) {
            $post = array_merge($itemValue, ['taskType' => $selectedTaskType]);
            try {
                $taskObject = $this->taskRepository->findByUid((int)$this->data['databaseRow']['uid']);
            } catch (\OutOfBoundsException $e) {
                // This happens for new tasks when 'uid' is set to "0" because we have a Task Type from defVals
                $taskObject = $this->taskService->createNewTask($selectedTaskType);
            }
            $additionalFields = $fieldProvider->getAdditionalFields($post, $taskObject, $this->schedulerModule);
            $additionalFields = $this->taskService->prepareAdditionalFields($selectedTaskType, $additionalFields);
        }

        if ($additionalFields !== []) {
            $fieldsHtml = $this->renderAdditionalFields($additionalFields, $selectedTaskType);
            $fieldsHtml = str_replace(' name="tx_scheduler[', ' name="' . $itemName . '[', $fieldsHtml);
            $fieldsHtml = str_replace(' data-params="tx_scheduler[', ' data-params="' . $itemName . '[', $fieldsHtml);

            $fieldInformationResult = $this->renderFieldInformation();
            $fieldInformationHtml = $fieldInformationResult['html'];
            $resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $fieldInformationResult, false);

            $html = [];
            $html[] = '<div class="formengine-field-item t3js-formengine-field-item">';
            $html[] = $fieldInformationHtml;
            $html[] = '<div class="form-wizards-wrap">';
            $html[] = $fieldsHtml;
            $html[] = '</div>';
            $html[] = '</div>';
            $resultArray['html'] = $this->wrapWithFieldsetAndLegend(implode(LF, $html));
        }

        return $resultArray;
    }

    protected function renderAdditionalFields(array $fields, string $taskType): string
    {
        $view = $this->viewFactory->create(
            new ViewFactoryData(
                templateRootPaths: ['EXT:scheduler/Resources/Private/Templates'],
                partialRootPaths: ['EXT:scheduler/Resources/Private/Partials'],
                layoutRootPaths: ['EXT:scheduler/Resources/Private/Layouts'],
                request: $this->data['request'],
                format: 'html',
            )
        );
        $view->assign('additionalFields', $fields);
        $view->assign('currentData', ['taskType' => $taskType]);
        return $view->render('AdditionalFields');
    }
}
