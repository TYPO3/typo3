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
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for \TYPO3\CMS\Extbase\Domain\Model\BackendUser
 * @internal This class is a TYPO3 Backend implementation and is not considered part of the Public TYPO3 API.
 */
class BackendUserSessionRepository extends Repository
{
    /**
     * Find all active sessions for all backend users
     *
     * @return array
     */
    public function findAllActive()
    {
        $sessionBackend = $this->getSessionBackend();
        $allSessions = $sessionBackend->getAll();

        // Map array to correct keys
        $allSessions = array_map(
            function ($session) {
                return [
                    'id' => $session['ses_id'], // this is the hashed sessionId
                    'ip' => $session['ses_iplock'],
                    'timestamp' => $session['ses_tstamp'],
                    'ses_userid' => $session['ses_userid']
                ];
            },
            $allSessions
        );

        // Sort by timestamp
        usort($allSessions, function ($session1, $session2) {
            return $session1['timestamp'] <=> $session2['timestamp'];
        });

        return $allSessions;
    }

    /**
     * Find Sessions for specific BackendUser
     *
     * @param BackendUser $backendUser
     * @return array
     */
    public function findByBackendUser(BackendUser $backendUser)
    {
        $allActive = $this->findAllActive();

        return array_filter(
            $allActive,
            function ($session) use ($backendUser) {
                return (int)$session['ses_userid'] === $backendUser->getUid();
            }
        );
    }

    /**
     * Update current session to move back to the original user.
     *
     * @param AbstractUserAuthentication $authentication
     */
    public function switchBackToOriginalUser(AbstractUserAuthentication $authentication)
    {
        $sessionBackend = $this->getSessionBackend();
        $sessionId = $this->getBackendSessionId();
        $sessionBackend->update(
            $sessionId,
            [
                'ses_userid' => $authentication->user['ses_backuserid'],
                'ses_backuserid' => 0
            ]
        );
    }

    /**
     * @return string
     */
    protected function getBackendSessionId(): string
    {
        return $GLOBALS['BE_USER']->id;
    }

    /**
     * @return SessionBackendInterface
     */
    protected function getSessionBackend(): SessionBackendInterface
    {
        return GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend('BE');
    }
}
