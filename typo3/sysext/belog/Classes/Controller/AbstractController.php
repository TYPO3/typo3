<?php
namespace TYPO3\CMS\Belog\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Abstract class to show log entries from sys_log
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
abstract class AbstractController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var integer
	 */
	const TIMEFRAME_THISWEEK = 0;

	/**
	 * @var integer
	 */
	const TIMEFRAME_LASTWEEK = 1;

	/**
	 * @var integer
	 */
	const TIMEFRAME_LASTSEVENDAYS = 2;

	/**
	 * @var integer
	 */
	const TIMEFRAME_THISMONTH = 10;

	/**
	 * @var integer
	 */
	const TIMEFRAME_LASTMONTH = 11;

	/**
	 * @var integer
	 */
	const TIMEFRAME_LAST31DAYS = 12;

	/**
	 * @var integer
	 */
	const TIMEFRAME_CUSTOM = 30;

	/**
	 * Whether plugin is running in page context (sub module of Web > Info)
	 *
	 * @var boolean
	 */
	protected $isInPageContext = FALSE;

	/**
	 * Page ID in page context
	 *
	 * @var integer
	 */
	protected $pageId = 0;

	/**
	 * @var \TYPO3\CMS\Belog\Domain\Repository\LogEntryRepository
	 * @inject
	 */
	protected $logEntryRepository = NULL;

	/**
	 * Initialize index action
	 *
	 * @return void
	 * @throws \RuntimeException
	 */
	public function initializeIndexAction() {
		// @TODO: Extbase backend modules rely on frontend TypoScript for view, persistence
		// and settings. Thus, we need a TypoScript root template, that then loads the
		// ext_typoscript_setup.txt file of this module. This is nasty, but can not be
		// circumvented until there is a better solution in extbase.
		// For now we throw an exception if no settings are detected.
		if (empty($this->settings)) {
			throw new \RuntimeException(
				'No settings detected. This usually happens if there is no frontend TypoScript template with root flag set. Please create one.',
				1333650506
			);
		}
		if (!isset($this->settings['dateFormat'])) {
			$this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
		}
		if (!isset($this->settings['timeFormat'])) {
			$this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
		}
		// @TODO: The dateTime property mapper throws exceptions that cannot be caught
		// if the property is not given in the expected format. This is documented with
		// issue http://forge.typo3.org/issues/33861. Depending on the outcome of the
		// ticket, the code below might have to be adapted again.
		// @TODO: There is a second solution to hint the property mapper with fluid on the expected
		// format: <f:form.hidden property="manualDateStart.dateFormat" value="..." />. This
		// could make the method below obsolete.
		$this->configurePropertyMapperForDateTimeFormat($this->arguments['constraint']->getPropertyMappingConfiguration()->forProperty('manualDateStart'));
		$this->configurePropertyMapperForDateTimeFormat($this->arguments['constraint']->getPropertyMappingConfiguration()->forProperty('manualDateStop'));
	}

	/**
	 * Show general information and the installed modules
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @return void
	 */
	public function indexAction(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint = NULL) {
		// Constraint object handling:
		// If there is none from GET, try to get it from BE user data, else create new
		if ($constraint === NULL) {
			$constraint = $this->getConstraintFromBeUserData();
			if ($constraint === NULL) {
				$constraint = $this->objectManager->get('TYPO3\\CMS\\Belog\\Domain\\Model\\Constraint');
			}
		} else {
			$this->persistConstraintInBeUserData($constraint);
		}
		$constraint->setIsInPageContext($this->isInPageContext);
		$constraint->setPageId($this->pageId);
		$this->setStartAndEndTimeFromTimeSelector($constraint);
		$this->forceWorkspaceSelectionIfInWorkspace($constraint);
		$logEntries = $this->logEntryRepository->findByConstraint($constraint);
		$groupedLogEntries = $this->groupLogEntriesByPageAndDay($logEntries, $constraint->getGroupByPage());
		$this->view->assign('groupedLogEntries', $groupedLogEntries)->assign('constraint', $constraint)->assign('userGroups', $this->createUserAndGroupListForSelectOptions())->assign('workspaces', $this->createWorkspaceListForSelectOptions())->assign('pageDepths', $this->createPageDepthOptions());
	}

	/**
	 * Get module states (the constraint object) from user data
	 *
	 * @return \TYPO3\CMS\Belog\Domain\Model\Constraint|NULL
	 */
	protected function getConstraintFromBeUserData() {
		$serializedConstraint = $GLOBALS['BE_USER']->getModuleData(get_class($this));
		if (!is_string($serializedConstraint) || empty($serializedConstraint)) {
			return NULL;
		}
		return @unserialize($serializedConstraint);
	}

	/**
	 * Save current constraint object in be user settings (uC)
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @return void
	 */
	protected function persistConstraintInBeUserData(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint) {
		$GLOBALS['BE_USER']->pushModuleData(get_class($this), serialize($constraint));
	}

	/**
	 * Create a sorted array for day and page view from
	 * the query result of the sys log repository.
	 *
	 * If group by page is FALSE, pid is always -1 (will render a flat list),
	 * otherwise the output is splitted by pages.
	 * '12345' is a sub array to split entries by day, number is first second of day
	 *
	 * [pid][dayTimestamp][items]
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\TYPO3\CMS\Belog\Domain\Model\LogEntry> $logEntries
	 * @param boolean $groupByPage Whether or not log entries should be grouped by page
	 * @return array
	 */
	protected function groupLogEntriesByPageAndDay(\TYPO3\CMS\Extbase\Persistence\QueryResultInterface $logEntries, $groupByPage = FALSE) {
		$targetStructure = array();
		/** @var $entry \TYPO3\CMS\Belog\Domain\Model\LogEntry */
		foreach ($logEntries as $entry) {
			// Create page split list or flat list
			if ($groupByPage) {
				$pid = $entry->getEventPid();
			} else {
				$pid = -1;
			}
			// Create array if it is not defined yet
			if (!is_array($targetStructure[$pid])) {
				$targetStructure[$pid] = array();
			}
			// Get day timestamp of log entry and create sub array if needed
			$timestampDay = strtotime(strftime('%d.%m.%Y', $entry->getTstamp()));
			if (!is_array($targetStructure[$pid][$timestampDay])) {
				$targetStructure[$pid][$timestampDay] = array();
			}
			// Add row
			$targetStructure[$pid][$timestampDay][] = $entry;
		}
		ksort($targetStructure);
		return $targetStructure;
	}

	/**
	 * Configure the property mapper to expect date strings in configured BE format
	 *
	 * @param \TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMapperDate
	 * @return void
	 */
	protected function configurePropertyMapperForDateTimeFormat(\TYPO3\CMS\Extbase\Property\PropertyMappingConfiguration $propertyMapperDate) {
		$propertyMapperDate->setTypeConverterOption('TYPO3\\CMS\\Extbase\\Property\\TypeConverter\\DateTimeConverter', \TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter::CONFIGURATION_DATE_FORMAT, $this->settings['dateFormat'] . ' ' . $this->settings['timeFormat']);
	}

	/**
	 * Create options for the user / group drop down.
	 * This is not moved to a repository by intention to not mix up this 'meta' data
	 * with real repository work
	 *
	 * @return array Key is the option name, value its label
	 */
	protected function createUserAndGroupListForSelectOptions() {
		$userGroupArray = array();
		// Two meta entries: 'all' and 'self'
		$userGroupArray[0] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('allUsers', 'Belog');
		$userGroupArray[-1] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('self', 'Belog');
		// List of groups, key is gr-'uid'
		$groups = \TYPO3\CMS\Backend\Utility\BackendUtility::getGroupNames();
		foreach ($groups as $group) {
			$userGroupArray['gr-' . $group['uid']] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('group', 'Belog') . ' ' . $group['title'];
		}
		// List of users, key is us-'uid'
		$users = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
		foreach ($users as $user) {
			$userGroupArray['us-' . $user['uid']] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('user', 'Belog') . ' ' . $user['username'];
		}
		return $userGroupArray;
	}

	/**
	 * Create options for the workspace selector
	 *
	 * @return array Key is uid of workspace, value its label
	 */
	protected function createWorkspaceListForSelectOptions() {
		if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('workspaces')) {
			return array();
		}
		$workspaceArray = array();
		// Two meta entries: 'all' and 'live'
		$workspaceArray[-99] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('any', 'Belog');
		$workspaceArray[0] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('live', 'Belog');
		$workspaces = $this->objectManager->get('TYPO3\\CMS\\Belog\\Domain\\Repository\\WorkspaceRepository')->findAll();
		/** @var $workspace \TYPO3\CMS\Belog\Domain\Model\Workspace */
		foreach ($workspaces as $workspace) {
			$workspaceArray[$workspace->getUid()] = $workspace->getUid() . ': ' . $workspace->getTitle();
		}
		return $workspaceArray;
	}

	/**
	 * If the user is in a workspace different than LIVE,
	 * we force to show only log entries from the selected workspace,
	 * and the workspace selector is not shown.
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @return void
	 */
	protected function forceWorkspaceSelectionIfInWorkspace(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint) {
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$constraint->setWorkspaceUid($GLOBALS['BE_USER']->workspace);
			$this->view->assign('showWorkspaceSelector', FALSE);
		} else {
			$this->view->assign('showWorkspaceSelector', TRUE);
		}
	}

	/**
	 * Create options for the 'depth of page levels' selector.
	 * This is shown if the module is displayed in page -> info
	 *
	 * @return array Key is depth identifier (1 = One level), value the localized select option label
	 */
	protected function createPageDepthOptions() {
		$options = array(
			0 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_mod_web_info.xlf:depth_0', 'lang'),
			1 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_mod_web_info.xlf:depth_1', 'lang'),
			2 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_mod_web_info.xlf:depth_2', 'lang'),
			3 => \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_mod_web_info.xlf:depth_3', 'lang')
		);
		return $options;
	}

	/**
	 * Calculate the start- and end timestamp from the different time selector options
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @return void
	 */
	protected function setStartAndEndTimeFromTimeSelector(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint) {
		$startTime = 0;
		$endTime = $GLOBALS['EXEC_TIME'];
		// @TODO: Refactor this construct
		switch ($constraint->getTimeFrame()) {
			case self::TIMEFRAME_THISWEEK:
				// This week
				$week = (date('w') ?: 7) - 1;
				$startTime = mktime(0, 0, 0) - $week * 3600 * 24;
				break;
			case self::TIMEFRAME_LASTWEEK:
				// Last week
				$week = (date('w') ?: 7) - 1;
				$startTime = mktime(0, 0, 0) - ($week + 7) * 3600 * 24;
				$endTime = mktime(0, 0, 0) - $week * 3600 * 24;
				break;
			case self::TIMEFRAME_LASTSEVENDAYS:
				// Last 7 days
				$startTime = mktime(0, 0, 0) - 7 * 3600 * 24;
				break;
			case self::TIMEFRAME_THISMONTH:
				// This month
				$startTime = mktime(0, 0, 0, date('m'), 1);
				break;
			case self::TIMEFRAME_LASTMONTH:
				// Last month
				$startTime = mktime(0, 0, 0, date('m') - 1, 1);
				$endTime = mktime(0, 0, 0, date('m'), 1);
				break;
			case self::TIMEFRAME_LAST31DAYS:
				// Last 31 days
				$startTime = mktime(0, 0, 0) - 31 * 3600 * 24;
				break;
			case self::TIMEFRAME_CUSTOM:
				if ($constraint->getManualDateStart() instanceof \DateTime) {
					$startTime = $constraint->getManualDateStart()->format('U');
					if ($constraint->getManualDateStop() instanceof \DateTime) {
						$manualEndTime = $constraint->getManualDateStop()->format('U');
						if ($manualEndTime > $startTime) {
							$endTime = $manualEndTime;
						}
					} else {
						$endTime = $GLOBALS['EXEC_TIME'];
					}
				}
				break;
			default:

		}
		$constraint->setStartTimestamp($startTime);
		$constraint->setEndTimestamp($endTime);
	}

}

?>