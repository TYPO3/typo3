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
use TYPO3\CMS\Beuser\Domain\Model\BackendUser;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for \TYPO3\CMS\Extbase\Domain\Model\BackendUser
 */
class BackendUserSessionRepository extends Repository
{
    /**
     * Find all active sessions for all backend users
     *
     * @return array|NULL Array of rows, or NULL in case of SQL error
     */
    public function findAllActive()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_sessions');
        return $queryBuilder
            ->select('ses_id AS id', 'ses_userid', 'ses_iplock AS ip', 'ses_tstamp AS timestamp')
            ->from('be_sessions')
            ->execute()
            ->fetchAll();
    }

    /**
     * Find Sessions for specific BackendUser
     * Delivers an Array, not an ObjectStorage!
     *
     * @param BackendUser $backendUser
     * @return array
     */
    public function findByBackendUser(BackendUser $backendUser)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_sessions');
        return $queryBuilder
            ->select('ses_id AS id', 'ses_iplock AS ip', 'ses_tstamp AS timestamp')
            ->from('be_sessions')
            ->where($queryBuilder->expr()->eq('ses_userid', (int)$backendUser->getUid()))
            ->orderBy('ses_tstamp', 'ASC')
            ->execute()
            ->fetchAll();
    }

    /**
     * Update current session to move back to the original user.
     *
     * @param AbstractUserAuthentication $authentication
     * @return void
     */
    public function switchBackToOriginalUser(AbstractUserAuthentication $authentication)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_sessions');
        $queryBuilder
            ->update('be_sessions')
            ->set('ses_userid', $authentication->user['ses_backuserid'])
            ->set('ses_backuserid', 0)
            ->where(
                $queryBuilder->expr()->eq('ses_id', $queryBuilder->createNamedParameter($GLOBALS['BE_USER']->id)),
                $queryBuilder->expr()->eq('ses_name', $queryBuilder->createNamedParameter(BackendUserAuthentication::getCookieName())),
                $queryBuilder->expr()->eq('ses_userid', (int)$GLOBALS['BE_USER']->user['uid'])
            )
            ->execute();
    }
}
