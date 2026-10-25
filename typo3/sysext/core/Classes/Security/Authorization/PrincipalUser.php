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

use Symfony\Component\Security\Core\User\UserInterface;
use TYPO3\CMS\Core\Authentication\Principal;

/**
 * Presents a Principal as user of the Symfony security component, so that
 * voters receive the immutable Principal instead of a user authentication
 * object.
 *
 * The roles are the cumulative SecurityRole cases of the principal role,
 * plus SecurityRole::BACKEND_USER or SecurityRole::FRONTEND_USER, as
 * frontend principals carry the role "user" as well, and one role per
 * group, ROLE_BACKEND_GROUP_<uid> or ROLE_FRONTEND_GROUP_<uid>.
 */
final readonly class PrincipalUser implements UserInterface
{
    public function __construct(
        public Principal $principal,
    ) {}

    /**
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = array_map(
            static fn(SecurityRole $role): string => $role->value,
            $this->getSecurityRoles()
        );
        $realm = $this->principal->isBackendUser() ? 'BACKEND' : 'FRONTEND';
        foreach (array_unique($this->principal->context->groupIds) as $groupId) {
            $roles[] = 'ROLE_' . $realm . '_GROUP_' . $groupId;
        }
        return $roles;
    }

    public function hasRole(SecurityRole $role): bool
    {
        return in_array($role, $this->getSecurityRoles(), true);
    }

    /**
     * @return list<SecurityRole>
     */
    private function getSecurityRoles(): array
    {
        $roles = SecurityRole::fromPrincipalRole($this->principal->context->role);
        $roles[] = $this->principal->isBackendUser() ? SecurityRole::BACKEND_USER : SecurityRole::FRONTEND_USER;
        return $roles;
    }

    /**
     * Unique across realms, as a backend and a frontend user may share a
     * username or a uid.
     */
    public function getUserIdentifier(): string
    {
        return $this->principal->realmIdentifier . ':' . $this->principal->context->id;
    }
}
