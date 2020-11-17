<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Session;

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

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Session\Backend\HashableSessionBackendInterface;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class SessionManager
 *
 * Example Configuration
 *
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'] => [
 *     'BE' => [
 *         'backend' => \TYPO3\CMS\Core\Session\Backend\FileSessionBackend::class,
 *         'savePath' => '/var/www/t3sessionframework/data/'
 *     ]
 * ]
 */
class SessionManager implements SingletonInterface
{
    /**
     * @var SessionBackendInterface[]
     */
    protected $sessionBackends = [];

    /**
     * Gets the currently running session backend for the given context
     *
     * @param string $identifier
     * @return SessionBackendInterface
     * @throws \InvalidArgumentException
     */
    public function getSessionBackend(string $identifier): SessionBackendInterface
    {
        if (!isset($this->sessionBackends[$identifier])) {
            $configuration = $GLOBALS['TYPO3_CONF_VARS']['SYS']['session'][$identifier] ?? false;
            if (!$configuration) {
                throw new \InvalidArgumentException('Session configuration for identifier ' . $identifier . ' was not found', 1482234750);
            }

            $sessionBackend = $this->createSessionBackendFromConfiguration($identifier, $configuration);

            // Validates the session backend configuration and throws an exception if something's wrong
            $sessionBackend->validateConfiguration();
            $this->sessionBackends[$identifier] = $sessionBackend;
        }
        return $this->sessionBackends[$identifier];
    }

    /**
     * Removes all sessions for a specific user ID
     *
     * @param SessionBackendInterface $backend see constants
     * @param int $userId
     * @param AbstractUserAuthentication $userAuthentication
     */
    public function invalidateAllSessionsByUserId(SessionBackendInterface $backend, int $userId, AbstractUserAuthentication $userAuthentication = null)
    {
        $sessionToRenew = '';
        $hashedSessionToRenew = '';
        // Prevent destroying the session of the current user session, but renew session id
        if ($userAuthentication !== null && (int)$userAuthentication->user['uid'] === $userId) {
            $sessionToRenew = $userAuthentication->getSessionId();
        }
        if ($sessionToRenew !== '' && $backend instanceof HashableSessionBackendInterface) {
            $hashedSessionToRenew = $backend->hash($sessionToRenew);
        }

        foreach ($backend->getAll() as $session) {
            if ($userAuthentication !== null) {
                if ($session['ses_id'] === $sessionToRenew || $session['ses_id'] === $hashedSessionToRenew) {
                    $userAuthentication->enforceNewSessionId();
                    continue;
                }
            }
            if ((int)$session['ses_userid'] === $userId) {
                $backend->remove($session['ses_id']);
            }
        }
    }

    /**
     * Creates a session backend from configuration
     *
     * @param string $identifier the identifier
     * @param array $configuration The session configuration array
     * @return SessionBackendInterface
     * @throws \InvalidArgumentException
     */
    protected function createSessionBackendFromConfiguration(string $identifier, array $configuration): SessionBackendInterface
    {
        $className = $configuration['backend'];

        if (!is_subclass_of($className, SessionBackendInterface::class)) {
            throw new \InvalidArgumentException('Configured session backend ' . $className . ' does not implement ' . SessionBackendInterface::class, 1482235035);
        }

        $options = $configuration['options'] ?? [];

        /** @var SessionBackendInterface $backend */
        $backend = GeneralUtility::makeInstance($className);
        $backend->initialize($identifier, $options);
        return $backend;
    }
}
