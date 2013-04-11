<?php
namespace TYPO3\CMS\Backend\Configuration\TypoScript\ConditionMatching;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Oliver Hader <oliver@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Matching TypoScript conditions for backend disposal.
 *
 * Used with the TypoScript parser.
 * Matches browserinfo, IPnumbers for use with templates
 *
 * @author 	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class ConditionMatcher extends \TYPO3\CMS\Core\Configuration\TypoScript\ConditionMatching\AbstractConditionMatcher {

	/**
	 * Constructor for this class
	 *
	 * @return void
	 */
	public function __construct() {

	}

	/**
	 * Evaluates a TypoScript condition given as input, eg. "[browser=net][...(other conditions)...]"
	 *
	 * @param string $string The condition to match against its criterias.
	 * @return boolean Whether the condition matched
	 * @see \TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser::parse()
	 */
	protected function evaluateCondition($string) {
		list($key, $value) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('=', $string, FALSE, 2);
		$result = parent::evaluateConditionCommon($key, $value);
		if (is_bool($result)) {
			return $result;
		} else {
			switch ($key) {
			case 'usergroup':
				$groupList = $this->getGroupList();
				$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
				foreach ($values as $test) {
					if ($test == '*' || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($groupList, $test)) {
						return TRUE;
					}
				}
				break;
			case 'adminUser':
				if ($this->isUserLoggedIn()) {
					$result = !((bool) $value xor $this->isAdminUser());
					return $result;
				}
				break;
			case 'treeLevel':
				$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
				$treeLevel = count($this->rootline) - 1;
				// If a new page is being edited or saved the treeLevel is higher by one:
				if ($this->isNewPageWithPageId($this->pageId)) {
					$treeLevel++;
				}
				foreach ($values as $test) {
					if ($test == $treeLevel) {
						return TRUE;
					}
				}
				break;
			case 'PIDupinRootline':

			case 'PIDinRootline':
				$values = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $value, TRUE);
				if ($key == 'PIDinRootline' || !in_array($this->pageId, $values) || $this->isNewPageWithPageId($this->pageId)) {
					foreach ($values as $test) {
						foreach ($this->rootline as $rl_dat) {
							if ($rl_dat['uid'] == $test) {
								return TRUE;
							}
						}
					}
				}
				break;
			}
		}
		return FALSE;
	}

	/**
	 * Returns GP / ENV vars
	 *
	 * @param string $var Identifier
	 * @return mixed The value of the variable pointed to.
	 * @access private
	 */
	protected function getVariable($var) {
		$vars = explode(':', $var, 2);
		$val = parent::getVariableCommon($vars);
		return $val;
	}

	/**
	 * Get the usergroup list of the current user.
	 *
	 * @return string The usergroup list of the current user
	 */
	protected function getGroupList() {
		$groupList = $GLOBALS['BE_USER']->groupList;
		return $groupList;
	}

	/**
	 * Tries to determine the ID of the page currently processed.
	 * When User/Group TS-Config is parsed when no specific page is handled
	 * (i.e. in the Extension Manager, etc.) this function will return "0", so that
	 * the accordant conditions (e.g. PIDinRootline) will return "FALSE"
	 *
	 * @return integer The determined page id or otherwise 0
	 */
	protected function determinePageId() {
		$pageId = 0;
		$editStatement = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('edit');
		$commandStatement = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		// Determine id from module that was called with an id:
		if ($id = intval(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'))) {
			$pageId = $id;
		} elseif (is_array($editStatement)) {
			list($table, $uidAndAction) = each($editStatement);
			list($uid, $action) = each($uidAndAction);
			if ($action === 'edit') {
				$pageId = $this->getPageIdByRecord($table, $uid);
			} elseif ($action === 'new') {
				$pageId = $this->getPageIdByRecord($table, $uid, TRUE);
			}
		} elseif (is_array($commandStatement)) {
			list($table, $uidActionAndTarget) = each($commandStatement);
			list($uid, $actionAndTarget) = each($uidActionAndTarget);
			list($action, $target) = each($actionAndTarget);
			if ($action === 'delete') {
				$pageId = $this->getPageIdByRecord($table, $uid);
			} elseif ($action === 'copy' || $action === 'move') {
				$pageId = $this->getPageIdByRecord($table, $target, TRUE);
			}
		}
		return $pageId;
	}

	/**
	 * Gets the properties for the current page.
	 *
	 * @return array The properties for the current page.
	 */
	protected function getPage() {
		$pageId = isset($this->pageId) ? $this->pageId : $this->determinePageId();
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $pageId);
	}

	/**
	 * Gets the page id by a record.
	 *
	 * @param string $table Name of the table
	 * @param integer $id Id of the accordant record
	 * @param boolean $ignoreTable Whether to ignore the page, if TRUE a positive
	 * @return integer Id of the page the record is persisted on
	 */
	protected function getPageIdByRecord($table, $id, $ignoreTable = FALSE) {
		$pageId = 0;
		$id = (int) $id;
		if ($table && $id) {
			if (($ignoreTable || $table === 'pages') && $id >= 0) {
				$pageId = $id;
			} else {
				$record = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($table, abs($id), '*', '', FALSE);
				$pageId = $record['pid'];
			}
		}
		return $pageId;
	}

	/**
	 * Determine if record of table 'pages' with the given $pid is currently created in TCEforms.
	 * This information is required for conditions in BE for PIDupinRootline.
	 *
	 * @param integer $pid The pid the check for as parent page
	 * @return boolean TRUE if the is currently a new page record being edited with $pid as uid of the parent page
	 */
	protected function isNewPageWithPageId($pageId) {
		if (isset($GLOBALS['SOBE']) && $GLOBALS['SOBE'] instanceof \TYPO3\CMS\Backend\Controller\EditDocumentController) {
			$pageId = intval($pageId);
			$elementsData = $GLOBALS['SOBE']->elementsData;
			$data = $GLOBALS['SOBE']->data;
			// If saving a new page record:
			if (is_array($data) && isset($data['pages']) && is_array($data['pages'])) {
				foreach ($data['pages'] as $uid => $fields) {
					if (strpos($uid, 'NEW') === 0 && $fields['pid'] == $pageId) {
						return TRUE;
					}
				}
			}
			// If editing a new page record (not saved yet):
			if (is_array($elementsData)) {
				foreach ($elementsData as $element) {
					if ($element['cmd'] == 'new' && $element['table'] == 'pages') {
						if ($element['pid'] < 0) {
							$pageRecord = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', abs($element['pid']), 'pid');
							$element['pid'] = $pageRecord['pid'];
						}
						if ($element['pid'] == $pageId) {
							return TRUE;
						}
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Determines the rootline for the current page.
	 *
	 * @return array The rootline for the current page.
	 */
	protected function determineRootline() {
		$pageId = isset($this->pageId) ? $this->pageId : $this->determinePageId();
		$rootline = \TYPO3\CMS\Backend\Utility\BackendUtility::BEgetRootLine($pageId, '', TRUE);
		return $rootline;
	}

	/**
	 * Get the id of the current user.
	 *
	 * @return integer The id of the current user
	 */
	protected function getUserId() {
		$userId = $GLOBALS['BE_USER']->user['uid'];
		return $userId;
	}

	/**
	 * Determines if a user is logged in.
	 *
	 * @return boolean Determines if a user is logged in
	 */
	protected function isUserLoggedIn() {
		$userLoggedIn = FALSE;
		if ($GLOBALS['BE_USER']->user['uid']) {
			$userLoggedIn = TRUE;
		}
		return $userLoggedIn;
	}

	/**
	 * Determines whether the current user is admin.
	 *
	 * @return boolean Whether the current user is admin
	 */
	protected function isAdminUser() {
		$isAdminUser = FALSE;
		if ($GLOBALS['BE_USER']->user['admin']) {
			$isAdminUser = TRUE;
		}
		return $isAdminUser;
	}

	/**
	 * Set/write a log message.
	 *
	 * @param string $message The log message to set/write
	 * @return void
	 */
	protected function log($message) {
		if (is_object($GLOBALS['BE_USER'])) {
			$GLOBALS['BE_USER']->writelog(3, 0, 1, 0, $message, array());
		}
	}

}


?>