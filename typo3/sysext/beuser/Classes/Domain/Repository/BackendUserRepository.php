<?php
namespace TYPO3\CMS\Beuser\Domain\Repository;

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

use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Domain\Repository\BackendUserGroupRepository;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Repository for \TYPO3\CMS\Beuser\Domain\Model\BackendUser
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserRepository extends BackendUserGroupRepository
{
    /**
     * Finds Backend Users on a given list of uids
     *
     * @param array $uidList
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult<\TYPO3\CMS\Beuser\Domain\Model\BackendUser>
     */
    public function findByUidList(array $uidList)
    {
        $query = $this->createQuery();
        $query->matching($query->in('uid', array_map('intval', $uidList)));
        /** @var QueryResult $result */
        $result = $query->execute();
        return $result;
    }

    /**
     * Find Backend Users matching to Demand object properties
     *
     * @param Demand $demand
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult<\TYPO3\CMS\Beuser\Domain\Model\BackendUser>
     */
    public function findDemanded(Demand $demand)
    {
        $constraints = [];
        $query = $this->createQuery();
        // Find invisible as well, but not deleted
        $constraints[] = $query->equals('deleted', 0);
        $query->setOrderings(['userName' => QueryInterface::ORDER_ASCENDING]);
        // Username
        if ($demand->getUserName() !== '') {
            $searchConstraints = [];
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
            foreach (['userName', 'realName'] as $field) {
                $searchConstraints[] = $query->like(
                    $field,
                    '%' . $queryBuilder->escapeLikeWildcards($demand->getUserName()) . '%'
                );
            }
            if (MathUtility::canBeInterpretedAsInteger($demand->getUserName())) {
                $searchConstraints[] = $query->equals('uid', (int)$demand->getUserName());
            }
            $constraints[] = $query->logicalOr($searchConstraints);
        }
        // Only display admin users
        if ($demand->getUserType() == Demand::USERTYPE_ADMINONLY) {
            $constraints[] = $query->equals('admin', 1);
        }
        // Only display non-admin users
        if ($demand->getUserType() == Demand::USERTYPE_USERONLY) {
            $constraints[] = $query->equals('admin', 0);
        }
        // Only display active users
        if ($demand->getStatus() == Demand::STATUS_ACTIVE) {
            $constraints[] = $query->equals('disable', 0);
        }
        // Only display in-active users
        if ($demand->getStatus() == Demand::STATUS_INACTIVE) {
            $constraints[] = $query->logicalOr($query->equals('disable', 1));
        }
        // Not logged in before
        if ($demand->getLogins() == Demand::LOGIN_NONE) {
            $constraints[] = $query->equals('lastlogin', 0);
        }
        // At least one login
        if ($demand->getLogins() == Demand::LOGIN_SOME) {
            $constraints[] = $query->logicalNot($query->equals('lastlogin', 0));
        }
        // In backend user group
        // @TODO: Refactor for real n:m relations
        if ($demand->getBackendUserGroup()) {
            $constraints[] = $query->logicalOr([
                $query->equals('usergroup', (int)$demand->getBackendUserGroup()),
                $query->like('usergroup', (int)$demand->getBackendUserGroup() . ',%'),
                $query->like('usergroup', '%,' . (int)$demand->getBackendUserGroup()),
                $query->like('usergroup', '%,' . (int)$demand->getBackendUserGroup() . ',%')
            ]);
        }
        $query->matching($query->logicalAnd($constraints));
        /** @var QueryResult $result */
        $result = $query->execute();
        return $result;
    }

    /**
     * Find Backend Users currently online
     *
     * @return \TYPO3\CMS\Extbase\Persistence\Generic\QueryResult<\TYPO3\CMS\Beuser\Domain\Model\BackendUser>
     */
    public function findOnline()
    {
        $uids = [];
        foreach ($this->getSessionBackend()->getAll() as $sessionRecord) {
            if (isset($sessionRecord['ses_userid']) && !in_array($sessionRecord['ses_userid'], $uids, true)) {
                $uids[] = $sessionRecord['ses_userid'];
            }
        }

        $query = $this->createQuery();
        $query->matching($query->in('uid', $uids));
        /** @var QueryResult $result */
        $result = $query->execute();
        return $result;
    }

    /**
     * Overwrite createQuery to don't respect enable fields
     *
     * @return QueryInterface
     */
    public function createQuery()
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        $query->getQuerySettings()->setIncludeDeleted(true);
        return $query;
    }

    /**
     * @return SessionBackendInterface
     */
    protected function getSessionBackend()
    {
        $loginType = $this->getBackendUserAuthentication()->getLoginType();
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend($loginType);
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
