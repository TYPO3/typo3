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

namespace TYPO3\CMS\Scheduler\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\Controller as BackendController;
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\GenericButton;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\HiddenRestriction;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SysLog\Action\Database as SystemLogDatabaseAction;
use TYPO3\CMS\Core\SysLog\Error as SystemLogErrorClassification;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Exception\InvalidDateException;
use TYPO3\CMS\Scheduler\Exception\InvalidTaskException;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Service\TaskService;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\CMS\Scheduler\Validation\Validator\TaskValidator;

/**
 * Scheduler backend module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[BackendController]
class SchedulerModuleController
{
    protected Action $currentAction;

    public function __construct(
        protected readonly Scheduler $scheduler,
        protected readonly TaskSerializer $taskSerializer,
        protected readonly SchedulerTaskRepository $taskRepository,
        protected readonly IconFactory $iconFactory,
        protected readonly UriBuilder $uriBuilder,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly Context $context,
        protected readonly TaskService $taskService,
    ) {}

    /**
     * Entry dispatcher method.
     *
     * There are two arguments involved regarding main module routing:
     * * 'action': add, edit, delete, toggleHidden, ...
     * * 'CMD': "save", "close", "new" when adding / editing a task.
     *          A better naming would be "nextAction", but the split button ModuleTemplate and
     *          DocumentSaveActions.ts can not cope with a renaming here and need "CMD".
     */
    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $view = $this->moduleTemplateFactory->create($request);
        $view->assign('dateFormat', [
            'day' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?? 'd-m-y',
            'time' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] ?? 'H:i',
        ]);

        $backendUser = $this->getBackendUser();
        $moduleData = $request->getAttribute('moduleData');

        // Simple actions from list view.
        if (!empty($parsedBody['action']['toggleHidden'])) {
            $this->toggleDisabledFlag($view, (int)$parsedBody['action']['toggleHidden']);
            return $this->renderListTasksView($view, $moduleData);
        }
        if (!empty($queryParams['action']['stop'])) {
            // @todo: Same as above.
            $this->stopTask($view, (int)$queryParams['action']['stop']);
            return $this->renderListTasksView($view, $moduleData);
        }
        if (!empty($parsedBody['execute'])) {
            $this->executeTasks($view, (string)$parsedBody['execute']);
            return $this->renderListTasksView($view, $moduleData);
        }
        if (!empty($parsedBody['scheduleCron'])) {
            $this->scheduleCrons($view, (string)$parsedBody['scheduleCron']);
            return $this->renderListTasksView($view, $moduleData);
        }

        if (!empty($parsedBody['action']['group']['uid'])) {
            $this->groupDisable((int)$parsedBody['action']['group']['uid'], (int)($parsedBody['action']['group']['hidden'] ?? 0));
            return $this->renderListTasksView($view, $moduleData);
        }

        if (!empty($parsedBody['action']['delete'])) {
            $this->deleteTask($view, (int)$parsedBody['action']['delete']);
            return $this->renderListTasksView($view, $moduleData);
        }

        if (!empty($parsedBody['action']['groupRemove'])) {
            $rows = $this->groupRemove((int)$parsedBody['action']['groupRemove']);
            if ($rows > 0) {
                $view->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.group.deleted'));
            } else {
                $view->addFlashMessage($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.group.delete.failed'), '', ContextualFeedbackSeverity::WARNING);
            }

            return $this->renderListTasksView($view, $moduleData);
        }

        if (($parsedBody['action'] ?? '') === Action::ADD
            && in_array($parsedBody['CMD'] ?? '', ['save', 'saveclose', 'close'], true)
        ) {
            // Received data for adding a new task - validate, persist, render requested 'next' action.
            $isTaskDataValid = $this->isSubmittedTaskDataValid($view, $request, true);
            if (!$isTaskDataValid) {
                return $this->renderAddTaskFormView($view, $request);
            }
            $newTaskUid = $this->createTask($view, $request);
            if ($parsedBody['CMD'] === 'close') {
                return $this->renderListTasksView($view, $moduleData);
            }
            if ($parsedBody['CMD'] === 'saveclose') {
                return $this->renderListTasksView($view, $moduleData);
            }
            if ($parsedBody['CMD'] === 'save') {
                return $this->renderEditTaskFormView($view, $request, $newTaskUid);
            }
        }

        if (($parsedBody['action'] ?? '') === Action::EDIT
            && in_array($parsedBody['CMD'] ?? '', ['save', 'close', 'saveclose', 'new'], true)
        ) {
            // Received data for updating existing task - validate, persist, render requested 'next' action.
            $isTaskDataValid = $this->isSubmittedTaskDataValid($view, $request, false);
            if (!$isTaskDataValid) {
                return $this->renderEditTaskFormView($view, $request);
            }
            $this->updateTask($view, $request);
            if ($parsedBody['CMD'] === 'new') {
                return $this->renderAddTaskFormView($view, $request);
            }
            if ($parsedBody['CMD'] === 'close') {
                return $this->renderListTasksView($view, $moduleData);
            }
            if ($parsedBody['CMD'] === 'saveclose') {
                return $this->renderListTasksView($view, $moduleData);
            }
            if ($parsedBody['CMD'] === 'save') {
                return $this->renderEditTaskFormView($view, $request);
            }
        }

        // Add new task form / edit existing task form.
        if (($queryParams['action'] ?? '') === Action::ADD) {
            return $this->renderAddTaskFormView($view, $request);
        }
        if (($queryParams['action'] ?? '') === Action::EDIT) {
            return $this->renderEditTaskFormView($view, $request);
        }

        // Render list if no other action kicked in.
        return $this->renderListTasksView($view, $moduleData);
    }

    /**
     * This is (unfortunately) used by additional field providers to distinct between "create new task" and "edit task".
     */
    public function getCurrentAction(): Action
    {
        return $this->currentAction;
    }

    /**
     * Set a task to deleted.
     */
    protected function deleteTask(ModuleTemplate $view, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        if ($taskUid <= 0) {
            throw new \RuntimeException('Expecting a valid task uid', 1641670374);
        }
        try {
            // Try to fetch the task and delete it
            $task = $this->taskRepository->findByUid($taskUid);
            if ($this->taskRepository->isTaskMarkedAsRunning($task)) {
                // If the task is currently running, it may not be deleted
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.canNotDeleteRunningTask'), ContextualFeedbackSeverity::ERROR);
            } else {
                if ($this->taskRepository->remove($task)) {
                    $backendUser->writelog(
                        SystemLogType::EXTENSION,
                        SystemLogDatabaseAction::DELETE,
                        SystemLogErrorClassification::MESSAGE,
                        0,
                        'Scheduler task "%s" (UID: %s, Class: "%s") was deleted',
                        [$task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()]
                    );
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteSuccess'));
                } else {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteError'));
                }
            }
        } catch (\UnexpectedValueException $e) {
            // The task could not be unserialized, simply update the database record setting it to deleted
            $result = $this->taskRepository->remove($taskUid);
            if ($result) {
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteSuccess'));
            } else {
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.deleteError'), ContextualFeedbackSeverity::ERROR);
            }
        } catch (\OutOfBoundsException $e) {
            // The task was not found, for some reason
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * Clears the registered running executions from the task.
     * Note this doesn't actually stop the running script. It just unmark execution.
     * @todo find a way to really kill the running task.
     */
    protected function stopTask(ModuleTemplate $view, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        if (!$taskUid > 0) {
            throw new \RuntimeException('Expecting a valid task uid', 1641670375);
        }
        try {
            // Try to fetch the task and stop it
            $task = $this->taskRepository->findByUid($taskUid);
            if ($this->taskRepository->isTaskMarkedAsRunning($task)) {
                // If the task is indeed currently running, clear marked executions
                $result = $this->taskRepository->removeAllRegisteredExecutionsForTask($task);
                if ($result) {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.stopSuccess'));
                } else {
                    $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.stopError'), ContextualFeedbackSeverity::ERROR);
                }
            } else {
                // The task is not running, nothing to unmark
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.maynotStopNonRunningTask'), ContextualFeedbackSeverity::WARNING);
            }
        } catch (\OutOfBoundsException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
        } catch (\UnexpectedValueException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.stopTaskFailed'), $taskUid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * Toggle the disabled state of a task and register for next execution if task is of type "single execution".
     */
    protected function toggleDisabledFlag(ModuleTemplate $view, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        if (!$taskUid > 0) {
            throw new \RuntimeException('Expecting a valid task uid to toggle disabled state', 1641670373);
        }
        try {
            $task = $this->taskRepository->findByUid($taskUid);
            // If a disabled single task is enabled again, register it for a single execution at next scheduler run.
            $isTaskQueuedForExecution = $task->getType() === AbstractTask::TYPE_SINGLE;

            // Toggle task state and add a flash message
            $taskName = $this->getHumanReadableTaskName($task);
            $isTaskDisabled = $task->isDisabled();
            if ($isTaskDisabled && $isTaskQueuedForExecution) {
                $task->setDisabled(false);
                $task->registerSingleExecution($this->context->getAspect('date')->get('timestamp'));
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskEnabledAndQueuedForExecution'), $taskName, $taskUid));
            } elseif ($isTaskDisabled) {
                $task->setDisabled(false);
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskEnabled'), $taskName, $taskUid));
            } else {
                $task->setDisabled(true);
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskDisabled'), $taskName, $taskUid));
            }
            $this->taskRepository->update($task);
        } catch (\OutOfBoundsException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
        } catch (\UnexpectedValueException $e) {
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.toggleDisableFailed'), $taskUid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
        }
    }

    /**
     * Render add task form.
     */
    protected function renderAddTaskFormView(ModuleTemplate $view, ServerRequestInterface $request): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $registeredClasses = $this->taskService->getAvailableTaskTypes();
        // Class selection can be GET - link and + button in info screen.
        $queryParams = $request->getQueryParams()['tx_scheduler'] ?? [];
        $parsedBody = $request->getParsedBody()['tx_scheduler'] ?? [];

        if ((int)($parsedBody['select_latest_group'] ?? 0) === 1) {
            $groups = array_column($this->getRegisteredTaskGroups(), 'uid');
            rsort($groups);
            $selectedTaskGroup = $groups[0] ?? 0;
        } else {
            $selectedTaskGroup = 0;
        }

        $currentData = [
            'class' => $parsedBody['class'] ?? $queryParams['class'] ?? key($registeredClasses),
            'disable' => (bool)($parsedBody['disable'] ?? false),
            'task_group' => $selectedTaskGroup,
            'type' => (int)($parsedBody['type'] ?? AbstractTask::TYPE_RECURRING),
            'start' => $parsedBody['start'] ?? $this->context->getAspect('date')->get('timestamp'),
            'end' => $parsedBody['end'] ?? 0,
            'frequency' => $parsedBody['frequency'] ?? '',
            'multiple' => (bool)($parsedBody['multiple'] ?? false),
            'description' => $parsedBody['description'] ?? '',
        ];

        // Group available tasks by extension name
        $groupedClasses = [];
        foreach ($registeredClasses as $class => $classInfo) {
            $groupedClasses[$classInfo['extension']][$class] = $classInfo;
        }
        ksort($groupedClasses);

        // Additional field provider access $this->getCurrentAction() - Init it for them
        $this->currentAction = new Action(Action::ADD);
        // Get the extra fields to display for each task that needs some.
        $additionalFields = [];
        foreach ($registeredClasses as $class => $registrationInfo) {
            if (!empty($registrationInfo['provider'])) {
                /** @var AdditionalFieldProviderInterface $providerObject */
                $providerObject = GeneralUtility::makeInstance($registrationInfo['provider']);
                if ($providerObject instanceof AdditionalFieldProviderInterface) {
                    // Additional field provider receive form data by reference. But they shouldn't pollute our array here.
                    $parseBodyForProvider = $request->getParsedBody()['tx_scheduler'] ?? [];
                    $fields = $providerObject->getAdditionalFields($parseBodyForProvider, null, $this);
                    if (is_array($fields)) {
                        $additionalFields = $this->addPreparedAdditionalFields($additionalFields, $fields, (string)$class);
                    }
                }
            }
        }

        $view->assignMultiple([
            'currentData' => $currentData,
            'groupedClasses' => $groupedClasses,
            'registeredTaskGroups' => $this->getRegisteredTaskGroups(),
            'preSelectedTaskGroup' => (int)($request->getQueryParams()['groupId'] ?? 0),
            'frequencyOptions' => (array)($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions'] ?? []),
            'additionalFields' => $additionalFields,
            // Adding a group in edit view switches to formEngine. returnUrl is needed to go back to edit view on group record close.
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
        ]);
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderCloseAndSaveButtons($view);
        $view->setTitle(
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add')
        );
        $this->addDocHeaderShortcutButton($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add'), 'add');
        return $view->renderResponse('AddTaskForm');
    }

    /**
     * Render edit task form.
     */
    protected function renderEditTaskFormView(ModuleTemplate $view, ServerRequestInterface $request, ?int $taskUid = null): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $registeredClasses = $this->taskService->getAvailableTaskTypes();
        $parsedBody = $request->getParsedBody()['tx_scheduler'] ?? [];
        $moduleData = $request->getAttribute('moduleData');
        $taskUid = (int)($taskUid ?? $request->getQueryParams()['uid'] ?? $parsedBody['uid'] ?? 0);
        if (empty($taskUid)) {
            throw new \RuntimeException('No valid task uid given to edit task', 1641720929);
        }

        try {
            $taskRecord = $this->taskRepository->findRecordByUid($taskUid);
        } catch (\OutOfBoundsException $e) {
            // Task not found - removed meanwhile?
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
            return $this->renderListTasksView($view, $moduleData);
        }

        if (!empty($taskRecord['serialized_executions'])) {
            // If there's a registered execution, the task should not be edited. May happen if a cron started the task meanwhile.
            $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.maynotEditRunningTask'), ContextualFeedbackSeverity::ERROR);
            return $this->renderListTasksView($view, $moduleData);
        }

        $task = null;
        $isInvalidTask = false;
        try {
            $task = $this->taskSerializer->deserialize($taskRecord['serialized_task_object']);
            $class = $this->taskSerializer->resolveClassName($task);
        } catch (InvalidTaskException) {
            $isInvalidTask = true;
            $class = $this->taskSerializer->extractClassName($taskRecord['serialized_task_object']);
        }

        if ($isInvalidTask || !isset($registeredClasses[$class]) || !(new TaskValidator())->isValid($task)) {
            // The task object is not valid anymore. Add flash message and go back to list view.
            $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidTaskClassEdit'), $class), ContextualFeedbackSeverity::ERROR);
            return $this->renderListTasksView($view, $moduleData);
        }

        $taskExecution = $task->getExecution();
        $taskName = $this->getHumanReadableTaskName($task);
        // If an interval or a cron command is defined, it's a recurring task
        $taskType = (int)($parsedBody['type'] ?? ((empty($taskExecution->getCronCmd()) && empty($taskExecution->getInterval())) ? AbstractTask::TYPE_SINGLE : AbstractTask::TYPE_RECURRING));

        $currentData = [
            'class' => $class,
            'taskName' => $taskName,
            'disable' => (bool)($parsedBody['disable'] ?? $task->isDisabled()),
            'task_group' => (int)($parsedBody['task_group'] ?? $task->getTaskGroup()),
            'type' => $taskType,
            'start' => $parsedBody['start'] ?? $taskExecution->getStart(),
            // End for single execution tasks is always 0
            'end' => $parsedBody['end'] ?? ($taskType === AbstractTask::TYPE_RECURRING ? $taskExecution->getEnd() : 0),
            // Find current frequency field value depending on task type and interval vs. cron command
            'frequency' => $parsedBody['frequency'] ?? ($taskType === AbstractTask::TYPE_RECURRING ? ($taskExecution->getInterval() ?: $taskExecution->getCronCmd()) : ''),
            'multiple' => !($taskType === AbstractTask::TYPE_SINGLE) && (bool)($parsedBody['multiple'] ?? $taskExecution->getMultiple()),
            'description' => $parsedBody['description'] ?? $task->getDescription(),
        ];

        // Additional field provider access $this->getCurrentAction() - Init it for them
        $this->currentAction = new Action(Action::EDIT);
        $additionalFields = [];
        if (!empty($registeredClasses[$class]['provider'])) {
            $providerObject = GeneralUtility::makeInstance($registeredClasses[$class]['provider']);
            if ($providerObject instanceof AdditionalFieldProviderInterface) {
                // Additional field provider receive form data by reference. But they shouldn't pollute our array here.
                $parseBodyForProvider = $request->getParsedBody()['tx_scheduler'] ?? [];
                $fields = $providerObject->getAdditionalFields($parseBodyForProvider, $task, $this);
                if (is_array($fields)) {
                    $additionalFields = $this->addPreparedAdditionalFields($additionalFields, $fields, (string)$class);
                }
            }
        }

        $view->assignMultiple([
            'uid' => $taskUid,
            'action' => 'edit',
            'currentData' => $currentData,
            'registeredTaskGroups' => $this->getRegisteredTaskGroups(),
            'frequencyOptions' => (array)($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions'] ?? []),
            'additionalFields' => $additionalFields,
            // Adding a group in edit view switches to formEngine. returnUrl is needed to go back to edit view on group record close.
            'returnUrl' => $request->getAttribute('normalizedParams')->getRequestUri(),
        ]);
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderCloseAndSaveButtons($view);
        $view->setTitle(
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.edit'), $taskName)
        );
        $this->addDocHeaderNewButton($view);
        $this->addDocHeaderDeleteButton($view, $taskUid);
        $this->addDocHeaderShortcutButton(
            $view,
            sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.edit'), $taskName),
            'edit',
            $taskUid
        );
        return $view->renderResponse('EditTaskForm');
    }

    /**
     * Execute a list of tasks.
     */
    protected function executeTasks(ModuleTemplate $view, string $taskUids): void
    {
        $taskUids = GeneralUtility::intExplode(',', $taskUids, true);
        if (empty($taskUids)) {
            throw new \RuntimeException('Expecting a list of task uids to execute', 1641715832);
        }
        // Loop selected tasks and execute.
        $languageService = $this->getLanguageService();
        foreach ($taskUids as $uid) {
            try {
                $task = $this->taskRepository->findByUid($uid);
                $name = $this->getHumanReadableTaskName($task);
                // Try to execute it and report result
                $result = $this->scheduler->executeTask($task);
                if ($result) {
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.executed'), $name, $uid));
                } else {
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.notExecuted'), $name, $uid), ContextualFeedbackSeverity::ERROR);
                }
                $this->scheduler->recordLastRun('manual');
            } catch (\OutOfBoundsException $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $uid), ContextualFeedbackSeverity::ERROR);
            } catch (\Exception $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.executionFailed'), $uid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
            }
        }
    }

    /**
     * Schedule selected tasks to be executed on next cron run
     */
    protected function scheduleCrons(ModuleTemplate $view, string $taskUids): void
    {
        $taskUids = GeneralUtility::intExplode(',', $taskUids, true);
        if (empty($taskUids)) {
            throw new \RuntimeException('Expecting a list of task uids to schedule', 1641715833);
        }
        // Loop selected tasks and register for next cron run.
        $languageService = $this->getLanguageService();
        foreach ($taskUids as $uid) {
            try {
                $task = $this->taskRepository->findByUid($uid);
                $name = $this->getHumanReadableTaskName($task);
                $task->setRunOnNextCronJob(true);
                if ($task->isDisabled()) {
                    $task->setDisabled(false);
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskEnabledAndQueuedForExecution'), $name, $uid));
                } else {
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskQueuedForExecution'), $name, $uid));
                }
                $this->taskRepository->update($task);
            } catch (\OutOfBoundsException $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $uid), ContextualFeedbackSeverity::ERROR);
            } catch (\UnexpectedValueException $e) {
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.schedulingFailed'), $uid, $e->getMessage()), ContextualFeedbackSeverity::ERROR);
            }
        }
    }

    /**
     * Assemble display of list of scheduled tasks
     */
    protected function renderListTasksView(ModuleTemplate $view, ModuleData $moduleData): ResponseInterface
    {
        $languageService = $this->getLanguageService();
        $data = $this->taskRepository->getGroupedTasks();
        $registeredClasses = $this->taskService->getAvailableTaskTypes();

        $groups = $data['taskGroupsWithTasks'] ?? [];
        $groups = array_map(
            static fn(int $key, array $group): array => array_merge($group, ['taskGroupCollapsed' => (bool)($moduleData->get('task-group-' . $key, false))]),
            array_keys($groups),
            $groups
        );

        $view->assignMultiple([
            'groups' => $groups,
            'groupsWithoutTasks' => $this->getGroupsWithoutTasks($groups),
            'now' => $this->context->getAspect('date')->get('timestamp'),
            'errorClasses' => $data['errorClasses'],
            'errorClassesCollapsed' => (bool)($moduleData->get('task-group-missing', false)),
        ]);
        $view->setTitle(
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang_mod.xlf:mlang_tabs_tab'),
            $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.scheduler')
        );
        $view->makeDocHeaderModuleMenu();
        $this->addDocHeaderReloadButton($view);
        if (!empty($registeredClasses)) {
            $this->addDocHeaderAddTaskButton($view);
            $this->addDocHeaderAddTaskGroupButton($view);
        }
        $this->addDocHeaderShortcutButton($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.scheduler'));
        return $view->renderResponse('ListTasks');
    }

    protected function isSubmittedTaskDataValid(ModuleTemplate $view, ServerRequestInterface $request, bool $isNewTask): bool
    {
        $languageService = $this->getLanguageService();
        $parsedBody = $request->getParsedBody()['tx_scheduler'] ?? [];
        $type = (int)($parsedBody['type'] ?? 0);
        $startTime = $parsedBody['start'] ?? 0;
        $endTime = $parsedBody['end'] ?? 0;
        $result = true;
        $taskClass = '';
        if ($isNewTask) {
            $taskClass = $parsedBody['class'] ?? '';
            if (!class_exists($taskClass)) {
                $result = false;
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noTaskClassFound'), ContextualFeedbackSeverity::ERROR);
            }
        } else {
            try {
                $taskUid = (int)($parsedBody['uid'] ?? 0);
                $task = $this->taskRepository->findByUid($taskUid);
                $taskClass = get_class($task);
            } catch (\OutOfBoundsException|\UnexpectedValueException $e) {
                $result = false;
                $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.taskNotFound'), $taskUid), ContextualFeedbackSeverity::ERROR);
            }
        }
        if ($type !== AbstractTask::TYPE_SINGLE && $type !== AbstractTask::TYPE_RECURRING) {
            $result = false;
            $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidTaskType'), ContextualFeedbackSeverity::ERROR);
        }
        if (empty($startTime)) {
            $result = false;
            $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noStartDate'), ContextualFeedbackSeverity::ERROR);
        } else {
            try {
                $startTime = $this->getTimestampFromDateString($startTime);
            } catch (InvalidDateException $e) {
                $result = false;
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidStartDate'), ContextualFeedbackSeverity::ERROR);
            }
        }
        if ($type === AbstractTask::TYPE_RECURRING && !empty($endTime)) {
            try {
                $endTime = $this->getTimestampFromDateString($endTime);
            } catch (InvalidDateException $e) {
                $result = false;
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidStartDate'), ContextualFeedbackSeverity::ERROR);
            }
        }
        if ($type === AbstractTask::TYPE_RECURRING && $endTime > 0 && $endTime < $startTime) {
            $result = false;
            $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.endDateSmallerThanStartDate'), ContextualFeedbackSeverity::ERROR);
        }
        if ($type === AbstractTask::TYPE_RECURRING) {
            if (empty(trim($parsedBody['frequency']))) {
                $result = false;
                $this->addMessage($view, $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noFrequency'), ContextualFeedbackSeverity::ERROR);
            } elseif (!is_numeric(trim($parsedBody['frequency']))) {
                try {
                    NormalizeCommand::normalize(trim($parsedBody['frequency']));
                } catch (\InvalidArgumentException $e) {
                    $result = false;
                    $this->addMessage($view, sprintf($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.frequencyError'), $e->getMessage(), $e->getCode()), ContextualFeedbackSeverity::ERROR);
                }
            }
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskClass]['additionalFields'])) {
            /** @var AdditionalFieldProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskClass]['additionalFields']);
            if ($provider instanceof AdditionalFieldProviderInterface) {
                // Providers should add messages for failed validations on their own.
                $result = $result && $provider->validateAdditionalFields($parsedBody, $this);
            }
        }
        return $result;
    }

    /**
     * Create a new task and persist. Return its new uid.
     */
    protected function createTask(ModuleTemplate $view, ServerRequestInterface $request): int
    {
        /** @var AbstractTask $task */
        $task = GeneralUtility::makeInstance($request->getParsedBody()['tx_scheduler']['class']);
        $task = $this->setTaskDataFromRequest($task, $request);
        if (!$this->taskRepository->add($task)) {
            throw new \RuntimeException('Unable to add task. Possible database error', 1641720169);
        }
        $this->getBackendUser()->writelog(
            SystemLogType::EXTENSION,
            SystemLogDatabaseAction::INSERT,
            SystemLogErrorClassification::MESSAGE,
            0,
            'Scheduler task "%s" (UID: %s, Class: "%s") was added',
            [$task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()]
        );
        $this->addMessage($view, $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.addSuccess'));
        return $task->getTaskUid();
    }

    /**
     * Update data of an existing task.
     */
    protected function updateTask(ModuleTemplate $view, ServerRequestInterface $request): void
    {
        $task = $this->taskRepository->findByUid((int)$request->getParsedBody()['tx_scheduler']['uid']);
        $task = $this->setTaskDataFromRequest($task, $request);
        $this->taskRepository->update($task);
        $this->getBackendUser()->writelog(
            SystemLogType::EXTENSION,
            SystemLogDatabaseAction::UPDATE,
            SystemLogErrorClassification::MESSAGE,
            0,
            'Scheduler task "%s" (UID: %s, Class: "%s") was updated',
            [$task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()]
        );
        $this->addMessage($view, $this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.updateSuccess'));
    }

    protected function setTaskDataFromRequest(AbstractTask $task, ServerRequestInterface $request): AbstractTask
    {
        $parsedBody = $request->getParsedBody()['tx_scheduler'];
        if ((int)$parsedBody['type'] === AbstractTask::TYPE_SINGLE) {
            $task->registerSingleExecution($this->getTimestampFromDateString($parsedBody['start']));
        } else {
            $task->registerRecurringExecution(
                $this->getTimestampFromDateString($parsedBody['start']),
                is_numeric($parsedBody['frequency']) ? (int)$parsedBody['frequency'] : 0,
                !empty($parsedBody['end'] ?? '') ? $this->getTimestampFromDateString($parsedBody['end']) : 0,
                (bool)($parsedBody['multiple'] ?? false),
                !is_numeric($parsedBody['frequency']) ? $parsedBody['frequency'] : '',
            );
        }
        $task->setDisabled($parsedBody['disable'] ?? false);
        $task->setDescription($parsedBody['description'] ?? '');
        $task->setTaskGroup((int)($parsedBody['task_group'] ?? 0));
        $taskClass = get_class($task);
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskClass]['additionalFields'])) {
            /** @var AdditionalFieldProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$taskClass]['additionalFields']);
            if ($provider instanceof AdditionalFieldProviderInterface) {
                $provider->saveAdditionalFields($parsedBody, $task);
            }
        }
        return $task;
    }

    /**
     * Convert input to DateTime and retrieve timestamp.
     *
     * @throws InvalidDateException
     */
    protected function getTimestampFromDateString(string $input): int
    {
        if (is_numeric($input)) {
            // Already looks like a timestamp
            return (int)$input;
        }
        try {
            // Convert from ISO 8601 dates
            $dateTime = new \DateTime($input);
            $value = $dateTime->getTimestamp();
            if ($value !== 0) {
                $value -= (int)date('Z', $value);
            }
        } catch (\Exception $e) {
            throw new InvalidDateException($e->getMessage(), 1641717510);
        }
        return $value;
    }

    /**
     * Fetch list of all task groups.
     */
    protected function getRegisteredTaskGroups(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);

        return $queryBuilder->select('*')
            ->from('tx_scheduler_task_group')
            ->orderBy('sorting')
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Prepared additional fields from field providers for rendering.
     */
    protected function addPreparedAdditionalFields(array $currentAdditionalFields, array $newAdditionalFields, string $class): array
    {
        foreach ($newAdditionalFields as $fieldID => $fieldInfo) {
            $currentAdditionalFields[] = [
                'class' => $class,
                'fieldID' => $fieldID,
                'htmlClassName' => strtolower(str_replace('\\', '-', $class)),
                'code' => $fieldInfo['code'] ?? '',
                'cshKey' => $fieldInfo['cshKey'] ?? '',
                'cshLabel' => $fieldInfo['cshLabel'] ?? '',
                'langLabel' => $this->getLanguageService()->sL($fieldInfo['label'] ?? ''),
                'browser' => $fieldInfo['browser'] ?? '',
                'pageTitle' => $fieldInfo['pageTitle'] ?? '',
                'pageUid' => $fieldInfo['pageUid'] ?? '',
                'type' => $fieldInfo['type'] ?? '',
                'description' => $fieldInfo['description'] ?? '',
            ];
        }
        return $currentAdditionalFields;
    }

    protected function addDocHeaderReloadButton(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $reloadButton = $buttonBar->makeLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL))
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('scheduler_manage'));
        $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
    }

    protected function addDocHeaderAddTaskButton(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $addButton = $buttonBar->makeLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.add'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL))
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('scheduler_manage', ['action' => 'add']));
        $buttonBar->addButton($addButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
    }

    private function addDocHeaderAddTaskGroupButton(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $addButton = $buttonBar->makeInputButton()
            ->setTitle($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:function.group.add'))
            ->setShowLabelText(true)
            ->setIcon($this->iconFactory->getIcon('actions-plus', Icon::SIZE_SMALL))
            ->setName('createSchedulerGroup')
            ->setValue('1')
            ->setClasses('t3js-create-group');
        $buttonBar->addButton($addButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
    }

    protected function addDocHeaderCloseAndSaveButtons(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $closeButton = $buttonBar->makeLinkButton()
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:close'))
            ->setIcon($this->iconFactory->getIcon('actions-close', Icon::SIZE_SMALL))
            ->setShowLabelText(true)
            ->setHref((string)$this->uriBuilder->buildUriFromRoute('scheduler_manage'))
            ->setClasses('t3js-scheduler-close');
        $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        $saveButton = $buttonBar->makeInputButton()
            ->setName('CMD')
            ->setValue('save')
            ->setForm('tx_scheduler_form')
            ->setIcon($this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'))
            ->setShowLabelText(true);
        $buttonBar->addButton($saveButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
    }

    protected function addDocHeaderNewButton(ModuleTemplate $moduleTemplate): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $newButton = $buttonBar->makeInputButton()
            ->setName('CMD')
            ->setValue('new')
            ->setForm('tx_scheduler_form')
            ->setIcon($this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL))
            ->setTitle($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:new'))
            ->setShowLabelText(true);
        $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 5);
    }

    protected function addDocHeaderDeleteButton(ModuleTemplate $moduleTemplate, int $taskUid): void
    {
        $languageService = $this->getLanguageService();
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $deleteButton = GeneralUtility::makeInstance(GenericButton::class)
            ->setTag('button')
            ->setClasses('btn btn-default t3js-modal-trigger')
            ->setAttributes([
                'type' => 'submit',
                'data-target-form' => 'tx_scheduler_form_delete_' . $taskUid,
                'data-severity' => 'warning',
                'data-title' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:delete'),
                'data-button-close-text' => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel'),
                'data-bs-content' => $languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.delete'),
            ])
            ->setIcon($this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL))
            ->setLabel($languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:delete'))
            ->setShowLabelText(true);
        $buttonBar->addButton($deleteButton, ButtonBar::BUTTON_POSITION_LEFT, 6);
    }

    protected function addDocHeaderShortcutButton(ModuleTemplate $moduleTemplate, string $name, string $action = '', int $taskUid = 0): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $shortcutArguments = [];
        if ($action) {
            $shortcutArguments['action'] = $action;
        }
        if ($taskUid) {
            $shortcutArguments['uid'] = $taskUid;
        }
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('scheduler_manage')
            ->setDisplayName($name)
            ->setArguments($shortcutArguments);
        $buttonBar->addButton($shortcutButton);
    }

    protected function getHumanReadableTaskName(AbstractTask $task): string
    {
        $class = get_class($task);
        $registeredClasses = $this->taskService->getAvailableTaskTypes();
        if (!array_key_exists($class, $registeredClasses)) {
            throw new \RuntimeException('Class ' . $class . ' not found in list of registered task classes', 1641658569);
        }
        return $registeredClasses[$class]['title'] . ' (' . $registeredClasses[$class]['extension'] . ')';
    }

    /**
     * Add a flash message to the flash message queue of this module.
     */
    protected function addMessage(ModuleTemplate $moduleTemplate, string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $moduleTemplate->addFlashMessage($message, '', $severity);
    }

    private function getGroupsWithoutTasks(array $taskGroupsWithTasks): array
    {
        $uidGroupsWithTasks = array_filter(array_column($taskGroupsWithTasks, 'uid'));
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        $queryBuilder->getRestrictions()->removeByType(HiddenRestriction::class);
        $resultEmptyGroups = $queryBuilder->select('*')
            ->from('tx_scheduler_task_group')
            ->orderBy('groupName');

        // Only add where statement if we have taskGroups to consider.
        if (!empty($uidGroupsWithTasks)) {
            $resultEmptyGroups->where($queryBuilder->expr()->notIn('uid', $uidGroupsWithTasks));
        }

        return $resultEmptyGroups->executeQuery()->fetchAllAssociative();
    }

    private function groupRemove(int $groupId): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        return $queryBuilder->update('tx_scheduler_task_group')
            ->where($queryBuilder->expr()->eq('uid', $groupId))
            ->set('deleted', 1)
            ->executeStatement();
    }

    private function groupDisable(int $groupId, int $hidden): void
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_scheduler_task_group');
        $queryBuilder->update('tx_scheduler_task_group')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($groupId)))
            ->set('hidden', $hidden)
            ->executeStatement();
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
