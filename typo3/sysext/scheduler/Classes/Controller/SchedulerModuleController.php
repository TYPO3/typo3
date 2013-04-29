<?php
namespace TYPO3\CMS\Scheduler\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 François Suter <francois@typo3.org>
 *  (c) 2005-2013 Christian Jul Jensen <julle@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Module 'TYPO3 Scheduler administration module' for the 'scheduler' extension.
 *
 * @author 		François Suter <francois@typo3.org>
 * @author 		Christian Jul Jensen <julle@typo3.org>
 * @author 		Ingo Renner <ingo@typo3.org>
 */
class SchedulerModuleController extends \TYPO3\CMS\Backend\Module\BaseScriptClass {

	/**
	 * Back path to typo3 main dir
	 *
	 * @var string $backPath
	 */
	public $backPath;

	/**
	 * Array containing submitted data when editing or adding a task
	 *
	 * @var array $submittedData
	 */
	protected $submittedData = array();

	/**
	 * Array containing all messages issued by the application logic
	 * Contains the error's severity and the message itself
	 *
	 * @var array $messages
	 */
	protected $messages = array();

	/**
	 * @var string Key of the CSH file
	 */
	protected $cshKey;

	/**
	 * @var \TYPO3\CMS\Scheduler\Scheduler Local scheduler instance
	 */
	protected $scheduler;

	/**
	 * Constructor
	 *
	 * @return \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController
	 */
	public function __construct() {
		$this->backPath = $GLOBALS['BACK_PATH'];
		// Set key for CSH
		$this->cshKey = '_MOD_' . $GLOBALS['MCONF']['name'];
	}

	/**
	 * Initializes the backend module
	 *
	 * @return void
	 */
	public function init() {
		parent::init();
		// Initialize document
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->setModuleTemplate(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('scheduler') . 'mod1/mod_template.html');
		$this->doc->getPageRenderer()->addCssFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler') . 'res/tx_scheduler_be.css');
		$this->doc->backPath = $this->backPath;
		$this->doc->bodyTagId = 'typo3-mod-php';
		$this->doc->bodyTagAdditions = 'class="tx_scheduler_mod1"';
		// Create scheduler instance
		$this->scheduler = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Scheduler\\Scheduler');
	}

	/**
	 * Adds items to the ->MOD_MENU array. Used for the function menu selector.
	 *
	 * @return void
	 */
	public function menuConfig() {
		$this->MOD_MENU = array(
			'function' => array(
				'scheduler' => $GLOBALS['LANG']->getLL('function.scheduler'),
				'check' => $GLOBALS['LANG']->getLL('function.check'),
				'info' => $GLOBALS['LANG']->getLL('function.info')
			)
		);
		parent::menuConfig();
	}

	/**
	 * Main function of the module. Write the content to $this->content
	 *
	 * @return void
	 */
	public function main() {
		// Access check!
		// The page will show only if user has admin rights
		if ($GLOBALS['BE_USER']->user['admin']) {
			// Set the form
			$this->doc->form = '<form name="tx_scheduler_form" id="tx_scheduler_form" method="post" action="">';
			// JavaScript for main function menu
			$this->doc->JScode = '
				<script language="javascript" type="text/javascript">
					script_ended = 0;
					function jumpToUrl(URL) {
						document.location = URL;
					}
				</script>
			';
			$this->doc->getPageRenderer()->addInlineSetting('scheduler', 'runningIcon', \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler') . 'res/gfx/status_running.png');
			// Prepare main content
			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('function.' . $this->MOD_SETTINGS['function']));
			$this->content .= $this->doc->spacer(5);
			$this->content .= $this->getModuleContent();
		} else {
			// If no access, only display the module's title
			$this->content = $this->doc->header($GLOBALS['LANG']->getLL('title'));
			$this->content .= $this->doc->spacer(5);
		}
		// Place content inside template
		$content = $this->doc->moduleBody(array(), $this->getDocHeaderButtons(), $this->getTemplateMarkers());
		// Renders the module page
		$this->content = $this->doc->render($GLOBALS['LANG']->getLL('title'), $content);
	}

	/**
	 * Generate the module's content
	 *
	 * @return string HTML of the module's main content
	 */
	protected function getModuleContent() {
		$content = '';
		$sectionTitle = '';
		// Get submitted data
		$this->submittedData = \TYPO3\CMS\Core\Utility\GeneralUtility::_GPmerged('tx_scheduler');
		$this->submittedData['uid'] = intval($this->submittedData['uid']);
		// If a save command was submitted, handle saving now
		if ($this->CMD == 'save' || $this->CMD == 'saveclose') {
			$previousCMD = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('previousCMD');
			// First check the submitted data
			$result = $this->preprocessData();
			// If result is ok, proceed with saving
			if ($result) {
				$this->saveTask();
				if ($this->CMD == 'saveclose') {
					// Unset command, so that default screen gets displayed
					unset($this->CMD);
				} elseif ($this->CMD == 'save') {
					// After saving a "add form", return to edit
					$this->CMD = 'edit';
				} else {
					// Return to edit form
					$this->CMD = $previousCMD;
				}
			} else {
				$this->CMD = $previousCMD;
			}
		}
		// Handle chosen action
		switch ((string) $this->MOD_SETTINGS['function']) {
		case 'scheduler':
			// Scheduler's main screen
			$this->executeTasks();
			// Execute chosen action
			switch ($this->CMD) {
			case 'add':

			case 'edit':
				try {
					// Try adding or editing
					$content .= $this->editTask();
					$sectionTitle = $GLOBALS['LANG']->getLL('action.' . $this->CMD);
				} catch (\Exception $e) {
					// An exception may happen when the task to
					// edit could not be found. In this case revert
					// to displaying the list of tasks
					// It can also happen when attempting to edit a running task
					$content .= $this->listTasks();
				}
				break;
			case 'delete':
				$this->deleteTask();
				$content .= $this->listTasks();
				break;
			case 'stop':
				$this->stopTask();
				$content .= $this->listTasks();
				break;
			case 'list':

			default:
				$content .= $this->listTasks();
				break;
			}
			break;
		case 'check':
			// Setup check screen
			// TODO: move check to the report module
			$content .= $this->displayCheckScreen();
			break;
		case 'info':
			// Information screen
			$content .= $this->displayInfoScreen();
			break;
		}
		// Wrap the content in a section
		return $this->doc->section($sectionTitle, '<div class="tx_scheduler_mod1">' . $content . '</div>', 0, 1);
	}

	/**
	 * This method actually prints out the module's HTML content
	 *
	 * @return void
	 */
	public function render() {
		echo $this->content;
	}

	/**
	 * This method checks the status of the '_cli_scheduler' user
	 * It will differentiate between a non-existing user and an existing,
	 * but disabled user (as per enable fields)
	 *
	 * @return integer	-1	if user doesn't exist
	 */
	protected function checkSchedulerUser() {
		$schedulerUserStatus = -1;
		// Assemble base WHERE clause
		$where = 'username = \'_cli_scheduler\' AND admin = 0' . \TYPO3\CMS\Backend\Utility\BackendUtility::deleteClause('be_users');
		// Check if user exists at all
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('1', 'be_users', $where);
		if ($GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$schedulerUserStatus = 0;
			// Check if user exists and is enabled
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('1', 'be_users', $where . \TYPO3\CMS\Backend\Utility\BackendUtility::BEenableFields('be_users'));
			if ($GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				$schedulerUserStatus = 1;
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $schedulerUserStatus;
	}

	/**
	 * This method creates the "cli_scheduler" BE user if it doesn't exist
	 *
	 * @return void
	 */
	protected function createSchedulerUser() {
		// Check _cli_scheduler user status
		$checkUser = $this->checkSchedulerUser();
		// Prepare default message
		$message = $GLOBALS['LANG']->getLL('msg.userExists');
		$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING;
		// If the user does not exist, try creating it
		if ($checkUser == -1) {
			// Prepare necessary data for _cli_scheduler user creation
			$password = md5(uniqid('scheduler', TRUE));
			$data = array('be_users' => array('NEW' => array('username' => '_cli_scheduler', 'password' => $password, 'pid' => 0)));
			/** @var $tcemain \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tcemain = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tcemain->stripslashes_values = 0;
			$tcemain->start($data, array());
			$tcemain->process_datamap();
			// Check if a new uid was indeed generated (i.e. a new record was created)
			// (counting TCEmain errors doesn't work as some failures don't report errors)
			$numberOfNewIDs = count($tcemain->substNEWwithIDs);
			if ($numberOfNewIDs == 1) {
				$message = $GLOBALS['LANG']->getLL('msg.userCreated');
				$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
			} else {
				$message = $GLOBALS['LANG']->getLL('msg.userNotCreated');
				$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
			}
		}
		$this->addMessage($message, $severity);
	}

	/**
	 * This method displays the result of a number of checks
	 * on whether the Scheduler is ready to run or running properly
	 *
	 * @return string Further information
	 */
	protected function displayCheckScreen() {
		$message = '';
		$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
		// First, check if _cli_scheduler user creation was requested
		if ($this->CMD == 'user') {
			$this->createSchedulerUser();
		}
		// Start generating the content
		$content = $GLOBALS['LANG']->getLL('msg.schedulerSetupCheck');
		// Display information about last automated run, as stored in the system registry
		/** @var $registry \TYPO3\CMS\Core\Registry */
		$registry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Registry');
		$lastRun = $registry->get('tx_scheduler', 'lastRun');
		if (!is_array($lastRun)) {
			$message = $GLOBALS['LANG']->getLL('msg.noLastRun');
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING;
		} else {
			if (empty($lastRun['end']) || empty($lastRun['start']) || empty($lastRun['type'])) {
				$message = $GLOBALS['LANG']->getLL('msg.incompleteLastRun');
				$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING;
			} else {
				$startDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $lastRun['start']);
				$startTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $lastRun['start']);
				$endDate = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'], $lastRun['end']);
				$endTime = date($GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'], $lastRun['end']);
				$label = 'automatically';
				if ($lastRun['type'] == 'manual') {
					$label = 'manually';
				}
				$type = $GLOBALS['LANG']->getLL('label.' . $label);
				$message = sprintf($GLOBALS['LANG']->getLL('msg.lastRun'), $type, $startDate, $startTime, $endDate, $endTime);
				$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::INFO;
			}
		}
		/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, '', $severity);
		$content .= '<div class="info-block">';
		$content .= '<h3>' . $GLOBALS['LANG']->getLL('hdg.lastRun') . '</h3>';
		$content .= $flashMessage->render();
		$content .= '</div>';
		// Check CLI user
		$content .= '<div class="info-block">';
		$content .= '<h3>' . $GLOBALS['LANG']->getLL('hdg.schedulerUser') . '</h3>';
		$content .= '<p>' . $GLOBALS['LANG']->getLL('msg.schedulerUser') . '</p>';
		$checkUser = $this->checkSchedulerUser();
		if ($checkUser == -1) {
			$link = $GLOBALS['MCONF']['_'] . '&SET[function]=check&CMD=user';
			$message = sprintf($GLOBALS['LANG']->getLL('msg.schedulerUserMissing'), htmlspecialchars($link));
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
		} elseif ($checkUser == 0) {
			$message = $GLOBALS['LANG']->getLL('msg.schedulerUserFoundButDisabled');
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING;
		} else {
			$message = $GLOBALS['LANG']->getLL('msg.schedulerUserFound');
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
		}
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, '', $severity);
		$content .= $flashMessage->render() . '</div>';
		// Check if CLI script is executable or not
		$script = PATH_typo3 . 'cli_dispatch.phpsh';
		$isExecutable = FALSE;
		// Skip this check if running Windows, as rights do not work the same way on this platform
		// (i.e. the script will always appear as *not* executable)
		if (TYPO3_OS === 'WIN') {
			$isExecutable = TRUE;
		} else {
			$isExecutable = is_executable($script);
		}
		$content .= '<div class="info-block">';
		$content .= '<h3>' . $GLOBALS['LANG']->getLL('hdg.cliScript') . '</h3>';
		$content .= '<p>' . sprintf($GLOBALS['LANG']->getLL('msg.cliScript'), $script) . '</p>';
		if ($isExecutable) {
			$message = $GLOBALS['LANG']->getLL('msg.cliScriptExecutable');
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK;
		} else {
			$message = $GLOBALS['LANG']->getLL('msg.cliScriptNotExecutable');
			$severity = \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR;
		}
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, '', $severity);
		$content .= $flashMessage->render() . '</div>';
		return $content;
	}

	/**
	 * This method gathers information about all available task classes and displays it
	 *
	 * @return string HTML content to display
	 */
	protected function displayInfoScreen() {
		$content = '';
		$registeredClasses = self::getRegisteredClasses();
		// No classes available, display information message
		if (count($registeredClasses) == 0) {
			/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('msg.noTasksDefined'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$content .= $flashMessage->render();
		} else {
			// Initialise table layout
			$tableLayout = array(
				'table' => array('<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist">', '</table>'),
				'0' => array(
					'tr' => array('<tr class="t3-row-header" valign="top">', '</tr>'),
					'defCol' => array('<td>', '</td>')
				),
				'defRow' => array(
					'tr' => array('<tr class="db_list_normal">', '</tr>'),
					'defCol' => array('<td>', '</td>')
				)
			);
			$table = array();
			$tr = 0;
			// Header row
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.name');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.extension');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.description');
			$table[$tr][] = '';
			$tr++;
			// Display information about each service
			foreach ($registeredClasses as $class => $classInfo) {
				$table[$tr][] = $classInfo['title'];
				$table[$tr][] = $classInfo['extension'];
				$table[$tr][] = $classInfo['description'];
				$link = $GLOBALS['MCONF']['_'] . '&SET[function]=list&CMD=add&tx_scheduler[class]=' . $class;
				$table[$tr][] = '<a href="' . htmlspecialchars($link) . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:new', TRUE) . '" class="icon">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new') . '</a>';
				$tr++;
			}
			// Render the table and return it
			$content = '<div>' . $GLOBALS['LANG']->getLL('msg.infoScreenIntro') . '</div>';
			$content .= $this->doc->spacer(5);
			$content .= $this->doc->table($table, $tableLayout);
		}
		return $content;
	}

	/**
	 * Display the current server's time along with a help text about server time
	 * usage in the Scheduler
	 *
	 * @return string HTML to display
	 */
	protected function displayServerTime() {
		// Get the current time, formatted
		$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'] . ' T (e';
		$now = date($dateFormat) . ', GMT ' . date('P') . ')';
		// Display the help text
		$serverTime = '<h4>' . $GLOBALS['LANG']->getLL('label.serverTime') . '</h4>';
		$serverTime .= '<p>' . $GLOBALS['LANG']->getLL('msg.serverTimeHelp') . '</p>';
		$serverTime .= '<p>' . sprintf($GLOBALS['LANG']->getLL('msg.serverTime'), $now) . '</p>';
		return $serverTime;
	}

	/**
	 * Renders the task progress bar.
	 *
	 * @param float $progress Task progress
	 * @return string Progress bar markup
	 */
	protected function renderTaskProgressBar($progress) {
		$progressText = $GLOBALS['LANG']->getLL('status.progress') . ':&nbsp;' . $progress . '%';
		$progressBar = '<div class="progress"> <div class="bar" style="width: ' . $progress . '%;">' . $progressText . '</div> </div>';
		return $progressBar;
	}

	/**
	 * Delete a task from the execution queue
	 *
	 * @return void
	 */
	protected function deleteTask() {
		try {
			// Try to fetch the task and delete it
			$task = $this->scheduler->fetchTask($this->submittedData['uid']);
			// If the task is currently running, it may not be deleted
			if ($task->isExecutionRunning()) {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.maynotDeleteRunningTask'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			} else {
				if ($this->scheduler->removeTask($task)) {
					$GLOBALS['BE_USER']->writeLog(4, 0, 0, 0, 'Scheduler task "%s" (UID: %s, Class: "%s") was deleted', array($task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()));
					$this->addMessage($GLOBALS['LANG']->getLL('msg.deleteSuccess'));
				} else {
					$this->addMessage($GLOBALS['LANG']->getLL('msg.deleteError'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				}
			}
		} catch (\UnexpectedValueException $e) {
			// The task could not be unserialized properly, simply delete the database record
			$result = $GLOBALS['TYPO3_DB']->exec_DELETEquery('tx_scheduler_task', 'uid = ' . intval($this->submittedData['uid']));
			if ($result) {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.deleteSuccess'));
			} else {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.deleteError'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			}
		} catch (\OutOfBoundsException $e) {
			// The task was not found, for some reason
			$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.taskNotFound'), $this->submittedData['uid']), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		}
	}

	/**
	 * Clears the registered running executions from the task
	 * Note that this doesn't actually stop the running script. It just unmarks
	 * all executions.
	 * TODO: find a way to really kill the running task
	 *
	 * @return void
	 */
	protected function stopTask() {
		try {
			// Try to fetch the task and stop it
			$task = $this->scheduler->fetchTask($this->submittedData['uid']);
			if ($task->isExecutionRunning()) {
				// If the task is indeed currently running, clear marked executions
				$result = $task->unmarkAllExecutions();
				if ($result) {
					$this->addMessage($GLOBALS['LANG']->getLL('msg.stopSuccess'));
				} else {
					$this->addMessage($GLOBALS['LANG']->getLL('msg.stopError'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				}
			} else {
				// The task is not running, nothing to unmark
				$this->addMessage($GLOBALS['LANG']->getLL('msg.maynotStopNonRunningTask'), \TYPO3\CMS\Core\Messaging\FlashMessage::WARNING);
			}
		} catch (\Exception $e) {
			// The task was not found, for some reason
			$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.taskNotFound'), $this->submittedData['uid']), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		}
	}

	/**
	 * Return a form to add a new task or edit an existing one
	 *
	 * @return 	string	HTML form to add or edit a task
	 */
	protected function editTask() {
		$registeredClasses = self::getRegisteredClasses();
		$content = '';
		$taskInfo = array();
		$task = NULL;
		$process = 'edit';
		if ($this->submittedData['uid'] > 0) {
			// If editing, retrieve data for existing task
			try {
				$taskRecord = $this->scheduler->fetchTaskRecord($this->submittedData['uid']);
				// If there's a registered execution, the task should not be edited
				if (!empty($taskRecord['serialized_executions'])) {
					$this->addMessage($GLOBALS['LANG']->getLL('msg.maynotEditRunningTask'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					throw new \LogicException('Runnings tasks cannot not be edited', 1251232849);
				}
				// Get the task object
				/** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
				$task = unserialize($taskRecord['serialized_task_object']);
				// Set some task information
				$taskInfo['disable'] = $taskRecord['disable'];
				// Check that the task object is valid
				if ($this->scheduler->isValidTaskObject($task)) {
					// The task object is valid, process with fetching current data
					$taskInfo['class'] = get_class($task);
					// Get execution information
					$taskInfo['start'] = $task->getExecution()->getStart();
					$taskInfo['end'] = $task->getExecution()->getEnd();
					$taskInfo['interval'] = $task->getExecution()->getInterval();
					$taskInfo['croncmd'] = $task->getExecution()->getCronCmd();
					$taskInfo['multiple'] = $task->getExecution()->getMultiple();
					if (!empty($taskInfo['interval']) || !empty($taskInfo['croncmd'])) {
						// Guess task type from the existing information
						// If an interval or a cron command is defined, it's a recurring task
						// FIXME: remove magic numbers for the type, use class constants instead
						$taskInfo['type'] = 2;
						$taskInfo['frequency'] = empty($taskInfo['interval']) ? $taskInfo['croncmd'] : $taskInfo['interval'];
					} else {
						// It's not a recurring task
						// Make sure interval and cron command are both empty
						$taskInfo['type'] = 1;
						$taskInfo['frequency'] = '';
						$taskInfo['end'] = 0;
					}
				} else {
					// The task object is not valid
					// Issue error message
					$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.invalidTaskClassEdit'), get_class($task)), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					// Initialize empty values
					$taskInfo['start'] = 0;
					$taskInfo['end'] = 0;
					$taskInfo['frequency'] = '';
					$taskInfo['multiple'] = FALSE;
					$taskInfo['type'] = 1;
				}
			} catch (\OutOfBoundsException $e) {
				// Add a message and continue throwing the exception
				$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.taskNotFound'), $this->submittedData['uid']), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				throw $e;
			}
		} else {
			// If adding a new object, set some default values
			$taskInfo['class'] = key($registeredClasses);
			$taskInfo['type'] = 2;
			$taskInfo['start'] = $GLOBALS['EXEC_TIME'];
			$taskInfo['end'] = '';
			$taskInfo['frequency'] = '';
			$taskInfo['multiple'] = 0;
			$process = 'add';
		}
		if (count($this->submittedData) > 0) {
			// If some data was already submitted, use it to override
			// existing data
			$taskInfo = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($taskInfo, $this->submittedData);
		}
		// Get the extra fields to display for each task that needs some
		$allAdditionalFields = array();
		if ($process == 'add') {
			foreach ($registeredClasses as $class => $registrationInfo) {
				if (!empty($registrationInfo['provider'])) {
					/** @var $providerObject \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface */
					$providerObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($registrationInfo['provider']);
					if ($providerObject instanceof \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface) {
						$additionalFields = $providerObject->getAdditionalFields($taskInfo, NULL, $this);
						$allAdditionalFields = array_merge($allAdditionalFields, array($class => $additionalFields));
					}
				}
			}
		} else {
			if (!empty($registeredClasses[$taskInfo['class']]['provider'])) {
				$providerObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($registeredClasses[$taskInfo['class']]['provider']);
				if ($providerObject instanceof \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface) {
					$allAdditionalFields[$taskInfo['class']] = $providerObject->getAdditionalFields($taskInfo, $task, $this);
				}
			}
		}
		// Load necessary JavaScript
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $this->doc->getPageRenderer();
		$pageRenderer->loadExtJS();
		$pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler') . 'res/tx_scheduler_be.js');
		$pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/tceforms.js');
		$pageRenderer->addJsFile($this->backPath . '../t3lib/js/extjs/ux/Ext.ux.DateTimePicker.js');
		// Define settings for Date Picker
		$typo3Settings = array(
			'datePickerUSmode' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 1 : 0,
			'dateFormat' => array('j-n-Y', 'G:i j-n-Y'),
			'dateFormatUS' => array('n-j-Y', 'G:i n-j-Y')
		);
		$pageRenderer->addInlineSettingArray('', $typo3Settings);
		// Define table layout for add/edit form
		$tableLayout = array(
			'table' => array('<table border="0" cellspacing="0" cellpadding="0" id="edit_form" class="typo3-usersettings">', '</table>')
		);
		// Define a style for hiding
		// Some fields will be hidden when the task is not recurring
		$style = '';
		if ($taskInfo['type'] == 1) {
			$style = ' style="display: none"';
		}
		// Start rendering the add/edit form
		$content .= '<input type="hidden" name="tx_scheduler[uid]" value="' . $this->submittedData['uid'] . '" />';
		$content .= '<input type="hidden" name="previousCMD" value="' . $this->CMD . '" />';
		$table = array();
		$tr = 0;
		$defaultCell = array('<td class="td-input">', '</td>');
		// Disable checkbox
		$label = '<label for="task_disable">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:disable') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_disable', $label);
		$table[$tr][] = '<input type="hidden" name="tx_scheduler[disable]" value="0" />
			 <input type="checkbox" name="tx_scheduler[disable]" value="1" id="task_disable"' . ($taskInfo['disable'] == 1 ? ' checked="checked"' : '') . ' />';
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_disable_row">', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// Task class selector
		$label = '<label for="task_class">' . $GLOBALS['LANG']->getLL('label.class') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_class', $label);
		// On editing, don't allow changing of the task class, unless it was not valid
		if ($this->submittedData['uid'] > 0 && !empty($taskInfo['class'])) {
			$cell = $registeredClasses[$taskInfo['class']]['title'] . ' (' . $registeredClasses[$taskInfo['class']]['extension'] . ')';
			$cell .= '<input type="hidden" name="tx_scheduler[class]" id="task_class" value="' . $taskInfo['class'] . '" />';
		} else {
			$cell = '<select name="tx_scheduler[class]" id="task_class" class="wide" onchange="actOnChangedTaskClass(this)">';
			// Group registered classes by classname
			$groupedClasses = array();
			foreach ($registeredClasses as $class => $classInfo) {
				$groupedClasses[$classInfo['extension']][$class] = $classInfo;
			}
			ksort($groupedClasses);
			// Loop on all grouped classes to display a selector
			foreach ($groupedClasses as $extension => $class) {
				$cell .= '<optgroup label="' . $extension . '">';
				foreach ($groupedClasses[$extension] as $class => $classInfo) {
					$selected = $class == $taskInfo['class'] ? ' selected="selected"' : '';
					$cell .= '<option value="' . $class . '"' . 'title="' . $classInfo['description'] . '"' . $selected . '>' . $classInfo['title'] . '</option>';
				}
				$cell .= '</optgroup>';
			}
			$cell .= '</select>';
		}
		$table[$tr][] = $cell;
		// Make sure each row has a unique id, for manipulation with JS
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_class_row">', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// Task type selector
		$label = '<label for="task_type">' . $GLOBALS['LANG']->getLL('label.type') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_type', $label);
		$table[$tr][] = '<select name="tx_scheduler[type]" id="task_type" onchange="actOnChangedTaskType(this)">' . '<option value="1"' . ($taskInfo['type'] == 1 ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('label.type.single') . '</option>' . '<option value="2"' . ($taskInfo['type'] == 2 ? ' selected="selected"' : '') . '>' . $GLOBALS['LANG']->getLL('label.type.recurring') . '</option>' . '</select>';
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_type_row">', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// Start date/time field
		// NOTE: datetime fields need a special id naming scheme
		$label = '<label for="tceforms-datetimefield-task_start">' . $GLOBALS['LANG']->getLL('label.start') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_start', $label);
		$table[$tr][] = '<input name="tx_scheduler[start]" type="text" id="tceforms-datetimefield-task_start" value="' . (empty($taskInfo['start']) ? '' : strftime('%H:%M %d-%m-%Y', $taskInfo['start'])) . '" />' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-pick-date', array(
			'style' => 'cursor:pointer;',
			'id' => 'picker-tceforms-datetimefield-task_start'
		));
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_start_row">', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// End date/time field
		// NOTE: datetime fields need a special id naming scheme
		$label = '<label for="tceforms-datetimefield-task_end">' . $GLOBALS['LANG']->getLL('label.end') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_end', $label);
		$table[$tr][] = '<input name="tx_scheduler[end]" type="text" id="tceforms-datetimefield-task_end" value="' . (empty($taskInfo['end']) ? '' : strftime('%H:%M %d-%m-%Y', $taskInfo['end'])) . '" />' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-pick-date', array(
			'style' => 'cursor:pointer;',
			'id' => 'picker-tceforms-datetimefield-task_end'
		));
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_end_row"' . $style . '>', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// Frequency input field
		$label = '<label for="task_frequency">' . $GLOBALS['LANG']->getLL('label.frequency.long') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_frequency', $label);
		$cell = '<input type="text" name="tx_scheduler[frequency]" id="task_frequency" value="' . htmlspecialchars($taskInfo['frequency']) . '" />';
		$table[$tr][] = $cell;
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_frequency_row"' . $style . '>', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// Multiple execution selector
		$label = '<label for="task_multiple">' . $GLOBALS['LANG']->getLL('label.parallel.long') . '</label>';
		$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($this->cshKey, 'task_multiple', $label);
		$table[$tr][] = '<input type="hidden"   name="tx_scheduler[multiple]" value="0" />
			 <input type="checkbox" name="tx_scheduler[multiple]" value="1" id="task_multiple"' . ($taskInfo['multiple'] == 1 ? ' checked="checked"' : '') . ' />';
		$tableLayout[$tr] = array(
			'tr' => array('<tr id="task_multiple_row"' . $style . '>', '</tr>'),
			'defCol' => $defaultCell,
			'0' => array('<td class="td-label">', '</td>')
		);
		$tr++;
		// Display additional fields
		foreach ($allAdditionalFields as $class => $fields) {
			if ($class == $taskInfo['class']) {
				$additionalFieldsStyle = '';
			} else {
				$additionalFieldsStyle = ' style="display: none"';
			}
			// Add each field to the display, if there are indeed any
			if (isset($fields) && is_array($fields)) {
				foreach ($fields as $fieldID => $fieldInfo) {
					$label = '<label for="' . $fieldID . '">' . $GLOBALS['LANG']->sL($fieldInfo['label']) . '</label>';
					$table[$tr][] = \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp($fieldInfo['cshKey'], $fieldInfo['cshLabel'], $label);
					$table[$tr][] = $fieldInfo['code'];
					$htmlClassName = strtolower(str_replace('\\', '-', $class));
					$tableLayout[$tr] = array(
						'tr' => array('<tr id="' . $fieldID . '_row"' . $additionalFieldsStyle . ' class="extraFields extra_fields_' . $htmlClassName . '">', '</tr>'),
						'defCol' => $defaultCell,
						'0' => array('<td class="td-label">', '</td>')
					);
					$tr++;
				}
			}
		}
		// Render the add/edit task form
		$content .= '<div style="float: left;"><div class="typo3-dyntabmenu-divs">';
		$content .= $this->doc->table($table, $tableLayout);
		$content .= '</div></div>';
		$content .= '<div style="padding-top: 20px; clear: both;"></div>';
		// Display information about server time usage
		$content .= $this->displayServerTime();
		return $content;
	}

	/**
	 * Execute all selected tasks
	 *
	 * @return void
	 */
	protected function executeTasks() {
		// Make sure next automatic scheduler-run is scheduled
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('go') !== NULL) {
			$this->scheduler->scheduleNextSchedulerRunUsingAtDaemon();
		}
		// Continue if some elements have been chosen for execution
		if (isset($this->submittedData['execute']) && count($this->submittedData['execute']) > 0) {
			// Get list of registered classes
			$registeredClasses = self::getRegisteredClasses();
			// Loop on all selected tasks
			foreach ($this->submittedData['execute'] as $uid) {
				try {
					// Try fetching the task
					$task = $this->scheduler->fetchTask($uid);
					$class = get_class($task);
					$name = $registeredClasses[$class]['title'] . ' (' . $registeredClasses[$class]['extension'] . ')';
					// Now try to execute it and report on outcome
					try {
						$result = $this->scheduler->executeTask($task);
						if ($result) {
							$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.executed'), $name));
						} else {
							$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.notExecuted'), $name), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
						}
					} catch (\Exception $e) {
						// An exception was thrown, display its message as an error
						$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.executionFailed'), $name, $e->getMessage()), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					}
				} catch (\OutOfBoundsException $e) {
					$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.taskNotFound'), $uid), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				} catch (\UnexpectedValueException $e) {
					$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.executionFailed'), $uid, $e->getMessage()), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				}
			}
			// Record the run in the system registry
			$this->scheduler->recordLastRun('manual');
			// Make sure to switch to list view after execution
			$this->CMD = 'list';
		}
	}

	/**
	 * Assemble display of list of scheduled tasks
	 *
	 * @return string Table of pending tasks
	 */
	protected function listTasks() {
		// Define display format for dates
		$dateFormat = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] . ' ' . $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
		$content = '';
		// Get list of registered classes
		$registeredClasses = self::getRegisteredClasses();
		// Get all registered tasks
		$query = array(
			'SELECT' => '*',
			'FROM' => 'tx_scheduler_task',
			'WHERE' => '1=1',
			'ORDERBY' => 'nextexecution'
		);
		$res = $GLOBALS['TYPO3_DB']->exec_SELECT_queryArray($query);
		$numRows = $GLOBALS['TYPO3_DB']->sql_num_rows($res);
		// No tasks defined, display information message
		if ($numRows == 0) {
			/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('msg.noTasks'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$content .= $flashMessage->render();
		} else {
			// Load ExtJS framework and specific JS library
			/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
			$pageRenderer = $this->doc->getPageRenderer();
			$pageRenderer->loadExtJS();
			$pageRenderer->addJsFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler') . 'res/tx_scheduler_be.js');
			// Initialise table layout
			$tableLayout = array(
				'table' => array(
					'<table border="0" cellspacing="0" cellpadding="0" class="typo3-dblist">',
					'</table>'
				),
				'0' => array(
					'tr' => array('<tr class="t3-row-header">', '</tr>'),
					'defCol' => array('<td>', '</td>'),
					'1' => array('<td style="width: 56px;">', '</td>'),
					'3' => array('<td colspan="2">', '</td>')
				),
				'defRow' => array(
					'tr' => array('<tr class="db_list_normal">', '</tr>'),
					'defCol' => array('<td>', '</td>'),
					'1' => array('<td class="right">', '</td>'),
					'2' => array('<td class="right">', '</td>')
				)
			);
			$disabledTaskRow = array(
				'tr' => array('<tr class="db_list_normal disabled">', '</tr>'),
				'defCol' => array('<td>', '</td>'),
				'1' => array('<td class="right">', '</td>'),
				'2' => array('<td class="right">', '</td>')
			);
			$rowWithSpan = array(
				'tr' => array('<tr class="db_list_normal">', '</tr>'),
				'defCol' => array('<td>', '</td>'),
				'1' => array('<td class="right">', '</td>'),
				'2' => array('<td class="right">', '</td>'),
				'3' => array('<td colspan="6">', '</td>')
			);
			$table = array();
			$tr = 0;
			// Header row
			$table[$tr][] = '<a href="#" onclick="toggleCheckboxes();" title="' . $GLOBALS['LANG']->getLL('label.checkAll', TRUE) . '" class="icon">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-select') . '</a>';
			$table[$tr][] = '&nbsp;';
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.id');
			$table[$tr][] = $GLOBALS['LANG']->getLL('task');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.type');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.frequency');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.parallel');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.lastExecution');
			$table[$tr][] = $GLOBALS['LANG']->getLL('label.nextExecution');
			$tr++;
			// Loop on all tasks
			while ($schedulerRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
				// Define action icons
				$editAction = '<a href="' . $GLOBALS['MCONF']['_'] . '&CMD=edit&tx_scheduler[uid]=' . $schedulerRecord['uid'] . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:edit', TRUE) . '" class="icon">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-open') . '</a>';
				$deleteAction = '<a href="' . $GLOBALS['MCONF']['_'] . '&CMD=delete&tx_scheduler[uid]=' . $schedulerRecord['uid'] . '" onclick="return confirm(\'' . $GLOBALS['LANG']->getLL('msg.delete') . '\');" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:delete', TRUE) . '" class="icon">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete') . '</a>';
				$stopAction = '<a href="' . $GLOBALS['MCONF']['_'] . '&CMD=stop&tx_scheduler[uid]=' . $schedulerRecord['uid'] . '" onclick="return confirm(\'' . $GLOBALS['LANG']->getLL('msg.stop') . '\');" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:stop', TRUE) . '" class="icon"><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler') . '/res/gfx/stop.png')) . ' alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:stop') . '" /></a>';
				$runAction = '<a href="' . $GLOBALS['MCONF']['_'] . '&tx_scheduler[execute][]=' . $schedulerRecord['uid'] . '" title="' . $GLOBALS['LANG']->getLL('action.run_task') . '" class="icon">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('extensions-scheduler-run-task') . '</a>';
				// Define some default values
				$lastExecution = '-';
				$isRunning = FALSE;
				$executionStatus = 'scheduled';
				$executionStatusOutput = '';
				$name = '';
				$nextDate = '-';
				$execType = '-';
				$frequency = '-';
				$multiple = '-';
				$startExecutionElement = '&nbsp;';
				// Restore the serialized task and pass it a reference to the scheduler object
				/** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask|\TYPO3\CMS\Scheduler\ProgressProviderInterface */
				$task = unserialize($schedulerRecord['serialized_task_object']);
				$class = get_class($task);
				if ($class === '__PHP_Incomplete_Class' && preg_match('/^O:[0-9]+:"(?P<classname>.+?)"/', $schedulerRecord['serialized_task_object'], $matches) === 1) {
					$class = $matches['classname'];
				}
				// Assemble information about last execution
				$context = '';
				if (!empty($schedulerRecord['lastexecution_time'])) {
					$lastExecution = date($dateFormat, $schedulerRecord['lastexecution_time']);
					if ($schedulerRecord['lastexecution_context'] == 'CLI') {
						$context = $GLOBALS['LANG']->getLL('label.cron');
					} else {
						$context = $GLOBALS['LANG']->getLL('label.manual');
					}
					$lastExecution .= ' (' . $context . ')';
				}
				if ($this->scheduler->isValidTaskObject($task)) {
					// The task object is valid
					$name = htmlspecialchars($registeredClasses[$class]['title'] . ' (' . $registeredClasses[$class]['extension'] . ')');
					$name .= '<br /> ';
					$additionalInformation = $task->getAdditionalInformation();
					if ($task instanceof \TYPO3\CMS\Scheduler\ProgressProviderInterface) {
						$progress = round(floatval($task->getProgress()), 2);
						$name .= $this->renderTaskProgressBar($progress);
					}
					if (!empty($additionalInformation)) {
						$name .= '[' . htmlspecialchars($additionalInformation) . ']';
					}
					// Check if task currently has a running execution
					if (!empty($schedulerRecord['serialized_executions'])) {
						$isRunning = TRUE;
						$executionStatus = 'running';
					}
					// Prepare display of next execution date
					// If task is currently running, date is not displayed (as next hasn't been calculated yet)
					// Also hide the date if task is disabled (the information doesn't make sense, as it will not run anyway)
					if ($isRunning || $schedulerRecord['disable'] == 1) {
						$nextDate = '-';
					} else {
						$nextDate = date($dateFormat, $schedulerRecord['nextexecution']);
						if (empty($schedulerRecord['nextexecution'])) {
							$nextDate = $GLOBALS['LANG']->getLL('none');
						} elseif ($schedulerRecord['nextexecution'] < $GLOBALS['EXEC_TIME']) {
							// Next execution is overdue, highlight date
							$nextDate = '<span class="late" title="' . $GLOBALS['LANG']->getLL('status.legend.scheduled') . '">' . $nextDate . '</span>';
							$executionStatus = 'late';
						}
					}
					// Get execution type
					if ($task->getExecution()->getInterval() == 0 && $task->getExecution()->getCronCmd() == '') {
						$execType = $GLOBALS['LANG']->getLL('label.type.single');
						$frequency = '-';
					} else {
						$execType = $GLOBALS['LANG']->getLL('label.type.recurring');
						if ($task->getExecution()->getCronCmd() == '') {
							$frequency = $task->getExecution()->getInterval();
						} else {
							$frequency = $task->getExecution()->getCronCmd();
						}
					}
					// Get multiple executions setting
					if ($task->getExecution()->getMultiple()) {
						$multiple = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:yes');
					} else {
						$multiple = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:no');
					}
					// Define checkbox
					$startExecutionElement = '<input type="checkbox" name="tx_scheduler[execute][]" value="' . $schedulerRecord['uid'] . '" id="task_' . $schedulerRecord['uid'] . '" class="checkboxes" />';

					$actions = $editAction . $deleteAction;

					// Check the disable status
					// Row is shown dimmed if task is disabled, unless it is still running
					if ($schedulerRecord['disable'] == 1 && !$isRunning) {
						$tableLayout[$tr] = $disabledTaskRow;
						$executionStatus = 'disabled';
					}

					// Show no action links (edit, delete) if task is running
					if ($isRunning) {
						$actions = $stopAction;
					} else {
						$actions .= $runAction;
					}

					// Check if the last run failed
					$failureOutput = '';
					if (!empty($schedulerRecord['lastexecution_failure'])) {
						// Try to get the stored exception object
						/** @var $exception \Exception */
						$exception = unserialize($schedulerRecord['lastexecution_failure']);
						// If the exception could not be unserialized, issue a default error message
						if ($exception === FALSE || $exception instanceof \__PHP_Incomplete_Class) {
							$failureDetail = $GLOBALS['LANG']->getLL('msg.executionFailureDefault');
						} else {
							$failureDetail = sprintf($GLOBALS['LANG']->getLL('msg.executionFailureReport'), $exception->getCode(), $exception->getMessage());
						}
						$failureOutput = ' <img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), 'res/gfx/status_failure.png') . ' alt="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.failure')) . '" title="' . htmlspecialchars($failureDetail) . '" />';
					}
					// Format the execution status,
					// including failure feedback, if any
					$executionStatusOutput = '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), ('res/gfx/status_' . $executionStatus . '.png')) . ' id="executionstatus_' . $schedulerRecord['uid'] . '" alt="' . htmlspecialchars($GLOBALS['LANG']->getLL(('status.' . $executionStatus))) . '" title="' . htmlspecialchars($GLOBALS['LANG']->getLL(('status.legend.' . $executionStatus))) . '" />' . $failureOutput;
					$table[$tr][] = $startExecutionElement;
					$table[$tr][] = $actions;
					$table[$tr][] = $schedulerRecord['uid'];
					$table[$tr][] = $executionStatusOutput;
					$table[$tr][] = $name;
					$table[$tr][] = $execType;
					$table[$tr][] = $frequency;
					$table[$tr][] = $multiple;
					$table[$tr][] = $lastExecution;
					$table[$tr][] = $nextDate;
				} else {
					// The task object is not valid
					// Prepare to issue an error
					/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
					$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', sprintf($GLOBALS['LANG']->getLL('msg.invalidTaskClass'), $class), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					$executionStatusOutput = $flashMessage->render();
					$tableLayout[$tr] = $rowWithSpan;
					$table[$tr][] = $startExecutionElement;
					$table[$tr][] = $deleteAction;
					$table[$tr][] = $schedulerRecord['uid'];
					$table[$tr][] = $executionStatusOutput;
				}
				$tr++;
			}
			// Render table
			$content .= $this->doc->table($table, $tableLayout);
			$content .= '<input type="submit" class="button" name="go" id="scheduler_executeselected" value="' . $GLOBALS['LANG']->getLL('label.executeSelected') . '" />';
		}
		if (!count($registeredClasses) > 0) {
			/** @var $flashMessage \TYPO3\CMS\Core\Messaging\FlashMessage */
			$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $GLOBALS['LANG']->getLL('msg.noTasksDefined'), '', \TYPO3\CMS\Core\Messaging\FlashMessage::INFO);
			$content .= $flashMessage->render();
		}
		// Display legend, if there's at least one registered task
		// Also display information about the usage of server time
		if ($numRows > 0) {
			$content .= $this->doc->spacer(20);
			$content .= '<h4>' . $GLOBALS['LANG']->getLL('status.legend') . '</h4>
			<ul>
				<li><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), 'res/gfx/status_failure.png') . ' alt="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.failure')) . '" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.failure')) . '" /> ' . $GLOBALS['LANG']->getLL('status.legend.failure') . '</li>
				<li><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), 'res/gfx/status_late.png') . ' alt="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.late')) . '" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.late')) . '" /> ' . $GLOBALS['LANG']->getLL('status.legend.late') . '</li>
				<li><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), 'res/gfx/status_running.png') . ' alt="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.running')) . '" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.running')) . '" /> ' . $GLOBALS['LANG']->getLL('status.legend.running') . '</li>
				<li><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), 'res/gfx/status_scheduled.png') . ' alt="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.scheduled')) . '" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.scheduled')) . '" /> ' . $GLOBALS['LANG']->getLL('status.legend.scheduled') . '</li>
				<li><img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('scheduler'), 'res/gfx/status_disabled.png') . ' alt="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.disabled')) . '" title="' . htmlspecialchars($GLOBALS['LANG']->getLL('status.disabled')) . '" /> ' . $GLOBALS['LANG']->getLL('status.legend.disabled') . '</li>
			</ul>';
			$content .= $this->doc->spacer(10);
			$content .= $this->displayServerTime();
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $content;
	}

	/**
	 * Saves a task specified in the backend form to the database
	 *
	 * @return void
	 */
	protected function saveTask() {
		// If a task is being edited fetch old task data
		if (!empty($this->submittedData['uid'])) {
			try {
				$taskRecord = $this->scheduler->fetchTaskRecord($this->submittedData['uid']);
				/** @var $task \TYPO3\CMS\Scheduler\Task\AbstractTask */
				$task = unserialize($taskRecord['serialized_task_object']);
			} catch (\OutOfBoundsException $e) {
				// If the task could not be fetched, issue an error message
				// and exit early
				$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.taskNotFound'), $this->submittedData['uid']), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				return;
			}
			// Register single execution
			if ($this->submittedData['type'] == 1) {
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
			// Save additional input values
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields'])) {
				/** @var $providerObject \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface */
				$providerObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields']);
				if ($providerObject instanceof \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface) {
					$providerObject->saveAdditionalFields($this->submittedData, $task);
				}
			}
			// Save to database
			$result = $this->scheduler->saveTask($task);
			if ($result) {
				$GLOBALS['BE_USER']->writeLog(4, 0, 0, 0, 'Scheduler task "%s" (UID: %s, Class: "%s") was updated', array($task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()));
				$this->addMessage($GLOBALS['LANG']->getLL('msg.updateSuccess'));
			} else {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.updateError'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			}
		} else {
			// A new task is being created
			// Create an instance of chosen class
			$task = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($this->submittedData['class']);
			if ($this->submittedData['type'] == 1) {
				// Set up single execution
				$task->registerSingleExecution($this->submittedData['start']);
			} else {
				// Set up recurring execution
				$task->registerRecurringExecution($this->submittedData['start'], $this->submittedData['interval'], $this->submittedData['end'], $this->submittedData['multiple'], $this->submittedData['croncmd']);
			}
			// Save additional input values
			if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields'])) {
				/** @var $providerObject \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface */
				$providerObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields']);
				if ($providerObject instanceof \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface) {
					$providerObject->saveAdditionalFields($this->submittedData, $task);
				}
			}
			// Set disable flag
			$task->setDisabled($this->submittedData['disable']);
			// Add to database
			$result = $this->scheduler->addTask($task);
			if ($result) {
				$GLOBALS['BE_USER']->writeLog(4, 0, 0, 0, 'Scheduler task "%s" (UID: %s, Class: "%s") was added', array($task->getTaskTitle(), $task->getTaskUid(), $task->getTaskClassName()));
				$this->addMessage($GLOBALS['LANG']->getLL('msg.addSuccess'));

				// set the uid of the just created task so that we
				// can continue editing after initial saving
				$this->submittedData['uid'] = $task->getTaskUid();
			} else {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.addError'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
	 * @return boolean TRUE if everything was ok, FALSE otherwise
	 */
	protected function preprocessData() {
		$result = TRUE;
		// Validate id
		$this->submittedData['uid'] = empty($this->submittedData['uid']) ? 0 : intval($this->submittedData['uid']);
		// Validate selected task class
		if (!class_exists($this->submittedData['class'])) {
			$this->addMessage($GLOBALS['LANG']->getLL('msg.noTaskClassFound'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		}
		// Check start date
		if (empty($this->submittedData['start'])) {
			$this->addMessage($GLOBALS['LANG']->getLL('msg.noStartDate'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
			$result = FALSE;
		} else {
			try {
				$timestamp = $this->checkDate($this->submittedData['start']);
				$this->submittedData['start'] = $timestamp;
			} catch (\Exception $e) {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.invalidStartDate'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$result = FALSE;
			}
		}
		// Check end date, if recurring task
		if ($this->submittedData['type'] == 2 && !empty($this->submittedData['end'])) {
			try {
				$timestamp = $this->checkDate($this->submittedData['end']);
				$this->submittedData['end'] = $timestamp;
				if ($this->submittedData['end'] < $this->submittedData['start']) {
					$this->addMessage($GLOBALS['LANG']->getLL('msg.endDateSmallerThanStartDate'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					$result = FALSE;
				}
			} catch (\Exception $e) {
				$this->addMessage($GLOBALS['LANG']->getLL('msg.invalidEndDate'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$result = FALSE;
			}
		}
		// Set default values for interval and cron command
		$this->submittedData['interval'] = 0;
		$this->submittedData['croncmd'] = '';
		// Check type and validity of frequency, if recurring
		if ($this->submittedData['type'] == 2) {
			$frequency = trim($this->submittedData['frequency']);
			if (empty($frequency)) {
				// Empty frequency, not valid
				$this->addMessage($GLOBALS['LANG']->getLL('msg.noFrequency'), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
				$result = FALSE;
			} else {
				$cronErrorCode = 0;
				$cronErrorMessage = '';
				// Try interpreting the cron command
				try {
					\TYPO3\CMS\Scheduler\CronCommand\NormalizeCommand::normalize($frequency);
					$this->submittedData['croncmd'] = $frequency;
				} catch (\Exception $e) {
					// Store the exception's result
					$cronErrorMessage = $e->getMessage();
					$cronErrorCode = $e->getCode();
					// Check if the frequency is a valid number
					// If yes, assume it is a frequency in seconds, and unset cron error code
					if (is_numeric($frequency)) {
						$this->submittedData['interval'] = intval($frequency);
						unset($cronErrorCode);
					}
				}
				// If there's a cron error code, issue validation error message
				if (!empty($cronErrorCode)) {
					$this->addMessage(sprintf($GLOBALS['LANG']->getLL('msg.frequencyError'), $cronErrorMessage, $cronErrorCode), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
					$result = FALSE;
				}
			}
		}
		// Validate additional input fields
		if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields'])) {
			/** @var $providerObject \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface */
			$providerObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][$this->submittedData['class']]['additionalFields']);
			if ($providerObject instanceof \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface) {
				// The validate method will return TRUE if all went well, but that must not
				// override previous FALSE values => AND the returned value with the existing one
				$result &= $providerObject->validateAdditionalFields($this->submittedData, $this);
			}
		}
		return $result;
	}

	/**
	 * This method checks whether the given string can be considered a valid date or not
	 * Allowed values are anything that matches natural language (see PHP function strtotime())
	 * or TYPO3's date syntax: HH:ii yyyy-mm-dd
	 * If the string is a valid date, the corresponding timestamp is returned.
	 * Otherwise an exception is thrown
	 *
	 * @param string $string String to check
	 * @return integer Unix timestamp
	 * @throws \InvalidArgumentException
	 */
	public function checkDate($string) {
		// Try with strtotime
		$timestamp = strtotime($string);
		// That failed. Try TYPO3's standard date/time input format
		if ($timestamp === FALSE) {
			// Split time and date
			$dateParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(' ', $string, TRUE);
			// Proceed if there are indeed two parts
			// Extract each component of date and time
			if (count($dateParts) == 2) {
				list($time, $date) = $dateParts;
				list($hour, $minutes) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $time, TRUE);
				list($day, $month, $year) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('-', $date, TRUE);
				// Get a timestamp from all these parts
				$timestamp = @mktime($hour, $minutes, 0, $month, $day, $year);
			}
			// If the timestamp is still FALSE, throw an exception
			if ($timestamp === FALSE) {
				throw new \InvalidArgumentException('"' . $string . '" seems not to be a correct date.', 1294587694);
			}
		}
		return $timestamp;
	}

	/*************************
	 *
	 * APPLICATION LOGIC UTILITIES
	 *
	 *************************/
	/**
	 * This method is used to add a message to the internal queue
	 *
	 * @param string $message The message itself
	 * @param integer $severity Message level (according to \TYPO3\CMS\Core\Messaging\FlashMessage class constants)
	 * @return void
	 */
	public function addMessage($message, $severity = \TYPO3\CMS\Core\Messaging\FlashMessage::OK) {
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $message, '', $severity);
		/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
		$flashMessageService = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
		/** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
		$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
		$defaultFlashMessageQueue->enqueue($flashMessage);
	}

	/**
	 * This method a list of all classes that have been registered with the Scheduler
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
	static protected function getRegisteredClasses() {
		$list = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'] as $class => $registrationInformation) {
				$title = isset($registrationInformation['title']) ? $GLOBALS['LANG']->sL($registrationInformation['title']) : '';
				$description = isset($registrationInformation['description']) ? $GLOBALS['LANG']->sL($registrationInformation['description']) : '';
				$list[$class] = array(
					'extension' => $registrationInformation['extension'],
					'title' => $title,
					'description' => $description,
					'provider' => isset($registrationInformation['additionalFields']) ? $registrationInformation['additionalFields'] : ''
				);
			}
		}
		return $list;
	}

	/*************************
	 *
	 * RENDERING UTILITIES
	 *
	 *************************/
	/**
	 * Gets the filled markers that are used in the HTML template.
	 *
	 * @return array The filled marker array
	 */
	protected function getTemplateMarkers() {
		$markers = array(
			'CSH' => \TYPO3\CMS\Backend\Utility\BackendUtility::wrapInHelp('_MOD_tools_txschedulerM1', ''),
			'FUNC_MENU' => $this->getFunctionMenu(),
			'CONTENT' => $this->content,
			'TITLE' => $GLOBALS['LANG']->getLL('title')
		);
		return $markers;
	}

	/**
	 * Gets the function menu selector for this backend module.
	 *
	 * @return string The HTML representation of the function menu selector
	 */
	protected function getFunctionMenu() {
		$functionMenu = \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu(0, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']);
		return $functionMenu;
	}

	/**
	 * Gets the buttons that shall be rendered in the docHeader.
	 *
	 * @return array Available buttons for the docHeader
	 */
	protected function getDocHeaderButtons() {
		$buttons = array(
			'addtask' => '',
			'close' => '',
			'save' => '',
			'saveclose' => '',
			'reload' => '',
			'shortcut' => $this->getShortcutButton()
		);
		if (empty($this->CMD) || $this->CMD == 'list' || $this->CMD == 'delete') {
			$buttons['reload'] = '<a href="' . $GLOBALS['MCONF']['_'] . '" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-system-refresh') . '</a>';
			if ($this->MOD_SETTINGS['function'] === 'scheduler' && count(self::getRegisteredClasses())) {
				$link = $GLOBALS['MCONF']['_'] . '&CMD=add';
				$image = '<img ' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($this->backPath, 'gfx/new_el.gif') . ' alt="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:new', TRUE) . '" />';
				$buttons['addtask'] = '<a href="' . htmlspecialchars($link) . '" ' . 'title="' . $GLOBALS['LANG']->getLL('action.add') . '">' . $image . '</a>';
			}
		}
		if ($this->CMD === 'add' || $this->CMD === 'edit') {
			$buttons['close'] = '<a href="#" onclick="document.location=\'' . $GLOBALS['MCONF']['_'] . '\'" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:cancel', TRUE) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-close') . '</a>';
			$buttons['save'] = '<button style="padding: 0; margin: 0; cursor: pointer;" type="submit" name="CMD" value="save" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:save', TRUE) . '" />' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save') . '</button>';
			$buttons['saveclose'] = '<button style="padding: 0; margin: 0; cursor: pointer;" type="submit" name="CMD" value="saveclose" class="c-inputButton" src="clear.gif" title="' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_common.xlf:saveAndClose', TRUE) . '" />' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-save-close') . '</button>';
		}
		return $buttons;
	}

	/**
	 * Gets the button to set a new shortcut in the backend (if current user is allowed to).
	 *
	 * @return string HTML representation of the shortcut button
	 */
	protected function getShortcutButton() {
		$result = '';
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$result = $this->doc->makeShortcutIcon('', 'function', $this->MCONF['name']);
		}
		return $result;
	}

}


?>
