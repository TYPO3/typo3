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

namespace TYPO3\CMS\Core\Context;

use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Exception\AspectPropertyNotFoundException;

/**
 * The aspect contains information about a user.
 * Can be used for frontend and backend users.
 *
 * The aspect is an immutable snapshot: all information is captured when the
 * aspect is created. Code changing the state of a user object needs to set
 * a newly created aspect afterwards to make the change visible.
 *
 * Allowed properties:
 * - id
 * - username
 * - isLoggedIn
 * - isAdmin
 * - groupIds (Array of Ids)
 * - groupNames
 */
final readonly class UserAspect implements AspectInterface
{
    private int $id;
    private string $username;
    private bool $isAdmin;
    private bool $isFrontendUser;
    private array $groupIds;
    private array $groupNames;

    /**
     * @param array|null $alternativeGroups Alternative list of groups, usually useful for frontend logins with "magic" groups like "-1" and "-2"
     */
    public function __construct(?AbstractUserAuthentication $user = null, ?array $alternativeGroups = null)
    {
        $this->id = (int)($user?->user[$user->userid_column] ?? 0);
        $this->username = (string)($user?->user[$user->username_column] ?? '');
        $this->isAdmin = $user instanceof BackendUserAuthentication && $user->isAdmin();
        $this->isFrontendUser = $user !== null && $user->loginType === 'FE';
        if (is_array($alternativeGroups)) {
            $this->groupIds = $alternativeGroups;
        } elseif ($user instanceof BackendUserAuthentication) {
            $this->groupIds = $user->userGroupsUID;
        } elseif ($this->isFrontendUser) {
            if ($this->id > 0) {
                // If a user is logged in, always add "-2"
                $groups = [0, -2];
                if (!empty($user->userGroups)) {
                    $groups = array_merge($groups, array_keys($user->userGroups));
                }
                $this->groupIds = $groups;
            } else {
                $this->groupIds = [0, -1];
            }
        } else {
            $this->groupIds = [];
        }
        $groupNames = [];
        if ($user !== null) {
            foreach ($user->userGroups as $userGroup) {
                $groupNames[] = $userGroup['title'] ?? '';
            }
        }
        $this->groupNames = $groupNames;
    }

    /**
     * Fetch common information about the user
     *
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name): int|bool|string|array
    {
        switch ($name) {
            case 'id':
                return $this->id;
            case 'username':
                return $this->username;
            case 'isLoggedIn':
                return $this->isLoggedIn();
            case 'isAdmin':
                return $this->isAdmin();
            case 'groupIds':
                return $this->getGroupIds();
            case 'groupNames':
                return $this->getGroupNames();
        }
        throw new AspectPropertyNotFoundException('Property "' . $name . '" not found in Aspect "' . __CLASS__ . '".', 1529996567);
    }

    /**
     * A user is logged in if the user has a UID, but does not care about groups.
     *
     * For frontend purposes, it is possible to e.g. simulate groups, but this would still be defined as "not logged in".
     *
     * For backend, only the check on the user ID is used.
     */
    public function isLoggedIn(): bool
    {
        return $this->id > 0;
    }

    /**
     * Check if admin is set
     */
    public function isAdmin(): bool
    {
        // Only backend users have the admin flag at all.
        return $this->isAdmin;
    }

    /**
     * Return the groups the user is a member of
     *
     * For Frontend Users there are two special groups:
     * "-1" = hide at login
     * "-2" = show at any login
     */
    public function getGroupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * Get the name of all groups, used in Fluid's IfHasRole ViewHelper
     */
    public function getGroupNames(): array
    {
        return $this->groupNames;
    }

    /**
     * Checking if a user is logged in or a group constellation different from "0,-1"
     *
     * @return bool TRUE if either a login user is found OR if the group list is set to something else than '0,-1' (could be done even without a user being logged in!)
     */
    public function isUserOrGroupSet(): bool
    {
        if ($this->isFrontendUser) {
            return $this->isLoggedIn() || implode(',', $this->groupIds) !== '0,-1';
        }
        return $this->isLoggedIn();
    }
}
