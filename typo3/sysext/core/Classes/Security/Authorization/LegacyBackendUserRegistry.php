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

namespace TYPO3\CMS\Core\Security\Authorization;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Authentication\Principal;
use TYPO3\CMS\Core\Security\PermissionSet\PrincipalRole;

/**
 * Remembers the backend user object a principal was built from, so that
 * decisions which still depend on that object can be taken: voters decide
 * on its group data, and module access gates receive the object itself.
 *
 * The group data is kept as it was when the principal was registered, and
 * only for the role "user", as every other role is granted before group
 * data is consulted. The user object is referenced weakly, so that it is
 * not kept alive by its principal. Entries are bound to the identity of
 * the principal object and live as long as it does: a principal built
 * anew from the same values is not known here.
 *
 * @internal Bridge for code that still requires BackendUserAuthentication,
 *           to be removed once all decisions are taken on principals.
 */
final class LegacyBackendUserRegistry
{
    /**
     * @var \WeakMap<Principal, \WeakReference<BackendUserAuthentication>>
     */
    private \WeakMap $users;

    /**
     * @var \WeakMap<Principal, array<mixed>>
     */
    private \WeakMap $groupData;

    public function __construct()
    {
        $this->users = new \WeakMap();
        $this->groupData = new \WeakMap();
    }

    public function register(Principal $principal, BackendUserAuthentication $user): void
    {
        $this->users[$principal] = \WeakReference::create($user);
        if ($principal->context->role === PrincipalRole::USER) {
            $this->groupData[$principal] = $user->groupData;
        } else {
            unset($this->groupData[$principal]);
        }
    }

    public function getUser(Principal $principal): ?BackendUserAuthentication
    {
        return ($this->users[$principal] ?? null)?->get();
    }

    /**
     * The group data of the user object at registration, null for an
     * unregistered principal and for roles other than "user"
     *
     * @return ?array<mixed>
     */
    public function getGroupData(Principal $principal): ?array
    {
        return $this->groupData[$principal] ?? null;
    }
}
