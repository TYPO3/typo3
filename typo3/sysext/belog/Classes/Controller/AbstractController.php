<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Christian Kuhn <lolli@schwarzbu.ch>
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
 * @package TYPO3
 * @subpackage belog
 */
abstract class Tx_Belog_Controller_AbstractController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @param boolean Wether or not the plugin is running in page context (sub module of Web->Info)
	 */
	protected $pageContext = FALSE;

	/**
	 * @var int The page id in page context
	 */
	protected $pageId = 0;

	/**
	 * @var Tx_Belog_Domain_Repository_SysLogRepository
	 */
	protected $sysLogRepository;

	/**
	 * Inject sys log repository
	 *
	 * @param Tx_Belog_Domain_Repository_SysLogRepository $sysLogRepository
	 * @return void
	 */
	public function injectSysLogRepository(Tx_Belog_Domain_Repository_SysLogRepository $sysLogRepository) {
		$this->sysLogRepository = $sysLogRepository;
	}

	/**
	 * Initialize index action
	 *
	 * @return void
	 */
	public function initializeIndexAction() {
		if (!isset($this->settings['dateFormat'])) {
			$this->settings['dateFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'];
		}
		if (!isset($this->settings['timeFormat'])) {
			$this->settings['timeFormat'] = $GLOBALS['TYPO3_CONF_VARS']['SYS']['hhmm'];
		}

			// @TODO: The dateTime property mapper throws exceptions that can not be caught
			// if the property is not give in the expected format. This is documented with
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
	 * @param Tx_Belog_Domain_Model_Constraint $constraint
	 * @return void
	 */
	public function indexAction(Tx_Belog_Domain_Model_Constraint $constraint = NULL) {
			// Constraint object handling:
			// If there is none from GET, try to get it from BE user data, else create new
		if (!$constraint instanceof Tx_Belog_Domain_Model_Constraint) {
			$constraint = $this->getConstraintObjectFromBeUserData();
			if (!$constraint) {
				$constraint = $this->objectManager->get('Tx_Belog_Domain_Model_Constraint');
			}
		} else {
			$this->persistConstraintObjectInBeUserData($constraint);
		}
		$constraint->setPageContext($this->pageContext);
		$constraint->setPageId($this->pageId);
		$constraint = $this->setStartAndEndTimeFromTimeSelector($constraint);
		$constraint = $this->forceWorkspaceSelectionIfInWorkspace($constraint);

		$logEntries = $this->sysLogRepository->findByConstraint($constraint);
		$groupedLogEntries = $this->groupLogEntriesByPageAndDay($logEntries, $constraint->getGroupByPage());

		$this->view
			->assign('groupedLogEntries', $groupedLogEntries)
			->assign('constraint', $constraint)
			->assign('userGroupList', $this->createUserAndGroupListForSelectOptions())
			->assign('workspaceList', $this->createWorkspaceListForSelectOptions())
			->assign('pageDepthList', $this->createPageDepthOptions())
		;
	}

	/**
	 * Get module states (the constraint object) from user data
	 *
	 * @return mixed Valid Tx_Belog_Domain_Model_Constraint object, or FALSE
	 */
	protected function getConstraintObjectFromBeUserData() {
		$serializedConstraintObject = $GLOBALS['BE_USER']->getModuleData(get_class($this));
		$constraintObject = NULL;
		if (strlen($serializedConstraintObject) > 0) {
			$constraintObject = @unserialize($serializedConstraintObject);
		}
		if ($constraintObject instanceof Tx_Belog_Domain_Model_Constraint) {
			return $constraintObject;
		} else {
			return FALSE;
		}
	}

	/**
	 * Save current constraint object in be user settings (uC)
	 *
	 * @param Tx_Belog_Domain_Model_Constraint $constraint
	 * @return void
	 */
	protected function persistConstraintObjectInBeUserData(Tx_Belog_Domain_Model_Constraint $constraint) {
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
	 * @param $logEntries Tx_Extbase_Persistence_QueryResult<Tx_Belog_Domain_Model_LogEntry> $logEntries
	 * @param boolean $groupByPage Wether or not log entries should be grouped by page
	 * @return array
	 */
	protected function groupLogEntriesByPageAndDay(Tx_Extbase_Persistence_QueryResult $logEntries, $groupByPage = FALSE) {
		$targetStructure = array();

			/** @var $entry Tx_Belog_Domain_Model_SysLog */
		foreach ($logEntries as $entry) {
				// Create page split list or flat list
			if ($groupByPage) {
				$pid = $entry->getEventPid();
			} else {
				$pid = -1;
			}

				// Create array if it is not defined yes
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
	 * @param Tx_Extbase_Property_PropertyMappingConfiguration $propertyMapperDate
	 * @return void
	 */
	protected function configurePropertyMapperForDateTimeFormat(Tx_Extbase_Property_PropertyMappingConfiguration $propertyMapperDate) {
		$propertyMapperDate->setTypeConverterOption(
			'Tx_Extbase_Property_TypeConverter_DateTimeConverter',
			Tx_Extbase_Property_TypeConverter_DateTimeConverter::CONFIGURATION_DATE_FORMAT,
			$this->settings['dateFormat'] . ' ' . $this->settings['timeFormat']
		);
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
		$userGroupArray[0] = Tx_Extbase_Utility_Localization::translate('any', 'Belog');
		$userGroupArray[-1] = Tx_Extbase_Utility_Localization::translate('self', 'Belog');

			// List of groups, key is gr-'uid'
		$groups = t3lib_BEfunc::getGroupNames();
		foreach ($groups as $group) {
			$userGroupArray['gr-' . $group['uid']] = Tx_Extbase_Utility_Localization::translate('group', 'Belog') . ' ' . $group['title'];
		}

			// List of users, key is us-'uid'
		$users = t3lib_BEfunc::getUserNames();
		foreach ($users as $user) {
			$userGroupArray['us-' . $user['uid']] = Tx_Extbase_Utility_Localization::translate('user', 'Belog') . ' ' . $user['username'];
		}

		return $userGroupArray;
	}

	/**
	 * Create options for the workspace selector
	 *
	 * @return array Key is uid of workspace, value its label
	 */
	protected function createWorkspaceListForSelectOptions() {
		$workspaceArray = array();

		if (t3lib_extMgm::isLoaded('workspaces')) {
				// Two meta entries: 'all' and 'live'
			$workspaceArray[-99] = Tx_Extbase_Utility_Localization::translate('any', 'Belog');
			$workspaceArray[0] = Tx_Extbase_Utility_Localization::translate('live', 'Belog');

			$workspaces = $this->objectManager->get('Tx_Belog_Domain_Repository_WorkspaceRepository')->findAll();
				/** @var $workspace Tx_Belog_Domain_Model_Workspace */
			foreach ($workspaces as $workspace) {
				$workspaceArray[$workspace->getUid()] = $workspace->getUid() . ': ' . $workspace->getTitle();
			}
		}

		return $workspaceArray;
	}

	/**
	 * If the user is in a workspace different than LIVE,
	 * we force to show only log entries from the selected workspace,
	 * and the workspace selector is not shown.
	 *
	 * @param Tx_Belog_Domain_Model_Constraint $constraint
	 * @return Tx_Belog_Domain_Model_Constraint
	 */
	protected function forceWorkspaceSelectionIfInWorkspace(Tx_Belog_Domain_Model_Constraint $constraint) {
		if ($GLOBALS['BE_USER']->workspace !== 0) {
			$constraint->setWorkspace($GLOBALS['BE_USER']->workspace);
			$this->view->assign('showWorkspaceSelector', FALSE);
		} else {
			$this->view->assign('showWorkspaceSelector', TRUE);
		}
		return $constraint;
	}

	/**
	 * Create options for the 'depth of page levels' selector.
	 * This is shown if the module is displayed in page -> info
	 *
	 * @return array Key is depth identifier (1 = One level), value the localized select option label
	 */
	protected function createPageDepthOptions() {
		$options = array();

		$options[0] = Tx_Extbase_Utility_Localization::translate(
			'LLL:EXT:lang/locallang_mod_web_info.xlf:depth_0',
			'lang'
		);
		$options[1] = Tx_Extbase_Utility_Localization::translate(
			'LLL:EXT:lang/locallang_mod_web_info.xlf:depth_1',
			'lang'
		);
		$options[2] = Tx_Extbase_Utility_Localization::translate(
			'LLL:EXT:lang/locallang_mod_web_info.xlf:depth_2',
			'lang'
		);
		$options[3] = Tx_Extbase_Utility_Localization::translate(
			'LLL:EXT:lang/locallang_mod_web_info.xlf:depth_3',
			'lang'
		);

		return $options;
	}

	/**
	 * Calculate the start- and end timestamp from the different time selector options
	 *
	 * @param Tx_Belog_Domain_Model_Constraint $constraint
	 * @return Tx_Belog_Domain_Model_Constraint
	 */
	protected function setStartAndEndTimeFromTimeSelector(Tx_Belog_Domain_Model_Constraint $constraint) {
		$startTime = 0;
		$endTime = $GLOBALS['EXEC_TIME'];
		switch ($constraint->getTimeFrame()) {
			case 0:
					// This week
				$week = (date('w') ?: 7) - 1;
				$startTime = mktime(0, 0, 0) - $week * 3600 * 24;
			break;
			case 1:
					// Last week
				$week = (date('w') ?: 7) - 1;
				$startTime = mktime(0, 0, 0) - ($week + 7) * 3600 * 24;
				$endTime = mktime(0, 0, 0) - $week * 3600 * 24;
			break;
			case 2:
					// Last 7 days
				$startTime = mktime(0, 0, 0) - 7 * 3600 * 24;
			break;
			case 10:
					// This month
				$startTime = mktime(0, 0, 0, date('m'), 1);
			break;
			case 11:
					// Last month
				$startTime = mktime(0, 0, 0, date('m') - 1, 1);
				$endTime = mktime(0, 0, 0, date('m'), 1);
			break;
			case 12:
					// Last 31 days
				$startTime = mktime(0, 0, 0) - 31 * 3600 * 24;
			break;
			case 30:
				if ($constraint->getManualDateStart() instanceof DateTime) {
					$startTime = $constraint->getManualDateStart()->format('U');
					if ($constraint->getManualDateStop() instanceof DateTime) {
						$manualEndTime = $constraint->getManualDateStop()->format('U');
						if ($manualEndTime > $startTime) {
							$endTime = $manualEndTime;
						}
					} else {
						$endTime = $GLOBALS['EXEC_TIME'];
					}
				}
			break;
		}
		$constraint->setStartTimestamp($startTime);
		$constraint->setEndTimestamp($endTime);

		return $constraint;
	}
}
?>