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
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * The aspect contains information about a user.
 * Can be used for frontend and backend users.
 *
 * Allowed properties:
 * - id
 * - username
 * - isLoggedIn
 * - groupIds (Array of Ids)
 * - groupNames
 */
class UserAspect implements AspectInterface
{
    /**
     * @var AbstractUserAuthentication|\stdClass
     */
    protected $user;

    /**
     * Alternative list of groups, usually useful for frontend logins with "magic" groups like "-1" and "-2"
     *
     * @var int[]|null
     */
    protected $groups;

    /**
     * @param AbstractUserAuthentication|null $user
     * @param array|null $alternativeGroups
     */
    public function __construct(AbstractUserAuthentication $user = null, array $alternativeGroups = null)
    {
        $this->user = $user ?? $this->createPseudoUser();
        $this->groups = $alternativeGroups;
    }

    /**
     * @return \stdClass
     */
    private function createPseudoUser(): \stdClass
    {
        $user = new \stdClass();
        $user->user = [];
        return $user;
    }

    /**
     * Fetch common information about the user
     *
     * @param string $name
     * @return int|bool|string|array
     * @throws AspectPropertyNotFoundException
     */
    public function get(string $name)
    {
        switch ($name) {
            case 'id':
                return (int)($this->user->user[$this->user->userid_column ?? 'uid'] ?? 0);
            case 'username':
                return (string)($this->user->user[$this->user->username_column ?? 'username'] ?? '');
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
     * This is also possible in frontend where there are cases that a user can be marked as NOT logged IN, but
     * be logged in but the groups are explicitly NOT defined (see pages.fe_login_mode)
     *
     * For backend, only the check on the user ID is used.
     *
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return ($this->user->user[$this->user->userid_column ?? 'uid'] ?? 0) > 0;
    }

    /**
     * Check if admin is set
     *
     * @return bool
     */
    public function isAdmin(): bool
    {
        $isAdmin = false;
        if ($this->user instanceof BackendUserAuthentication) {
            $isAdmin = $this->user->isAdmin();
        }
        return $isAdmin;
    }

    /**
     * Return the groups the user is a member of
     *
     * For Frontend Users there are two special groups:
     * "-1" = hide at login
     * "-2" = show at any login
     *
     * @return array
     */
    public function getGroupIds(): array
    {
        // Alternative groups are set
        if (is_array($this->groups)) {
            return $this->groups;
        }
        if ($this->user instanceof BackendUserAuthentication) {
            return $this->user->userGroupsUID;
        }
        $groups = [];
        if ($this->user instanceof FrontendUserAuthentication) {
            if ($this->isLoggedIn()) {
                // If a user is logged in, always add "-2"
                $groups = [0, -2];
                if (!empty($this->user->userGroups)) {
                    $groups = array_merge($groups, array_keys($this->user->userGroups));
                }
            } else {
                $groups = [0, -1];
            }
        }
        return $groups;
    }

    /**
     * Get the name of all groups, used in Fluid's IfHasRole ViewHelper
     *
     * @return array
     */
    public function getGroupNames(): array
    {
        $groupNames = [];
        if ($this->user instanceof AbstractUserAuthentication) {
            foreach ($this->user->userGroups as $userGroup) {
                $groupNames[] = $userGroup['title'];
            }
        }
        return $groupNames;
    }

    /**
     * Checking if a user is logged in or a group constellation different from "0,-1"
     *
     * @return bool TRUE if either a login user is found OR if the group list is set to something else than '0,-1' (could be done even without a user being logged in!)
     */
    public function isUserOrGroupSet(): bool
    {
        if ($this->user instanceof FrontendUserAuthentication) {
            $groups = $this->getGroupIds();
            return $this->isLoggedIn() || implode(',', $groups) !== '0,-1';
        }
        return $this->isLoggedIn();
    }
}
