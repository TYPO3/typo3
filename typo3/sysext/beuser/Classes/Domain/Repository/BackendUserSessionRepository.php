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
use TYPO3\CMS\Core\Database\DatabaseConnection;
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
        return $this->getDatabaseConnection()->exec_SELECTgetRows(
            'ses_id AS id, ses_userid, ses_iplock AS ip, ses_tstamp AS timestamp',
            'be_sessions',
            '1=1'
        );
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
        $sessions = [];
        $db = $this->getDatabaseConnection();
        $res = $db->exec_SELECTquery(
            'ses_id AS id, ses_iplock AS ip, ses_tstamp AS timestamp',
            'be_sessions',
            'ses_userid = ' . (int)$backendUser->getUid(),
            '',
            'ses_tstamp ASC'
        );
        while ($row = $db->sql_fetch_assoc($res)) {
            $sessions[] = [
                'id' => $row['id'],
                'ip' => $row['ip'],
                'timestamp' => $row['timestamp']
            ];
        }
        $db->sql_free_result($res);
        return $sessions;
    }

    /**
     * Update current session to move back to the original user.
     *
     * @param AbstractUserAuthentication $authentication
     * @return void
     */
    public function switchBackToOriginalUser(AbstractUserAuthentication $authentication)
    {
        $updateData = [
            'ses_userid' => $authentication->user['ses_backuserid'],
            'ses_backuserid' => 0,
        ];
        $db = $this->getDatabaseConnection();
        $db->exec_UPDATEquery(
            'be_sessions',
            'ses_id = ' . $db->fullQuoteStr($GLOBALS['BE_USER']->id, 'be_sessions') .
                ' AND ses_name = ' . $db->fullQuoteStr(BackendUserAuthentication::getCookieName(), 'be_sessions') .
                ' AND ses_userid=' . (int)$GLOBALS['BE_USER']->user['uid'], $updateData
        );
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
