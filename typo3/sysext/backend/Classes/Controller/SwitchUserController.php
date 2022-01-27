<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Backend\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Authentication\Event\SwitchUserEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Session\Backend\SessionBackendInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\CMS\Core\SysLog\Type;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
class SwitchUserController
{
    protected const RECENT_USERS_LIMIT = 3;

    protected EventDispatcherInterface $eventDispatcher;
    protected UriBuilder $uriBuilder;
    protected ResponseFactoryInterface $responseFactory;
    protected SessionBackendInterface $sessionBackend;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        UriBuilder $uriBuilder,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->uriBuilder = $uriBuilder;
        $this->responseFactory = $responseFactory;
        $this->sessionBackend = GeneralUtility::makeInstance(SessionManager::class)->getSessionBackend('BE');
    }

    /**
     * Handle switching current user to the requested target user
     */
    public function switchUserAction(ServerRequestInterface $request): ResponseInterface
    {
        $currentUser = $this->getBackendUserAuthentication();
        $targetUserId = (int)($request->getParsedBody()['targetUser'] ?? 0);

        if (!$targetUserId
            || $targetUserId === (int)($currentUser->user[$currentUser->userid_column] ?? 0)
            || !$currentUser->isAdmin()
            || $currentUser->getOriginalUserIdWhenInSwitchUserMode() !== null
        ) {
            return $this->jsonResponse(['success' => false]);
        }

        $targetUser = BackendUtility::getRecord('be_users', $targetUserId, '*', BackendUtility::BEenableFields('be_users'));
        if ($targetUser === null) {
            return $this->jsonResponse(['success' => false]);
        }

        if (ExtensionManagementUtility::isLoaded('beuser')) {
            // Set backend user listing module as starting module if installed
            $currentUser->uc['startModuleOnFirstLogin'] = 'system_BeuserTxBeuser';
        }
        $currentUser->uc['recentSwitchedToUsers'] = $this->generateListOfMostRecentSwitchedUsers($targetUserId);
        $currentUser->writeUC();

        // Write user switch to log
        $currentUser->writelog(Type::LOGIN, 2, 0, 1, 'User %s switched to user %s (be_users:%s)', [
            $currentUser->user[$currentUser->username_column] ?? '',
            $targetUser['username'] ?? '',
            $targetUserId,
        ]);

        $sessionObject = $currentUser->getSession();
        $sessionObject->set('backuserid', (int)($currentUser->user[$currentUser->userid_column] ?? 0));
        $sessionRecord = $sessionObject->toArray();
        $sessionRecord['ses_userid'] = $targetUserId;
        $this->sessionBackend->update($sessionObject->getIdentifier(), $sessionRecord);
        // We must regenerate the internal session so the new ses_userid is present in the userObject
        $currentUser->enforceNewSessionId();

        $event = new SwitchUserEvent(
            $currentUser->getSession()->getIdentifier(),
            $targetUser,
            (array)$currentUser->user
        );
        $this->eventDispatcher->dispatch($event);

        return $this->jsonResponse([
            'success' => true,
            'url' => $this->uriBuilder->buildUriFromRoute('main'),
        ]);
    }

    /**
     * Handle exiting the switch user mode
     */
    public function exitSwitchUserAction(ServerRequestInterface $request): ResponseInterface
    {
        $currentUser = $this->getBackendUserAuthentication();

        if ($currentUser->getOriginalUserIdWhenInSwitchUserMode() === null) {
            return $this->jsonResponse(['success' => false]);
        }

        $sessionObject = $currentUser->getSession();
        $originalUser = (int)$sessionObject->get('backuserid');
        $sessionObject->set('backuserid', null);
        $sessionRecord = $sessionObject->toArray();
        $sessionRecord['ses_userid'] = $originalUser;
        $this->sessionBackend->update($sessionObject->getIdentifier(), $sessionRecord);
        // We must regenerate the internal session so the new ses_userid is present in the userObject
        $currentUser->enforceNewSessionId();

        return $this->jsonResponse([
            'success' => true,
            'url' => $this->uriBuilder->buildUriFromRoute('main'),
        ]);
    }

    /**
     * Generates a list of users to whom where switched in the past. This is limited by RECENT_USERS_LIMIT.
     *
     * @param int $targetUserUid
     * @return int[]
     */
    protected function generateListOfMostRecentSwitchedUsers(int $targetUserUid): array
    {
        $latestUserUids = [];
        $backendUser = $this->getBackendUserAuthentication();

        if (isset($backendUser->uc['recentSwitchedToUsers']) && is_array($backendUser->uc['recentSwitchedToUsers'])) {
            $latestUserUids = $backendUser->uc['recentSwitchedToUsers'];
        }

        // Remove potentially existing user in that list
        $index = array_search($targetUserUid, $latestUserUids, true);
        if ($index !== false) {
            unset($latestUserUids[$index]);
        }
        array_unshift($latestUserUids, $targetUserUid);

        return array_slice($latestUserUids, 0, static::RECENT_USERS_LIMIT);
    }

    protected function jsonResponse(array $data): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withAddedHeader('Content-Type', 'application/json; charset=utf-8');

        $response->getBody()->write(json_encode($data));
        return $response;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
