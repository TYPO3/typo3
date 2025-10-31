<?php

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

namespace TYPO3\CMS\Beuser\Domain\Repository;

use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Beuser\Domain\Model\Demand;
use TYPO3\CMS\Beuser\Event\AfterBackendUserListConstraintsAssembledFromDemandEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Persistence\Generic\QueryResult;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for \TYPO3\CMS\Beuser\Domain\Model\BackendUser
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 * @extends Repository<BackendUser>
 */
class BackendUserRepository extends Repository
{
    /**
     * Finds Backend Users on a given list of uids
     */
    public function findByUidList(array $uidList): QueryResult
    {
        $query = $this->createQuery();
        $query->matching($query->in('uid', array_map(intval(...), $uidList)));
        /** @var QueryResult $result */
        $result = $query->execute();
        return $result;
    }

    /**
     * Find Backend Users matching to Demand object properties
     */
    public function findDemanded(Demand $demand): QueryResult
    {
        $constraints = [];
        $query = $this->createQuery();
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
            if (count($searchConstraints) === 1) {
                $constraints[] = reset($searchConstraints);
            } else {
                $constraints[] = $query->logicalOr(...$searchConstraints);
            }
        }

        switch ($demand->getUserType()) {
            case Demand::USERTYPE_ADMINONLY:
                // Only display admin users
                $constraints[] = $query->equals('admin', 1);
                break;
            case Demand::USERTYPE_USERONLY:
                // Only display non-admin users
                $constraints[] = $query->equals('admin', 0);
                break;
        }

        switch ($demand->getStatus()) {
            case Demand::STATUS_ACTIVE:
                // Only display active users
                $constraints[] = $query->equals('disable', 0);
                break;
            case Demand::STATUS_INACTIVE:
                // Only display in-active users
                $constraints[] = $query->equals('disable', 1);
                break;
        }

        switch ($demand->getLogins()) {
            case Demand::LOGIN_NONE:
                // Not logged in before
                $constraints[] = $query->equals('lastlogin', 0);
                break;
            case Demand::LOGIN_SOME:
                // At least one login
                $constraints[] = $query->logicalNot($query->equals('lastlogin', 0));
                break;

            case Demand::LOGIN_CURRENT:
                // Currently logged-in users
                $sessionTimeout = (int)($GLOBALS['TYPO3_CONF_VARS']['BE']['sessionTimeout'] ?? 28800);
                $constraints[] = $query->greaterThanOrEqual('lastlogin', time() - $sessionTimeout);
        }

        // In backend user group
        if ($demand->getBackendUserGroup()) {
            $constraints[] = $query->logicalOr(
                $query->equals('usergroup', $demand->getBackendUserGroup()),
                $query->like('usergroup', $demand->getBackendUserGroup() . ',%'),
                $query->like('usergroup', '%,' . $demand->getBackendUserGroup()),
                $query->like('usergroup', '%,' . $demand->getBackendUserGroup() . ',%'),
            );
        }
        $constraints = $this->eventDispatcher->dispatch(
            new AfterBackendUserListConstraintsAssembledFromDemandEvent(
                $demand,
                $query,
                $constraints
            )
        )->constraints;
        $query->matching($query->logicalAnd(...$constraints));

        /** @var QueryResult $result */
        $result = $query->execute();
        return $result;
    }

    /**
     * Find Backend Users currently online
     */
    public function findOnline(): QueryResult
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
     */
    public function createQuery(): QueryInterface
    {
        $query = parent::createQuery();
        $query->getQuerySettings()->setIgnoreEnableFields(true);
        return $query;
    }

    protected function getSessionBackend(): SessionBackendInterface
    {
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend('BE');
    }
}
