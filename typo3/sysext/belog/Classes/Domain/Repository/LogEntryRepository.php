<?php
namespace TYPO3\CMS\Belog\Domain\Repository;

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
 * Sys log entry repository
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class LogEntryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository {

	/**
	 * Backend users, with UID as key
	 *
	 * @var array
	 */
	protected $beUserList = array();

	/**
	 * Initialize some local variables to be used during creation of objects
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->beUserList = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
		/** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
		$defaultQuerySettings = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Persistence\\Generic\\QuerySettingsInterface');
		$defaultQuerySettings->setRespectStoragePage(FALSE);
		$this->setDefaultQuerySettings($defaultQuerySettings);
	}

	/**
	 * Finds all log entries that match all given constraints.
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\TYPO3\CMS\Belog\Domain\Model\LogEntry>
	 */
	public function findByConstraint(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint) {
		$query = $this->createQuery();
		$queryConstraints = $this->createQueryConstraints($query, $constraint);
		if (!empty($queryConstraints)) {
			$query->matching($query->logicalAnd($queryConstraints));
		}
		$query->setOrderings(array('uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING));
		$query->setLimit($constraint->getNumber());
		return $query->execute();
	}

	/**
	 * Create an array of query constraints from constraint object
	 *
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @return array<\TYPO3\CMS\Extbase\Persistence\Generic\Qom\ConstraintInterface>
	 */
	protected function createQueryConstraints(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint) {
		$queryConstraints = array();
		// User / group handling
		$this->addUsersAndGroupsToQueryConstraints($constraint, $query, $queryConstraints);
		// Workspace
		if ($constraint->getWorkspaceUid() != \TYPO3\CMS\Belog\Domain\Model\Workspace::UID_ANY_WORKSPACE) {
			$queryConstraints[] = $query->equals('workspace', $constraint->getWorkspaceUid());
		}
		// Action (type):
		if ($constraint->getAction() > 0) {
			$queryConstraints[] = $query->equals('type', $constraint->getAction());
		} elseif ($constraint->getAction() == -1) {
			$queryConstraints[] = $query->in('error', array(-1,1,2,3));
		}
		// Start / endtime handling: The timestamp calculation was already done
		// in the controller, since we need those calculated values in the view as well.
		$queryConstraints[] = $query->greaterThanOrEqual('tstamp', $constraint->getStartTimestamp());
		$queryConstraints[] = $query->lessThan('tstamp', $constraint->getEndTimestamp());
		// Page and level constraint if in page context
		$this->addPageTreeConstraintsToQuery($constraint, $query, $queryConstraints);
		return $queryConstraints;
	}

	/**
	 * Adds constraints for the page(s) to the query; this could be one single page or a whole subtree beneath a given
	 * page.
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @param array &$queryConstraints the query constraints to add to, will be modified
	 * @return void
	 */
	protected function addPageTreeConstraintsToQuery(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint, \TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array &$queryConstraints) {
		if (!$constraint->getIsInPageContext()) {
			return;
		}
		$pageIds = array();
		// Check if we should get a whole tree of pages and not only a single page
		if ($constraint->getDepth() > 0) {
			/** @var $pageTree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
			$pageTree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Tree\\View\\PageTreeView');
			$pageTree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
			$pageTree->makeHTML = 0;
			$pageTree->fieldArray = array('uid');
			$pageTree->getTree($constraint->getPageId(), $constraint->getDepth());
			$pageIds = $pageTree->ids;
		}
		$pageIds[] = $constraint->getPageId();
		$queryConstraints[] = $query->in('eventPid', $pageIds);
	}

	/**
	 * Adds users and groups to the query constraints.
	 *
	 * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
	 * @param \TYPO3\CMS\Extbase\Persistence\QueryInterface $query
	 * @param array &$queryConstraints the query constraints to add to, will be modified
	 * @return void
	 */
	protected function addUsersAndGroupsToQueryConstraints(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint, \TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array &$queryConstraints) {
		$userOrGroup = $constraint->getUserOrGroup();
		if ($userOrGroup === '') {
			return;
		}
		// Constraint for a group
		if (substr($userOrGroup, 0, 3) === 'gr-') {
			$groupId = intval(substr($userOrGroup, 3));
			$userIds = array();
			foreach ($this->beUserList as $userId => $userData) {
				if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($userData['usergroup_cached_list'], $groupId)) {
					$userIds[] = $userId;
				}
			}
			if (!empty($userIds)) {
				$queryConstraints[] = $query->in('userid', $userIds);
			} else {
				// If there are no group members -> use -1 as constraint to not find anything
				$queryConstraints[] = $query->in('userid', array(-1));
			}
		} elseif (substr($userOrGroup, 0, 3) === 'us-') {
			$queryConstraints[] = $query->equals('userid', intval(substr($userOrGroup, 3)));
		} elseif ($userOrGroup === '-1') {
			$queryConstraints[] = $query->equals('userid', intval($GLOBALS['BE_USER']->user['uid']));
		}
	}

}

?>