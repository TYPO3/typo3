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
 * Sys log entry repository
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 * @package TYPO3
 * @subpackage belog
 */
class Tx_Belog_Domain_Repository_SysLogRepository extends Tx_Extbase_Persistence_Repository {

	/**
	 * @var array List of backend users, with uid as key
	 */
	protected $beUserList;

	/**
	 * Initialize some local variables to be used during creation of objects
	 */
	public function initializeObject() {
		$this->beUserList = t3lib_BEfunc::getUserNames();
	}

	/**
	 * Find log entries that match given constraints
	 *
	 * @param Tx_Belog_Domain_Model_Constraint $constraint
	 * @return Tx_Extbase_Persistence_QueryResult<Tx_Belog_Domain_Model_LogEntry>
	 */
	public function findByConstraint(Tx_Belog_Domain_Model_Constraint $constraint) {
		$query = $this->createQuery();

		$query->getQuerySettings()->setRespectStoragePage(FALSE);
		$queryConstraints = $this->createQueryConstraints($query, $constraint);
		if (!empty($queryConstraints)) {
			$query->matching(
				$query->logicalAnd($queryConstraints)
			);
		}
		$query->setOrderings(array('uid' => Tx_Extbase_Persistence_QueryInterface::ORDER_DESCENDING));
		$query->setLimit((int) $constraint->getNumber());

		return $query->execute();
	}

	/**
	 * Create an array of query constraints from constraint object
	 *
	 * @param Tx_Extbase_Persistence_QueryInterface $query
	 * @param Tx_Belog_Domain_Model_Constraint $constraint
	 * @return array<Tx_Extbase_Persistence_QOM_Constraint>
	 */
	protected function createQueryConstraints(Tx_Extbase_Persistence_QueryInterface $query, Tx_Belog_Domain_Model_Constraint $constraint) {
		$queryConstraints = array();

			// User / group handling
		if (strlen($constraint->getUser()) > 0) {
			if (substr($constraint->getUser(), 0, 3) === 'gr-') {
				$groupId = (int)substr($constraint->getUser(), 3);
				$userIds = array();
				foreach ($this->beUserList as $userId => $userData) {
					if (t3lib_div::inList($userData['usergroup_cached_list'], $groupId)) {
						$userIds[] = $userId;
					}
				}
				if (count($userIds) > 0) {
					$queryConstraints[] = $query->in('userid', $userIds);
				} else {
						// If there are no group members -> use -1 as constraint to not find anything
					$queryConstraints[] = $query->in('userid', array(-1));
				}
			} elseif (substr($constraint->getUser(), 0, 3) === 'us-') {
				$queryConstraints[] = $query->equals('userid', substr($constraint->getUser(), 3));
			} elseif ($constraint->getUser() === '-1') {
				$queryConstraints[] = $query->equals('userid', intval($GLOBALS['BE_USER']->user['uid']));
			}
		}

			// Workspace
		if ($constraint->getWorkspace() != -99) {
			$queryConstraints[] = $query->equals('workspace', $constraint->getWorkspace());
		}

			// Action (type):
		if ($constraint->getAction() > 0) {
			$queryConstraints[] = $query->equals('type', $constraint->getAction());
		} elseif ($constraint->getAction() == -1) {
			$queryConstraints[] = $query->equals('error', 0);
		}

			// Start / endtime handling: The timestamp calculation was already done
			// in the controller, since we need those calculated values in the view as well.
		$queryConstraints[] = $query->greaterThanOrEqual('tstamp', $constraint->getStartTimestamp());
		$queryConstraints[] = $query->lessThan('tstamp', $constraint->getEndTimestamp());

			// Page and level constraint if in page context
		if ($constraint->getPageContext() === TRUE) {
				/** @var $pageTree t3lib_pageTree */
			$pageTree = t3lib_div::makeInstance('t3lib_pageTree');
			$pageTree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
			$pageTree->makeHTML = 0;
			$pageTree->fieldArray = array('uid');
			$pageIds = array();
			if ($constraint->getDepth() > 0) {
				$pageTree->getTree($constraint->getPageId(), $constraint->getDepth());
				$pageIds = $pageTree->ids;
			}
			$pageIds[] = $constraint->getPageId();
			$queryConstraints[] = $query->in('event_pid', $pageIds);
		}

		return $queryConstraints;
	}
}
?>