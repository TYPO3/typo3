<?php
namespace TYPO3\CMS\Belog\Domain\Repository;

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

/**
 * Sys log entry repository
 */
class LogEntryRepository extends \TYPO3\CMS\Extbase\Persistence\Repository
{
    /**
     * Backend users, with UID as key
     *
     * @var array
     */
    protected $beUserList = [];

    /**
     * Initialize some local variables to be used during creation of objects
     *
     * @return void
     */
    public function initializeObject()
    {
        $this->beUserList = \TYPO3\CMS\Backend\Utility\BackendUtility::getUserNames();
        /** @var $defaultQuerySettings \TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface */
        $defaultQuerySettings = $this->objectManager->get(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class);
        $defaultQuerySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($defaultQuerySettings);
    }

    /**
     * Finds all log entries that match all given constraints.
     *
     * @param \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint
     * @return \TYPO3\CMS\Extbase\Persistence\QueryResultInterface<\TYPO3\CMS\Belog\Domain\Model\LogEntry>
     */
    public function findByConstraint(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint)
    {
        $query = $this->createQuery();
        $queryConstraints = $this->createQueryConstraints($query, $constraint);
        if (!empty($queryConstraints)) {
            $query->matching($query->logicalAnd($queryConstraints));
        }
        $query->setOrderings(['uid' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING]);
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
    protected function createQueryConstraints(\TYPO3\CMS\Extbase\Persistence\QueryInterface $query, \TYPO3\CMS\Belog\Domain\Model\Constraint $constraint)
    {
        $queryConstraints = [];
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
            $queryConstraints[] = $query->in('error', [-1, 1, 2, 3]);
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
    protected function addPageTreeConstraintsToQuery(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint, \TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array &$queryConstraints)
    {
        if (!$constraint->getIsInPageContext()) {
            return;
        }
        $pageIds = [];
        // Check if we should get a whole tree of pages and not only a single page
        if ($constraint->getDepth() > 0) {
            /** @var $pageTree \TYPO3\CMS\Backend\Tree\View\PageTreeView */
            $pageTree = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Tree\View\PageTreeView::class);
            $pageTree->init('AND ' . $GLOBALS['BE_USER']->getPagePermsClause(1));
            $pageTree->makeHTML = 0;
            $pageTree->fieldArray = ['uid'];
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
    protected function addUsersAndGroupsToQueryConstraints(\TYPO3\CMS\Belog\Domain\Model\Constraint $constraint, \TYPO3\CMS\Extbase\Persistence\QueryInterface $query, array &$queryConstraints)
    {
        $userOrGroup = $constraint->getUserOrGroup();
        if ($userOrGroup === '') {
            return;
        }
        // Constraint for a group
        if (substr($userOrGroup, 0, 3) === 'gr-') {
            $groupId = (int)substr($userOrGroup, 3);
            $userIds = [];
            foreach ($this->beUserList as $userId => $userData) {
                if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList($userData['usergroup_cached_list'], $groupId)) {
                    $userIds[] = $userId;
                }
            }
            if (!empty($userIds)) {
                $queryConstraints[] = $query->in('userid', $userIds);
            } else {
                // If there are no group members -> use -1 as constraint to not find anything
                $queryConstraints[] = $query->in('userid', [-1]);
            }
        } elseif (substr($userOrGroup, 0, 3) === 'us-') {
            $queryConstraints[] = $query->equals('userid', (int)substr($userOrGroup, 3));
        } elseif ($userOrGroup === '-1') {
            $queryConstraints[] = $query->equals('userid', (int)$GLOBALS['BE_USER']->user['uid']);
        }
    }
}
