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

namespace TYPO3\CMS\Core\Security\Authorization\Page;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\Authorization\BackendMountContext;
use TYPO3\CMS\Core\Security\Authorization\PrincipalUser;
use TYPO3\CMS\Core\Security\Authorization\SecurityRole;

/**
 * Everything page permissions are decided on: the user and its groups, whether
 * the user is an admin, and the mounts of the request.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final readonly class PageAccessContext
{
    /**
     * @param list<int> $groupIds
     * @param BackendUserAuthentication|null $legacyBackendUser the user object the deprecated hooks
     *                                                          getPagePermsClause and calcPerms are
     *                                                          called with, to be removed in v16
     */
    public function __construct(
        public int $userId,
        public array $groupIds,
        public bool $isAdmin,
        public BackendMountContext $mounts,
        public ?BackendUserAuthentication $legacyBackendUser = null,
    ) {}

    public static function fromBackendUser(BackendUserAuthentication $backendUser): self
    {
        return new self(
            is_array($backendUser->user) ? (int)($backendUser->user['uid'] ?? 0) : 0,
            array_values(array_map(intval(...), $backendUser->userGroupsUID)),
            is_array($backendUser->user) && $backendUser->isAdmin(),
            BackendMountContext::fromBackendUser($backendUser),
            $backendUser,
        );
    }

    /**
     * @param BackendUserAuthentication|null $legacyBackendUser the user object the principal was built from,
     *                                                          see LegacyBackendUserRegistry::getUser(). Without
     *                                                          it, a registered deprecated hook denies all pages.
     */
    public static function fromPrincipal(Principal $principal, BackendMountContext $mounts, ?BackendUserAuthentication $legacyBackendUser): self
    {
        return new self(
            $principal->context->id,
            array_values(array_map(intval(...), $principal->context->groupIds)),
            new PrincipalUser($principal)->hasRole(SecurityRole::ADMIN),
            $mounts,
            $legacyBackendUser,
        );
    }

    /**
     * Identifies the user and the groups, the parts the permission clause depends on.
     */
    public function getPermissionIdentifier(): string
    {
        return $this->userId . '|' . implode(',', $this->groupIds) . '|' . (int)$this->isAdmin;
    }
}
