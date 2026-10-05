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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\LocalIdentityProvider;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;

/**
 * Builds a Principal snapshot from a legacy user authentication object.
 * Only needed during the migration period while authentication is still
 * performed by the legacy user objects.
 *
 * @internal not part of TYPO3 Core API yet, only exists for the migration
 *           period and will vanish together with the legacy user objects
 */
final class LegacyUserPrincipalFactory
{
    public function __construct(
        private readonly LocalIdentityProvider $identityProvider,
    ) {}

    /**
     * Returns null for an anonymous user, and for a switch user session whose
     * original user cannot be resolved: a principal of the switched user
     * without the impersonating user is never established.
     *
     * @throws \LogicException for a backend user that is not the global backend user
     */
    public function fromUser(AbstractUserAuthentication $user, Realm $realm): ?Principal
    {
        $userId = $user->getUserId();
        if ($userId === null || $userId <= 0) {
            return null;
        }
        $impersonatedBy = null;
        if ($user instanceof BackendUserAuthentication) {
            // BackendUserAuthentication::getRole() evaluates the switch user
            // mode through $GLOBALS['BE_USER'], so the role is only correct
            // for the global backend user.
            if (($GLOBALS['BE_USER'] ?? null) !== $user) {
                throw new \LogicException(
                    'A principal can only be built for the global backend user.',
                    1791530201
                );
            }
            $groupIds = array_map(intval(...), $user->userGroupsUID);
            $role = $user->getRole();
            $originalUserId = $user->getOriginalUserIdWhenInSwitchUserMode();
            if ($originalUserId !== null) {
                $impersonatedBy = $this->createImpersonatingContext($originalUserId);
                if ($impersonatedBy === null) {
                    return null;
                }
            }
        } else {
            $groupIds = array_map(intval(...), array_keys($user->userGroups));
            $role = PrincipalRole::USER;
        }
        $groupNames = array_map(
            static fn(int $groupId): string => (string)($user->userGroups[$groupId]['title'] ?? ''),
            $groupIds
        );
        return new Principal(
            context: new PrincipalContext(
                role: $role,
                id: $userId,
                groupIds: $groupIds,
                impersonatedBy: $impersonatedBy,
            ),
            realmIdentifier: $realm->identifier,
            username: (string)$user->getUserName(),
            groupNames: $groupNames,
        );
    }

    private function createImpersonatingContext(int $originalUserId): ?PrincipalContext
    {
        if ($this->identityProvider->findBackendUserByUid($originalUserId) === null) {
            return null;
        }
        return new PrincipalContext(
            // Only administrators can switch users. The role is not refined
            // to MAINTAINER, which matches LogEntryRepository, where the
            // refinement is left to a follow-up.
            role: PrincipalRole::ADMIN,
            id: $originalUserId,
        );
    }
}
