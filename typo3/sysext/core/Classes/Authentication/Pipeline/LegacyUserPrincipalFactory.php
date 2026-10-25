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
use TYPO3\CMS\Core\Security\Authorization\LegacyBackendUserRegistry;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;

/**
 * Builds a Principal snapshot from a legacy user authentication object,
 * for the authenticators as well as for authorization decisions on behalf
 * of any backend user: isGrantedForUser(new PrincipalUser($principal), ...).
 * Only needed during the migration period while authentication is still
 * performed by the legacy user objects.
 *
 * A backend user object is registered for its principal in the
 * LegacyBackendUserRegistry, so that decisions are taken on the group data
 * of that object, as the access checks of BackendUserAuthentication do.
 * The role is BackendUserAuthentication::getRole() of the object itself.
 *
 * The principal of a user object is built once, and anew only when the
 * user, its role, groups, switch user mode or group data changed.
 *
 * @internal not part of TYPO3 Core API yet, only exists for the migration
 *           period and will vanish together with the legacy user objects
 */
final class LegacyUserPrincipalFactory
{
    /**
     * @var \WeakMap<AbstractUserAuthentication, Principal>
     */
    private \WeakMap $principals;

    public function __construct(
        private readonly LocalIdentityProvider $identityProvider,
        private readonly LegacyBackendUserRegistry $legacyUserRegistry,
    ) {
        $this->principals = new \WeakMap();
    }

    /**
     * Returns null for an anonymous user, and for a switch user session whose
     * original user cannot be resolved: a principal of the switched user
     * without the impersonating user is never established.
     */
    public function fromUser(AbstractUserAuthentication $user, Realm $realm): ?Principal
    {
        $userId = $user->getUserId();
        if ($userId === null || $userId <= 0) {
            return null;
        }
        $originalUserId = null;
        if ($user instanceof BackendUserAuthentication) {
            $groupIds = array_map(intval(...), $user->userGroupsUID);
            $role = $user->getRole();
            $originalUserId = $user->getOriginalUserIdWhenInSwitchUserMode();
        } else {
            $groupIds = array_map(intval(...), array_keys($user->userGroups));
            $role = PrincipalRole::USER;
        }
        $principal = $this->principals[$user] ?? null;
        if ($principal !== null && $this->isUnchanged($principal, $user, $realm, $userId, $role, $groupIds, $originalUserId)) {
            return $principal;
        }
        $impersonatedBy = null;
        if ($originalUserId !== null) {
            $impersonatedBy = $this->createImpersonatingContext($originalUserId);
            if ($impersonatedBy === null) {
                return null;
            }
        }
        $groupNames = array_map(
            static fn(int $groupId): string => (string)($user->userGroups[$groupId]['title'] ?? ''),
            $groupIds
        );
        $principal = new Principal(
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
        if ($user instanceof BackendUserAuthentication) {
            $this->legacyUserRegistry->register($principal, $user);
        }
        $this->principals[$user] = $principal;
        return $principal;
    }

    /**
     * @param list<int> $groupIds
     */
    private function isUnchanged(
        Principal $principal,
        AbstractUserAuthentication $user,
        Realm $realm,
        int $userId,
        PrincipalRole $role,
        array $groupIds,
        ?int $originalUserId,
    ): bool {
        return $principal->realmIdentifier === $realm->identifier
            && $principal->context->id === $userId
            && $principal->context->role === $role
            && $principal->context->groupIds === $groupIds
            && $principal->context->impersonatedBy?->id === $originalUserId
            && $principal->username === (string)$user->getUserName()
            && (!$user instanceof BackendUserAuthentication
                || $role !== PrincipalRole::USER
                || $this->legacyUserRegistry->getGroupData($principal) === $user->groupData);
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
