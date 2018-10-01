<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Scheduler\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Fluid\ViewHelpers\Be\InfoboxViewHelper;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand;
use TYPO3\CMS\Scheduler\ProgressProviderInterface;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Module 'TYPO3 Scheduler administration module' for the 'scheduler' extension.
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class SchedulerModuleController
{
    use PublicMethodDeprecationTrait;
    use PublicPropertyDeprecationTrait;

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'addMessage' => 'Using SchedulerModuleController::addMessage() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicProperties = [
        'CMD' => 'Using SchedulerModuleController::$CMD is deprecated and will not be possible anymore in TYPO3 v10.0. Use SchedulerModuleController::getCurrentAction() instead.',
    ];

    /**
     * Array containing submitted data when editing or adding a task
     *
     * @var array
     */
    protected $submittedData = [];

    /**
     * Array containing all messages issued by the application logic
     * Contains the error's severity and the message itself
     *
     * @var array
     */
    protected $messages = [];

    /**
     * @var string Key of the CSH file
     */
    protected $cshKey = '_MOD_system_txschedulerM1';

    /**
     * @var Scheduler Local scheduler instance
     */
    protected $scheduler;

    /**
     * @var string
     */
    protected $backendTemplatePath = '';

    /**
     * @var StandaloneView
     */
    protected $view;

    /**
     * @var string Base URI of scheduler module
     */
    protected $moduleUri;

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The value of GET/POST var, 'CMD'
     *
     * @var mixed
     */
    protected $CMD;

    /**
     * @var Action
     */
    protected $action;

    /**
     * The module menu items array. Each key represents a key for which values can range between the items in the array of that key.
     *
     * @var array
     */
    protected $MOD_MENU = [
        'function' => []
    ];

    /**
     * Current settings for the keys of the MOD_MENU array
     *
     * @see $MOD_MENU
     * @var array
     */
    protected $MOD_SETTINGS = [];

    /**
     * Default constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:scheduler/Resources/Private/Language/locallang.xlf');
        $this->backendTemplatePath = ExtensionManagementUtility::extPath('scheduler') . 'Resources/Private/Templates/Backend/SchedulerModule/';
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->getRequest()->setControllerExtensionName('scheduler');
        $this->view->setPartialRootPaths([ExtensionManagementUtility::extPath('scheduler') . 'Resources/Private/Partials/Backend/SchedulerModule/']);
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->moduleUri = (string)$uriBuilder->buildUriFromRoute('system_txschedulerM1');
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->scheduler = GeneralUtility::makeInstance(Scheduler::class);

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Modal');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/SplitButtons');
    }

    /**
     * Injects the request object for the current request or subrequest
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->setCurrentAction(Action::cast($parsedBody['CMD'] ?? $queryParams['CMD'] ?? null));
        $this->MOD_MENU = [
            'function' => [
                'scheduler' => $this->getLanguageService()->getLL('function.scheduler'),
                'check' => $this->getLanguageService()->getLL('function.check'),
                'info' => $this->getLanguageService()->getLL('function.info')
            ]
        ];
        $settings = $parsedBody['SET'] ?? $queryParams['SET'] ?? null;
        $this->MOD_SETTINGS = BackendUtility::getModuleData($this->MOD_MENU, $settings, 'system_txschedulerM1', '', '', '');

        // Set the form
        $content = '<form name="tx_scheduler_form" id="tx_scheduler_form" method="post" action="">';

        // Prepare main content
        $content .= '<h1>' . $this->getLanguageService()->getLL('function.' . $this->MOD_SETTINGS['function']) . '</h1>';
        $previousCMD = Action::cast($parsedBody['previousCMD'] ?? $queryParams['previousCMD'] ?? null);
        $content .= $this->getModuleContent($previousCMD);
        $content .= '<div id="extraFieldsSection"></div></form><div id="extraFieldsHidden"></div>';

        $this->getButtons();
        $this->getModuleMenu();

        $this->moduleTemplate->setContent($content);
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Get the current action
     *
     * @return Action
     */
    public function getCurrentAction(): Action
    {
        return $this->action;
    }

    /**
     * Generates the action menu
     */
    protected function getModuleMenu(): void
    {
        $menu = $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->makeMenu();
        $menu->setIdentifier('SchedulerJumpMenu');
        /** @var UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        foreach ($this->MOD_MENU['function'] as $controller => $title) {
            $item = $menu
                ->makeMenuItem()
                ->setHref(
                    (string)$uriBuilder->buildUriFromRoute(
                        'system_txschedulerM1',
                        [
                            'id' => 0,
                            'SET' => [
                                'function' => $controller
                            ]
                        ]
                    )
                )
                ->setTitle($title);
            if ($controller === $this->MOD_SETTINGS['function']) {
                $item->setActive(true);
            }
            $menu->addMenuItem($item);
        }
        $this->moduleTemplate->getDocHeaderComponent()->getMenuRegistry()->addMenu($menu);
    }

    /**
     * Generate the module's content
     *
     * @param Action $previousAction
     * @return string HTML of the module's main content
     */
    protected function getModuleContent(Action $previousAction): string
    {
        $content = '';
        $sectionTitle = '';
        // Get submitted data
        $this->submittedData = GeneralUtility::_GPmerged('tx_scheduler');
        $this->submittedData['uid'] = (int)$this->submittedData['uid'];
        // If a save command was submitted, handle saving now
        if (in_array((string)$this->getCurrentAction(), [Action::SAVE, Action::SAVE_CLOSE, Action::SAVE_NEW], true)) {
            // First check the submitted data
            $result = $this->preprocessData();

            // If result is ok, proceed with saving
            if ($result) {
                $this->saveTask();

                if ($this->action->equals(Action::SAVE_CLOSE)) {
                    // Display default screen
                    $this->setCurrentAction(Action::cast(Action::LIST));
                } elseif ($this->action->equals(Action::SAVE)) {
                    // After saving a "add form", return to edit
                    $this->setCurrentAction(Action::cast(Action::EDIT));
                } elseif ($this->action->equals(Action::SAVE_NEW)) {
                    // Unset submitted data, so that empty form gets displayed
                    unset($this->submittedData);
                    // After saving a "add/edit form", return to add
                    $this->setCurrentAction(Action::cast(Action::ADD));
                } else {
                    // Return to edit form
                    $this->setCurrentAction($previousAction);
                }
            } else {
                $this->setCurrentAction($previousAction);
            }
        }

        // Handle chosen action
        switch ((string)$this->MOD_SETTINGS['function']) {
            case 'scheduler':
                $this->executeTasks();

                switch ((string)$this->getCurrentAction()) {
                    case Action::ADD:
                    case Action::EDIT:
                        try {
                            // Try adding or editing
                            $content .= $this->editTaskAction();
                            $sectionTitle = $this->getLanguageService()->getLL('action.' . $this->getCurrentAction());
                        } catch (\Exception $e) {
                            if ($e->getCode() === 1305100019) {
                                // Invalid controller class name exception
                                $this->addMessage($e->getMessage(), FlashMessage::ERROR);
                            }
                            // An exception may also happen when the task to
                            // edit could not be found. In this case revert
                            // to displaying the list of tasks
                            // It can also happen when attempting to edit a running task
                            $content .= $this->listTasksAction();
                        }
                        break;
                    case Action::DELETE:
                        $this->deleteTask();
                        $content .= $this->listTasksAction();
                        break;
                    case Action::STOP:
                        $this->stopTask();
                        $content .= $this->listTasksAction();
                        break;
                    case Action::TOGGLE_HIDDEN:
                        $this->toggleDisableAction();
                        $content .= $this->listTasksAction();
                        break;
                    case Action::SET_NEXT_EXECUTION_TIME:
                        $this->setNextExecutionTimeAction();
                        $content .= $this->listTasksAction();
                        break;
                    case Action::LIST:
                        $content .= $this->listTasksAction();
                }
                break;

            // Setup check screen
            case 'check':
                // @todo move check to the report module
                $content .= $this->checkScreenAction();
                break;

            // Information screen
            case 'info':
                $content .= $this->infoScreenAction();
                break;
        }
        // Wrap the content
        return '<h2>' . $sectionTitle . '</h2><div class="tx_scheduler_mod1">' . $content . '</div>';
    }

    /**
     * This method displays the result of a number of checks
     * on whether the Scheduler is ready to run or running properly
     *
     * @return string Further information
     */
    protected function checkScreenAction(): string
    {
        $this->view->setTemplatePathAndFilename($this->backendTemplatePath . 'CheckScreen.html');

        // Display information about last automated run, as stored in the system registry
        $registry = GeneralUtility::makeInstance(Registry::class);
        $lastRun = $registry->get('tx_scheduler', 'lastRun');
        if (!is_array($lastRun)) {
            $message = $this->getLanguageService()->getLL('msg.noLastRun');
            $severity = InfoboxViewHelper::STATE_WARNING;
        } else {
            if (empty($lastRun['end']) || empty($lastRun['start']) || empty($lastRun['type'])) {
                $message = $this->getLanguageService()->getLL('msg.incompleteLastRun');
                $severity = InfoboxViewHelper::STATE_WARNING;
            } else {
                $startDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $lastRun['start']);
                $startTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $lastRun['start']);
                $endDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $lastRun['end']);
                $endTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $lastRun['end']);
                $label = 'automatically';
                if ($lastRun['type'] === 'manual') {
                    $label = 'manually';
                }
                $type = $this->getLanguageService()->getLL('label.' . $label);
                $message = sprintf($this->getLanguageService()->getLL('msg.lastRun'), $type, $startDate, $startTime, $endDate, $endTime);
                $severity = InfoboxViewHelper::STATE_INFO;
            }
        }
        $this->view->assign('lastRunMessage', $message);
        $this->view->assign('lastRunSeverity', $severity);

        // Check if CLI script is executable or not
        $script = GeneralUtility::getFileAbsFileName('EXT:core/bin/typo3');
        $this->view->assign('script', $script);

        // Skip this check if running Windows, as rights do not work the same way on this platform
        // (i.e. the script will always appear as *not* executable)
        if (Environment::isWindows()) {
            $isExecutable = true;
        } else {
            $isExecutable = is_executable($script);
        }
        if ($isExecutable) {
            $message = $this->getLanguageService()->getLL('msg.cliScriptExecutable');
            $severity = InfoboxViewHelper::STATE_OK;
        } else {
            $message = $this->getLanguageService()->getLL('msg.cliScriptNotExecutable');
            $severity = InfoboxViewHelper::STATE_ERROR;
        }
        $this->view->assign('isExecutableMessage', $message);
        $this->view->assign('isExecutableSeverity', $severity);
        $this->view->assign('now', $this->getServerTime());

        return $this->view->render();
    }

    /**
     * This method gathers information about all available task classes and displays it
     *
     * @return string html
     */
    protected function infoScreenAction(): string
    {
        $registeredClasses = $this->getRegisteredClasses();
        // No classes available, display information message
        if (empty($registeredClasses)) {
            $this->view->setTemplatePathAndFilename($this->backendTemplatePath . 'InfoScreenNoClasses.html');
            return $this->view->render();
        }

        $this->view->setTemplatePathAndFilename($this->backendTemplatePath . 'InfoScreen.html');
        $this->view->assign('registeredClasses', $registeredClasses);

        return $this->view->render();
    }

    /**
     * Delete a task from the execution queue
     */
    protected function deleteTask(): void
    {
        try {
            // Try to fetch the task and delete it
            $task = $this->scheduler->fetchTask($this->submittedData['uid']);
            // If the task is currently running, it may not be deleted
            if ($task->isExecutionRunning()) {
                $this->addMessage($this->getLanguageService()->getLL('msg.maynotDeleteRunningTask'), FlashMessage::ERROR);
            } else {
                if ($this->scheduler->removeTask($task)) {
                    $this->getBackendUser()->writelog(4, 0, 0, 0, 'Scheduler task "%s" (UID: %s, Class: "%s") was deleted', [$task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()]);
                    $this->addMessage($this->getLanguageService()->getLL('msg.deleteSuccess'));
                } else {
                    $this->addMessage($this->getLanguageService()->getLL('msg.deleteError'), FlashMessage::ERROR);
                }
            }
        } catch (\UnexpectedValueException $e) {
            // The task could not be unserialized properly, simply update the database record
            $taskUid = (int)$this->submittedData['uid'];
            $result = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('tx_scheduler_task')
                ->update('tx_scheduler_task', ['deleted' => 1], ['uid' => $taskUid]);
            if ($result) {
                $this->addMessage($this->getLanguageService()->getLL('msg.deleteSuccess'));
            } else {
                $this->addMessage($this->getLanguageService()->getLL('msg.deleteError'), FlashMessage::ERROR);
            }
        } catch (\OutOfBoundsException $e) {
            // The task was not found, for some reason
            $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.taskNotFound'), $this->submittedData['uid']), FlashMessage::ERROR);
        }
    }

    /**
     * Clears the registered running executions from the task
     * Note that this doesn't actually stop the running script. It just unmarks
     * all executions.
     * @todo find a way to really kill the running task
     */
    protected function stopTask(): void
    {
        try {
            // Try to fetch the task and stop it
            $task = $this->scheduler->fetchTask($this->submittedData['uid']);
            if ($task->isExecutionRunning()) {
                // If the task is indeed currently running, clear marked executions
                $result = $task->unmarkAllExecutions();
                if ($result) {
                    $this->addMessage($this->getLanguageService()->getLL('msg.stopSuccess'));
                } else {
                    $this->addMessage($this->getLanguageService()->getLL('msg.stopError'), FlashMessage::ERROR);
                }
            } else {
                // The task is not running, nothing to unmark
                $this->addMessage($this->getLanguageService()->getLL('msg.maynotStopNonRunningTask'), FlashMessage::WARNING);
            }
        } catch (\Exception $e) {
            // The task was not found, for some reason
            $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.taskNotFound'), $this->submittedData['uid']), FlashMessage::ERROR);
        }
    }

    /**
     * Toggles the disabled state of the submitted task
     */
    protected function toggleDisableAction(): void
    {
        $task = $this->scheduler->fetchTask($this->submittedData['uid']);
        $task->setDisabled(!$task->isDisabled());
        // If a disabled single task is enabled again, we register it for a
        // single execution at next scheduler run.
        if ($task->getType() === AbstractTask::TYPE_SINGLE) {
            $task->registerSingleExecution(time());
        }
        $task->save();
    }

    /**
     * Sets the next execution time of the submitted task to now
     */
    protected function setNextExecutionTimeAction(): void
    {
        $task = $this->scheduler->fetchTask($this->submittedData['uid']);
        $task->setRunOnNextCronJob(true);
        $task->save();
    }

    /**
     * Return a form to add a new task or edit an existing one
     *
     * @return string HTML form to add or edit a task
     */
    protected function editTaskAction(): string
    {
        $this->view->setTemplatePathAndFilename($this->backendTemplatePath . 'EditTask.html');

        $registeredClasses = $this->getRegisteredClasses();
        $registeredTaskGroups = $this->getRegisteredTaskGroups();

        $taskInfo = [];
        $task = null;
        $process = 'edit';

        if ($this->submittedData['uid'] > 0) {
            // If editing, retrieve data for existing task
            try {
                $taskRecord = $this->scheduler->fetchTaskRecord($this->submittedData['uid']);
                // If there's a registered execution, the task should not be edited
                if (!empty($taskRecord['serialized_executions'])) {
                    $this->addMessage($this->getLanguageService()->getLL('msg.maynotEditRunningTask'), FlashMessage::ERROR);
                    throw new \LogicException('Runnings tasks cannot not be edited', 1251232849);
                }

                // Get the task object
                /** @var \TYPO3\CMS\Scheduler\Task\AbstractTask $task */
                $task = unserialize($taskRecord['serialized_task_object']);

                // Set some task information
                $taskInfo['disable'] = $taskRecord['disable'];
                $taskInfo['description'] = $taskRecord['description'];
                $taskInfo['task_group'] = $taskRecord['task_group'];

                // Check that the task object is valid
                if (isset($registeredClasses[get_class($task)]) && $this->scheduler->isValidTaskObject($task)) {
                    // The task object is valid, process with fetching current data
                    $taskInfo['class'] = get_class($task);
                    // Get execution information
                    $taskInfo['start'] = (int)$task->getExecution()->getStart();
                    $taskInfo['end'] = (int)$task->getExecution()->getEnd();
                    $taskInfo['interval'] = $task->getExecution()->getInterval();
                    $taskInfo['croncmd'] = $task->getExecution()->getCronCmd();
                    $taskInfo['multiple'] = $task->getExecution()->getMultiple();
                    if (!empty($taskInfo['interval']) || !empty($taskInfo['croncmd'])) {
                        // Guess task type from the existing information
                        // If an interval or a cron command is defined, it's a recurring task
                        $taskInfo['type'] = AbstractTask::TYPE_RECURRING;
                        $taskInfo['frequency'] = $taskInfo['interval'] ?: $taskInfo['croncmd'];
                    } else {
                        // It's not a recurring task
                        // Make sure interval and cron command are both empty
                        $taskInfo['type'] = AbstractTask::TYPE_SINGLE;
                        $taskInfo['frequency'] = '';
                        $taskInfo['end'] = 0;
                    }
                } else {
                    // The task object is not valid
                    // Issue error message
                    $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.invalidTaskClassEdit'), get_class($task)), FlashMessage::ERROR);
                    // Initialize empty values
                    $taskInfo['start'] = 0;
                    $taskInfo['end'] = 0;
                    $taskInfo['frequency'] = '';
                    $taskInfo['multiple'] = false;
                    $taskInfo['type'] = AbstractTask::TYPE_SINGLE;
                }
            } catch (\OutOfBoundsException $e) {
                // Add a message and continue throwing the exception
                $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.taskNotFound'), $this->submittedData['uid']), FlashMessage::ERROR);
                throw $e;
            }
        } else {
            // If adding a new object, set some default values
            $taskInfo['class'] = key($registeredClasses);
            $taskInfo['type'] = AbstractTask::TYPE_RECURRING;
            $taskInfo['start'] = $GLOBALS['EXEC_TIME'];
            $taskInfo['end'] = '';
            $taskInfo['frequency'] = '';
            $taskInfo['multiple'] = 0;
            $process = 'add';
        }

        // If some data was already submitted, use it to override
        // existing data
        if (!empty($this->submittedData)) {
            ArrayUtility::mergeRecursiveWithOverrule($taskInfo, $this->submittedData);
        }

        // Get the extra fields to display for each task that needs some
        $allAdditionalFields = [];
        if ($process === 'add') {
            foreach ($registeredClasses as $class => $registrationInfo) {
                if (!empty($registrationInfo['provider'])) {
                    /** @var AdditionalFieldProviderInterface $providerObject */
                    $providerObject = GeneralUtility::makeInstance($registrationInfo['provider']);
                    if ($providerObject instanceof AdditionalFieldProviderInterface) {
                        $additionalFields = $providerObject->getAdditionalFields($taskInfo, null, $this);
                        $allAdditionalFields = array_merge($allAdditionalFields, [$class => $additionalFields]);
                    }
                }
            }
        } elseif ($task !== null && !empty($registeredClasses[$taskInfo['class']]['provider'])) {
            // only try to fetch additionalFields if the task is valid
            $providerObject = GeneralUtility::makeInstance($registeredClasses[$taskInfo['class']]['provider']);
            if ($providerObject instanceof AdditionalFieldProviderInterface) {
                $allAdditionalFields[$taskInfo['class']] = $providerObject->getAdditionalFields($taskInfo, $task, $this);
            }
        }

        // Load necessary JavaScript
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Scheduler/Scheduler');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/DateTimePicker');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Scheduler/PageBrowser');
        $this->getPageRenderer()->addJsInlineCode('browse-button', '
            function setFormValueFromBrowseWin(fieldReference, elValue, elName) {
                var res = elValue.split("_");
                var element = document.getElementById(fieldReference);
                element.value = res[1];
            }
        ');

        // Start rendering the add/edit form
        $this->view->assign('uid', htmlspecialchars((string)$this->submittedData['uid']));
        $this->view->assign('cmd', htmlspecialchars((string)$this->getCurrentAction()));
        $this->view->assign('csh', $this->cshKey);
        $this->view->assign('lang', 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:');

        $table = [];

        // Disable checkbox
        $this->view->assign('task_disable', ($taskInfo['disable'] ? ' checked="checked"' : ''));
        $this->view->assign('task_disable_label', 'LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:disable');

        // Task class selector
        // On editing, don't allow changing of the task class, unless it was not valid
        if ($this->submittedData['uid'] > 0 && !empty($taskInfo['class'])) {
            $this->view->assign('task_class', $taskInfo['class']);
            $this->view->assign('task_class_title', $registeredClasses[$taskInfo['class']]['title']);
            $this->view->assign('task_class_extension', $registeredClasses[$taskInfo['class']]['extension']);
        } else {
            // Group registered classes by classname
            $groupedClasses = [];
            foreach ($registeredClasses as $class => $classInfo) {
                $groupedClasses[$classInfo['extension']][$class] = $classInfo;
            }
            ksort($groupedClasses);
            foreach ($groupedClasses as $extension => $class) {
                foreach ($groupedClasses[$extension] as $class => $classInfo) {
                    $selected = $class == $taskInfo['class'] ? ' selected="selected"' : '';
                    $groupedClasses[$extension][$class]['selected'] = $selected;
                }
            }
            $this->view->assign('groupedClasses', $groupedClasses);
        }

        // Task type selector
        $this->view->assign('task_type_selected_1', ((int)$taskInfo['type'] === AbstractTask::TYPE_SINGLE ? ' selected="selected"' : ''));
        $this->view->assign('task_type_selected_2', ((int)$taskInfo['type'] === AbstractTask::TYPE_RECURRING ? ' selected="selected"' : ''));

        // Task group selector
        foreach ($registeredTaskGroups as $key => $taskGroup) {
            $selected = $taskGroup['uid'] == $taskInfo['task_group'] ? ' selected="selected"' : '';
            $registeredTaskGroups[$key]['selected'] = $selected;
        }
        $this->view->assign('registeredTaskGroups', $registeredTaskGroups);

        // Start date/time field
        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? '%H:%M %m-%d-%Y' : '%H:%M %d-%m-%Y';
        $this->view->assign('start_value_hr', ($taskInfo['start'] > 0 ? strftime($dateFormat, $taskInfo['start']) : ''));
        $this->view->assign('start_value', $taskInfo['start']);

        // End date/time field
        // NOTE: datetime fields need a special id naming scheme
        $this->view->assign('end_value_hr', ($taskInfo['end'] > 0 ? strftime($dateFormat, $taskInfo['end']) : ''));
        $this->view->assign('end_value', $taskInfo['end']);

        // Frequency input field
        $this->view->assign('frequency', $taskInfo['frequency']);

        // Multiple execution selector
        $this->view->assign('multiple', ($taskInfo['multiple'] ? 'checked="checked"' : ''));

        // Description
        $this->view->assign('description', $taskInfo['description']);

        // Display additional fields
        $additionalFieldList = [];
        foreach ($allAdditionalFields as $class => $fields) {
            if ($class == $taskInfo['class']) {
                $additionalFieldsStyle = '';
            } else {
                $additionalFieldsStyle = ' style="display: none"';
            }
            // Add each field to the display, if there are indeed any
            if (isset($fields) && is_array($fields)) {
                foreach ($fields as $fieldID => $fieldInfo) {
                    $htmlClassName = strtolower(str_replace('\\', '-', $class));
                    $field = [];
                    $field['htmlClassName'] = $htmlClassName;
                    $field['code'] = $fieldInfo['code'];
                    $field['cshKey'] = $fieldInfo['cshKey'];
                    $field['cshLabel'] = $fieldInfo['cshLabel'];
                    $field['langLabel'] = $fieldInfo['label'];
                    $field['fieldID'] = $fieldID;
                    $field['additionalFieldsStyle'] = $additionalFieldsStyle;
                    $field['browseButton'] = $this->getBrowseButton($fieldID, $fieldInfo);
                    $additionalFieldList[] = $field;
                }
            }
        }
        $this->view->assign('additionalFields', $additionalFieldList);

        $this->view->assign('returnUrl', (string)GeneralUtility::getIndpEnv('REQUEST_URI'));
        $this->view->assign('table', implode(LF, $table));
        $this->view->assign('now', $this->getServerTime());
        $this->view->assign('frequencyOptions', (array)$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['frequencyOptions']);

        return $this->view->render();
    }

    /**
     * @param string $fieldID The id of the field witch contains the page id
     * @param array $fieldInfo The array with the field info, contains the page title shown beside the button
     * @return string HTML code for the browse button
     */
    protected function getBrowseButton($fieldID, array $fieldInfo): string
    {
        if (isset($fieldInfo['browser']) && ($fieldInfo['browser'] === 'page')) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $url = (string)$uriBuilder->buildUriFromRoute(
                'wizard_element_browser',
                ['mode' => 'db', 'bparams' => $fieldID . '|||pages|']
            );
            $title = htmlspecialchars($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.browse_db'));
            return '
                <div><a href="#" data-url=' . htmlspecialchars($url) . ' class="btn btn-default t3js-pageBrowser" title="' . $title . '">
                    <span class="t3js-icon icon icon-size-small icon-state-default icon-actions-insert-record" data-identifier="actions-insert-record">
                        <span class="icon-markup">' . $this->iconFactory->getIcon(
                'actions-insert-record',
                    Icon::SIZE_SMALL
            )->render() . '</span>
                    </span>
                </a><span id="page_' . $fieldID . '">&nbsp;' . htmlspecialchars($fieldInfo['pageTitle']) . '</span></div>';
        }
        return '';
    }

    /**
     * Execute all selected tasks
     */
    protected function executeTasks(): void
    {
        // Continue if some elements have been chosen for execution
        if (isset($this->submittedData['execute']) && !empty($this->submittedData['execute'])) {
            // Get list of registered classes
            $registeredClasses = $this->getRegisteredClasses();
            // Loop on all selected tasks
            foreach ($this->submittedData['execute'] as $uid) {
                try {
                    // Try fetching the task
                    $task = $this->scheduler->fetchTask($uid);
                    $class = get_class($task);
                    $name = $registeredClasses[$class]['title'] . ' (' . $registeredClasses[$class]['extension'] . ')';
                    if (GeneralUtility::_POST('go_cron') !== null) {
                        $task->setRunOnNextCronJob(true);
                        $task->save();
                    } else {
                        // Now try to execute it and report on outcome
                        try {
                            $result = $this->scheduler->executeTask($task);
                            if ($result) {
                                $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.executed'), $name));
                            } else {
                                $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.notExecuted'), $name), FlashMessage::ERROR);
                            }
                        } catch (\Exception $e) {
                            // An exception was thrown, display its message as an error
                            $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.executionFailed'), $name, $e->getMessage()), FlashMessage::ERROR);
                        }
                    }
                } catch (\OutOfBoundsException $e) {
                    $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.taskNotFound'), $uid), FlashMessage::ERROR);
                } catch (\UnexpectedValueException $e) {
                    $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.executionFailed'), $uid, $e->getMessage()), FlashMessage::ERROR);
                }
            }
            // Record the run in the system registry
            $this->scheduler->recordLastRun('manual');
            // Make sure to switch to list view after execution
            $this->setCurrentAction(Action::cast(Action::LIST));
        }
    }

    /**
     * Assemble display of list of scheduled tasks
     *
     * @return string Table of pending tasks
     */
    protected function listTasksAction(): string
    {
        $this->view->setTemplatePathAndFilename($this->backendTemplatePath . 'ListTasks.html');

        // Define display format for dates
        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];

        // Get list of registered task groups
        $registeredTaskGroups = $this->getRegisteredTaskGroups();

        // add an empty entry for non-grouped tasks
        // add in front of list
        array_unshift($registeredTaskGroups, ['uid' => 0, 'groupName' => '']);

        // Get all registered tasks
        // Just to get the number of entries
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task');
        $queryBuilder->getRestrictions()->removeAll();

        $result = $queryBuilder->select('t.*')
            ->addSelect(
                'g.groupName AS taskGroupName',
                'g.description AS taskGroupDescription',
                'g.deleted AS isTaskGroupDeleted'
            )
            ->from('tx_scheduler_task', 't')
            ->leftJoin(
                't',
                'tx_scheduler_task_group',
                'g',
                $queryBuilder->expr()->eq('t.task_group', $queryBuilder->quoteIdentifier('g.uid'))
            )
            ->where(
                $queryBuilder->expr()->eq('t.deleted', 0)
            )
            ->orderBy('g.sorting')
            ->execute();

        // Loop on all tasks
        $temporaryResult = [];
        while ($row = $result->fetch()) {
            if ($row['taskGroupName'] === null || $row['isTaskGroupDeleted'] === '1') {
                $row['taskGroupName'] = '';
                $row['taskGroupDescription'] = '';
                $row['task_group'] = 0;
            }
            $temporaryResult[$row['task_group']]['groupName'] = $row['taskGroupName'];
            $temporaryResult[$row['task_group']]['groupDescription'] = $row['taskGroupDescription'];
            $temporaryResult[$row['task_group']]['tasks'][] = $row;
        }

        // No tasks defined, display information message
        if (empty($temporaryResult)) {
            $this->view->setTemplatePathAndFilename($this->backendTemplatePath . 'ListTasksNoTasks.html');
            return $this->view->render();
        }

        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Scheduler/Scheduler');
        $this->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');

        $tasks = $temporaryResult;

        $registeredClasses = $this->getRegisteredClasses();
        $missingClasses = [];
        foreach ($temporaryResult as $taskIndex => $taskGroup) {
            foreach ($taskGroup['tasks'] as $recordIndex => $schedulerRecord) {
                if ((int)$schedulerRecord['disable'] === 1) {
                    $translationKey = 'enable';
                } else {
                    $translationKey = 'disable';
                }
                $tasks[$taskIndex]['tasks'][$recordIndex]['translationKey'] = $translationKey;

                // Define some default values
                $lastExecution = '-';
                $isRunning = false;
                $showAsDisabled = false;
                // Restore the serialized task and pass it a reference to the scheduler object
                /** @var \TYPO3\CMS\Scheduler\Task\AbstractTask|ProgressProviderInterface $task */
                $task = unserialize($schedulerRecord['serialized_task_object']);
                $class = get_class($task);
                if ($class === '__PHP_Incomplete_Class' && preg_match('/^O:[0-9]+:"(?P<classname>.+?)"/', $schedulerRecord['serialized_task_object'], $matches) === 1) {
                    $class = $matches['classname'];
                }
                $tasks[$taskIndex]['tasks'][$recordIndex]['class'] = $class;
                // Assemble information about last execution
                if (!empty($schedulerRecord['lastexecution_time'])) {
                    $lastExecution = date($dateFormat, (int)$schedulerRecord['lastexecution_time']);
                    if ($schedulerRecord['lastexecution_context'] === 'CLI') {
                        $context = $this->getLanguageService()->getLL('label.cron');
                    } else {
                        $context = $this->getLanguageService()->getLL('label.manual');
                    }
                    $lastExecution .= ' (' . $context . ')';
                }
                $tasks[$taskIndex]['tasks'][$recordIndex]['lastExecution'] = $lastExecution;

                if (isset($registeredClasses[get_class($task)]) && $this->scheduler->isValidTaskObject($task)) {
                    $tasks[$taskIndex]['tasks'][$recordIndex]['validClass'] = true;
                    // The task object is valid
                    $labels = [];
                    $additionalInformation = $task->getAdditionalInformation();
                    if ($task instanceof ProgressProviderInterface) {
                        $progress = round((float)$task->getProgress(), 2);
                        $tasks[$taskIndex]['tasks'][$recordIndex]['progress'] = $progress;
                    }
                    $tasks[$taskIndex]['tasks'][$recordIndex]['classTitle'] = $registeredClasses[$class]['title'];
                    $tasks[$taskIndex]['tasks'][$recordIndex]['classExtension'] = $registeredClasses[$class]['extension'];
                    $tasks[$taskIndex]['tasks'][$recordIndex]['additionalInformation'] = $additionalInformation;
                    // Check if task currently has a running execution
                    if (!empty($schedulerRecord['serialized_executions'])) {
                        $labels[] = [
                            'class' => 'success',
                            'text' => $this->getLanguageService()->getLL('status.running')
                        ];
                        $isRunning = true;
                    }
                    $tasks[$taskIndex]['tasks'][$recordIndex]['isRunning'] = $isRunning;

                    // Prepare display of next execution date
                    // If task is currently running, date is not displayed (as next hasn't been calculated yet)
                    // Also hide the date if task is disabled (the information doesn't make sense, as it will not run anyway)
                    if ($isRunning || $schedulerRecord['disable']) {
                        $nextDate = '-';
                    } else {
                        $nextDate = date($dateFormat, (int)$schedulerRecord['nextexecution']);
                        if (empty($schedulerRecord['nextexecution'])) {
                            $nextDate = $this->getLanguageService()->getLL('none');
                        } elseif ($schedulerRecord['nextexecution'] < $GLOBALS['EXEC_TIME']) {
                            $labels[] = [
                                'class' => 'warning',
                                'text' => $this->getLanguageService()->getLL('status.late'),
                                'description' => $this->getLanguageService()->getLL('status.legend.scheduled')
                            ];
                        }
                    }
                    $tasks[$taskIndex]['tasks'][$recordIndex]['nextDate'] = $nextDate;
                    // Get execution type
                    if ($task->getType() === AbstractTask::TYPE_SINGLE) {
                        $execType = $this->getLanguageService()->getLL('label.type.single');
                        $frequency = '-';
                    } else {
                        $execType = $this->getLanguageService()->getLL('label.type.recurring');
                        if ($task->getExecution()->getCronCmd() == '') {
                            $frequency = $task->getExecution()->getInterval();
                        } else {
                            $frequency = $task->getExecution()->getCronCmd();
                        }
                    }
                    // Check the disable status
                    // Row is shown dimmed if task is disabled, unless it is still running
                    if ($schedulerRecord['disable'] && !$isRunning) {
                        $labels[] = [
                            'class' => 'default',
                            'text' => $this->getLanguageService()->getLL('status.disabled')
                        ];
                        $showAsDisabled = true;
                    }
                    $tasks[$taskIndex]['tasks'][$recordIndex]['execType'] = $execType;
                    $tasks[$taskIndex]['tasks'][$recordIndex]['frequency'] = $frequency;
                    // Get multiple executions setting
                    if ($task->getExecution()->getMultiple()) {
                        $multiple = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:yes');
                    } else {
                        $multiple = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:no');
                    }
                    $tasks[$taskIndex]['tasks'][$recordIndex]['multiple'] = $multiple;

                    // Check if the last run failed
                    if (!empty($schedulerRecord['lastexecution_failure'])) {
                        // Try to get the stored exception array
                        /** @var array $exceptionArray */
                        $exceptionArray = @unserialize($schedulerRecord['lastexecution_failure']);
                        // If the exception could not be unserialized, issue a default error message
                        if (!is_array($exceptionArray) || empty($exceptionArray)) {
                            $labelDescription = $this->getLanguageService()->getLL('msg.executionFailureDefault');
                        } else {
                            $labelDescription = sprintf($this->getLanguageService()->getLL('msg.executionFailureReport'), $exceptionArray['code'], $exceptionArray['message']);
                        }
                        $labels[] = [
                            'class' => 'danger',
                            'text' => $this->getLanguageService()->getLL('status.failure'),
                            'description' => $labelDescription
                        ];
                    }
                    $tasks[$taskIndex]['tasks'][$recordIndex]['labels'] = $labels;
                    if ($showAsDisabled) {
                        $tasks[$taskIndex]['tasks'][$recordIndex]['showAsDisabled'] = 'disabled';
                    }
                } else {
                    $missingClasses[] = $tasks[$taskIndex]['tasks'][$recordIndex];
                    unset($tasks[$taskIndex]['tasks'][$recordIndex]);
                }
            }
        }

        $this->view->assign('tasks', $tasks);
        $this->view->assign('missingClasses', $missingClasses);
        $this->view->assign('moduleUri', $this->moduleUri);
        $this->view->assign('now', $this->getServerTime());

        return $this->view->render();
    }

    /**
     * Generates bootstrap labels containing the label statuses
     *
     * @param array $labels
     * @return string
     */
    protected function makeStatusLabel(array $labels): string
    {
        $htmlLabels = [];
        foreach ($labels as $label) {
            if (empty($label['text'])) {
                continue;
            }
            $htmlLabels[] = '<span class="label label-' . htmlspecialchars($label['class']) . ' pull-right" title="' . htmlspecialchars($label['description']) . '">' . htmlspecialchars($label['text']) . '</span>';
        }

        return implode('&nbsp;', $htmlLabels);
    }

    /**
     * Saves a task specified in the backend form to the database
     */
    protected function saveTask(): void
    {
        // If a task is being edited fetch old task data
        if (!empty($this->submittedData['uid'])) {
            try {
                $taskRecord = $this->scheduler->fetchTaskRecord($this->submittedData['uid']);
                /** @var \TYPO3\CMS\Scheduler\Task\AbstractTask $task */
                $task = unserialize($taskRecord['serialized_task_object']);
            } catch (\OutOfBoundsException $e) {
                // If the task could not be fetched, issue an error message
                // and exit early
                $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.taskNotFound'), $this->submittedData['uid']), FlashMessage::ERROR);
                return;
            }
            // Register single execution
            if ((int)$this->submittedData['type'] === AbstractTask::TYPE_SINGLE) {
                $task->registerSingleExecution($this->submittedData['start']);
            } else {
                if (!empty($this->submittedData['croncmd'])) {
                    // Definition by cron-like syntax
                    $interval = 0;
                    $cronCmd = $this->submittedData['croncmd'];
                } else {
                    // Definition by interval
                    $interval = $this->submittedData['interval'];
                    $cronCmd = '';
                }
                // Register recurring execution
                $task->registerRecurringExecution($this->submittedData['start'], $interval, $this->submittedData['end'], $this->submittedData['multiple'], $cronCmd);
            }
            // Set disable flag
            $task->setDisabled($this->submittedData['disable']);
            // Set description
            $task->setDescription($this->submittedData['description']);
            // Set task group
            $task->setTaskGroup($this->submittedData['task_group']);
            // Save additional input values
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields'])) {
                /** @var AdditionalFieldProviderInterface $providerObject */
                $providerObject = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields']);
                if ($providerObject instanceof AdditionalFieldProviderInterface) {
                    $providerObject->saveAdditionalFields($this->submittedData, $task);
                }
            }
            // Save to database
            $result = $this->scheduler->saveTask($task);
            if ($result) {
                $this->getBackendUser()->writelog(4, 0, 0, 0, 'Scheduler task "%s" (UID: %s, Class: "%s") was updated', [$task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()]);
                $this->addMessage($this->getLanguageService()->getLL('msg.updateSuccess'));
            } else {
                $this->addMessage($this->getLanguageService()->getLL('msg.updateError'), FlashMessage::ERROR);
            }
        } else {
            // A new task is being created
            // Create an instance of chosen class
            /** @var AbstractTask $task */
            $task = GeneralUtility::makeInstance($this->submittedData['class']);
            if ((int)$this->submittedData['type'] === AbstractTask::TYPE_SINGLE) {
                // Set up single execution
                $task->registerSingleExecution($this->submittedData['start']);
            } else {
                // Set up recurring execution
                $task->registerRecurringExecution($this->submittedData['start'], $this->submittedData['interval'], $this->submittedData['end'], $this->submittedData['multiple'], $this->submittedData['croncmd']);
            }
            // Save additional input values
            if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields'])) {
                /** @var AdditionalFieldProviderInterface $providerObject */
                $providerObject = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields']);
                if ($providerObject instanceof AdditionalFieldProviderInterface) {
                    $providerObject->saveAdditionalFields($this->submittedData, $task);
                }
            }
            // Set disable flag
            $task->setDisabled($this->submittedData['disable']);
            // Set description
            $task->setDescription($this->submittedData['description']);
            // Set description
            $task->setTaskGroup($this->submittedData['task_group']);
            // Add to database
            $result = $this->scheduler->addTask($task);
            if ($result) {
                $this->getBackendUser()->writelog(4, 0, 0, 0, 'Scheduler task "%s" (UID: %s, Class: "%s") was added', [$task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()]);
                $this->addMessage($this->getLanguageService()->getLL('msg.addSuccess'));

                // set the uid of the just created task so that we
                // can continue editing after initial saving
                $this->submittedData['uid'] = $task->getTaskUid();
            } else {
                $this->addMessage($this->getLanguageService()->getLL('msg.addError'), FlashMessage::ERROR);
            }
        }
    }

    /*************************
     *
     * INPUT PROCESSING UTILITIES
     *
     *************************/
    /**
     * Checks the submitted data and performs some pre-processing on it
     *
     * @return bool true if everything was ok, false otherwise
     */
    protected function preprocessData()
    {
        $result = true;
        // Validate id
        $this->submittedData['uid'] = empty($this->submittedData['uid']) ? 0 : (int)$this->submittedData['uid'];
        // Validate selected task class
        if (!class_exists($this->submittedData['class'])) {
            $this->addMessage($this->getLanguageService()->getLL('msg.noTaskClassFound'), FlashMessage::ERROR);
        }
        // Check start date
        if (empty($this->submittedData['start'])) {
            $this->addMessage($this->getLanguageService()->getLL('msg.noStartDate'), FlashMessage::ERROR);
            $result = false;
        } elseif (is_string($this->submittedData['start']) && (!is_numeric($this->submittedData['start']))) {
            try {
                $this->submittedData['start'] = $this->convertToTimestamp($this->submittedData['start']);
            } catch (\Exception $e) {
                $this->addMessage($this->getLanguageService()->getLL('msg.invalidStartDate'), FlashMessage::ERROR);
                $result = false;
            }
        } else {
            $this->submittedData['start'] = (int)$this->submittedData['start'];
        }
        // Check end date, if recurring task
        if ((int)$this->submittedData['type'] === AbstractTask::TYPE_RECURRING && !empty($this->submittedData['end'])) {
            if (is_string($this->submittedData['end']) && (!is_numeric($this->submittedData['end']))) {
                try {
                    $this->submittedData['end'] = $this->convertToTimestamp($this->submittedData['end']);
                } catch (\Exception $e) {
                    $this->addMessage($this->getLanguageService()->getLL('msg.invalidStartDate'), FlashMessage::ERROR);
                    $result = false;
                }
            } else {
                $this->submittedData['end'] = (int)$this->submittedData['end'];
            }
            if ($this->submittedData['end'] < $this->submittedData['start']) {
                $this->addMessage(
                    $this->getLanguageService()->getLL('msg.endDateSmallerThanStartDate'),
                    FlashMessage::ERROR
                );
                $result = false;
            }
        }
        // Set default values for interval and cron command
        $this->submittedData['interval'] = 0;
        $this->submittedData['croncmd'] = '';
        // Check type and validity of frequency, if recurring
        if ((int)$this->submittedData['type'] === AbstractTask::TYPE_RECURRING) {
            $frequency = trim($this->submittedData['frequency']);
            if (empty($frequency)) {
                // Empty frequency, not valid
                $this->addMessage($this->getLanguageService()->getLL('msg.noFrequency'), FlashMessage::ERROR);
                $result = false;
            } else {
                $cronErrorCode = 0;
                $cronErrorMessage = '';
                // Try interpreting the cron command
                try {
                    NormalizeCommand::normalize($frequency);
                    $this->submittedData['croncmd'] = $frequency;
                } catch (\Exception $e) {
                    // Store the exception's result
                    $cronErrorMessage = $e->getMessage();
                    $cronErrorCode = $e->getCode();
                    // Check if the frequency is a valid number
                    // If yes, assume it is a frequency in seconds, and unset cron error code
                    if (is_numeric($frequency)) {
                        $this->submittedData['interval'] = (int)$frequency;
                        unset($cronErrorCode);
                    }
                }
                // If there's a cron error code, issue validation error message
                if (!empty($cronErrorCode)) {
                    $this->addMessage(sprintf($this->getLanguageService()->getLL('msg.frequencyError'), $cronErrorMessage, $cronErrorCode), FlashMessage::ERROR);
                    $result = false;
                }
            }
        }
        // Validate additional input fields
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields'])) {
            /** @var AdditionalFieldProviderInterface $providerObject */
            $providerObject = GeneralUtility::makeInstance($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields']);
            if ($providerObject instanceof AdditionalFieldProviderInterface) {
                // The validate method will return true if all went well, but that must not
                // override previous false values => AND the returned value with the existing one
                $result &= $providerObject->validateAdditionalFields($this->submittedData, $this);
            }
        }
        return $result;
    }

    /**
     * Convert input to DateTime and retrieve timestamp
     *
     * @param string $input
     * @return int
     */
    protected function convertToTimestamp(string $input): int
    {
        // Convert to ISO 8601 dates
        $dateTime = new \DateTime($input);
        $value = $dateTime->getTimestamp();
        if ($value !== 0) {
            $value -= date('Z', $value);
        }
        return $value;
    }

    /**
     * This method is used to add a message to the internal queue
     *
     * @param string $message The message itself
     * @param int $severity Message level (according to FlashMessage class constants)
     */
    protected function addMessage($message, $severity = FlashMessage::OK)
    {
        $this->moduleTemplate->addFlashMessage($message, '', $severity);
    }

    /**
     * This method fetches a list of all classes that have been registered with the Scheduler
     * For each item the following information is provided, as an associative array:
     *
     * ['extension']	=>	Key of the extension which provides the class
     * ['filename']		=>	Path to the file containing the class
     * ['title']		=>	String (possibly localized) containing a human-readable name for the class
     * ['provider']		=>	Name of class that implements the interface for additional fields, if necessary
     *
     * The name of the class itself is used as the key of the list array
     *
     * @return array List of registered classes
     */
    protected function getRegisteredClasses(): array
    {
        $list = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'] ?? [] as $class => $registrationInformation) {
            $title = isset($registrationInformation['title']) ? $this->getLanguageService()->sL($registrationInformation['title']) : '';
            $description = isset($registrationInformation['description']) ? $this->getLanguageService()->sL($registrationInformation['description']) : '';
            $list[$class] = [
                'extension' => $registrationInformation['extension'],
                'title' => $title,
                'description' => $description,
                'provider' => $registrationInformation['additionalFields'] ?? ''
            ];
        }
        return $list;
    }

    /**
     * This method fetches list of all group that have been registered with the Scheduler
     *
     * @return array List of registered groups
     */
    protected function getRegisteredTaskGroups(): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_scheduler_task_group');

        return $queryBuilder
            ->select('*')
            ->from('tx_scheduler_task_group')
            ->orderBy('sorting')
            ->execute()
            ->fetchAll();
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function getButtons(): void
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        // CSH
        $helpButton = $buttonBar->makeHelpButton()
            ->setModuleName('_MOD_system_txschedulerM1')
            ->setFieldName('');
        $buttonBar->addButton($helpButton);

        // Add and Reload
        if (in_array((string)$this->getCurrentAction(), [Action::LIST, Action::DELETE, Action::STOP, Action::TOGGLE_HIDDEN, Action::SET_NEXT_EXECUTION_TIME], true)) {
            $reloadButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-refresh', Icon::SIZE_SMALL))
                ->setHref($this->moduleUri);
            $buttonBar->addButton($reloadButton, ButtonBar::BUTTON_POSITION_RIGHT, 1);
            if ($this->MOD_SETTINGS['function'] === 'scheduler' && !empty($this->getRegisteredClasses())) {
                $addButton = $buttonBar->makeLinkButton()
                    ->setTitle($this->getLanguageService()->getLL('action.add'))
                    ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-add', Icon::SIZE_SMALL))
                    ->setHref($this->moduleUri . '&CMD=' . Action::ADD);
                $buttonBar->addButton($addButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            }
        }

        // Close and Save
        if (in_array((string)$this->getCurrentAction(), [Action::ADD, Action::EDIT], true)) {
            // Close
            $closeButton = $buttonBar->makeLinkButton()
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:cancel'))
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-close', Icon::SIZE_SMALL))
                ->setOnClick('document.location=' . GeneralUtility::quoteJSvalue($this->moduleUri))
                ->setHref('#');
            $buttonBar->addButton($closeButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
            // Save, SaveAndClose, SaveAndNew
            $saveButtonDropdown = $buttonBar->makeSplitButton();
            $saveButton = $buttonBar->makeInputButton()
                ->setName('CMD')
                ->setValue(Action::SAVE)
                ->setForm('tx_scheduler_form')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:save'));
            $saveButtonDropdown->addItem($saveButton);
            $saveAndNewButton = $buttonBar->makeInputButton()
                ->setName('CMD')
                ->setValue(Action::SAVE_NEW)
                ->setForm('tx_scheduler_form')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save-new', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.saveAndCreateNewTask'));
            $saveButtonDropdown->addItem($saveAndNewButton);
            $saveAndCloseButton = $buttonBar->makeInputButton()
                ->setName('CMD')
                ->setValue(Action::SAVE_CLOSE)
                ->setForm('tx_scheduler_form')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-document-save-close', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:saveAndClose'));
            $saveButtonDropdown->addItem($saveAndCloseButton);
            $buttonBar->addButton($saveButtonDropdown, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // Edit
        if ($this->getCurrentAction()->equals(Action::EDIT)) {
            $deleteButton = $buttonBar->makeInputButton()
                ->setName('CMD')
                ->setValue(Action::DELETE)
                ->setForm('tx_scheduler_form')
                ->setIcon($this->moduleTemplate->getIconFactory()->getIcon('actions-edit-delete', Icon::SIZE_SMALL))
                ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:delete'));
            $buttonBar->addButton($deleteButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
        }

        // Shortcut
        $shortcutButton = $buttonBar->makeShortcutButton()
            ->setModuleName('system_txschedulerM1')
            ->setDisplayName($this->MOD_MENU['function'][$this->MOD_SETTINGS['function']])
            ->setSetVariables(['function']);
        $buttonBar->addButton($shortcutButton);
    }

    /**
     * Set the current action
     *
     * @param Action $action
     */
    protected function setCurrentAction(Action $action): void
    {
        $this->action = $action;
        // @deprecated since TYPO3 v9, will be removed with TYPO3 v10.0
        $this->CMD = (string)$action;
    }

    /**
     * @return string
     */
    protected function getServerTime(): string
    {
        $dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ' T (e';
        return date($dateFormat) . ', GMT ' . date('P') . ')';
    }

    /**
     * Returns the Language Service
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the global BackendUserAuthentication object.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return PageRenderer
     */
    protected function getPageRenderer(): PageRenderer
    {
        return GeneralUtility::makeInstance(PageRenderer::class);
    }
}
