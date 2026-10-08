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

namespace TYPO3\CMS\Core\Authentication;

use TYPO3\CMS\Core\Authentication\Pipeline\Realm;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalContext;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;

/**
 * Immutable representation of an authenticated user: who the user is, in
 * which realm the user authenticated - independent of how and where
 * the underlying identity is stored.
 *
 * The PrincipalContext carries role, id, groups and impersonation, the data
 * the permission evaluation works with. The principal adds what only
 * authentication knows. The role in the context is the only source of the
 * authorization level.
 *
 * Frontend principals always carry the role USER. Their context holds an
 * fe_users uid and fe_groups uids, so it must never be handed to a
 * permission evaluation of backend users.
 *
 * The group ids are the resolved user groups including their subgroups, as
 * the user aspect and the permission context expect them, not only the
 * groups the user is a direct member of. The group names belong to the
 * same groups.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication modernization groundwork
 */
final readonly class Principal
{
    /**
     * @param list<string> $groupNames
     */
    public function __construct(
        public PrincipalContext $context,
        public string $realmIdentifier,
        public string $username,
        public array $groupNames = [],
    ) {
        if ($context->id <= 0) {
            throw new \InvalidArgumentException('A principal requires a positive user id, got ' . $context->id . '.', 1791465463);
        }
        if ($realmIdentifier === '') {
            throw new \InvalidArgumentException('A principal requires a realm identifier.', 1791466989);
        }
        if ($this->belongsToFrontendRealm() && ($context->role !== PrincipalRole::USER || $context->impersonatedBy !== null)) {
            throw new \InvalidArgumentException('A principal of the frontend realm requires the role "user" and no impersonation.', 1791466458);
        }
    }

    /**
     * Whether the principal denotes a backend user. Every realm other than
     * the frontend realm authenticates backend users, the backend realm as
     * well as realms of token based channels.
     */
    public function isBackendUser(): bool
    {
        return !$this->belongsToFrontendRealm();
    }

    public function belongsToFrontendRealm(): bool
    {
        return Realm::isFrontendIdentifier($this->realmIdentifier);
    }
}
