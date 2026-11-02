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

namespace TYPO3\CMS\Core\Authentication\Event;

/**
 * Listeners can grant or revoke the permissions a backend user has on a page,
 * e.g. to apply access control lists. The permissions are calculated from the
 * owner, group and everybody settings of the page, after the page has been
 * found within a webmount of the user.
 *
 * Listings select pages with a constraint, which listeners of
 * ModifyPagePermissionConstraintEvent change in the same way.
 *
 * The event is not dispatched for admins, who hold all permissions, and not for
 * pages outside the webmounts of the user.
 *
 * The page record holds the fields the caller loaded, e.g. the page tree passes
 * a subset of the fields. Load a missing field by the uid of the page.
 *
 * The permission checks of the DataHandler do not dispatch the event yet, only
 * the check of the subpages when a page is deleted recursively does: a listener
 * revoking a permission hides the action in the backend, but does not keep the
 * DataHandler from executing it.
 */
final class ModifyPagePermissionsEvent
{
    /**
     * @param array<string, mixed> $pageRecord
     * @param list<int> $groupIds
     */
    public function __construct(
        private readonly array $pageRecord,
        private int $permissions,
        private readonly int $userId,
        private readonly array $groupIds,
        private readonly int $workspaceId,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function getPageRecord(): array
    {
        return $this->pageRecord;
    }

    /**
     * The bitmask of the permissions, see TYPO3\CMS\Core\Type\Bitmask\Permission.
     */
    public function getPermissions(): int
    {
        return $this->permissions;
    }

    public function setPermissions(int $permissions): void
    {
        $this->permissions = $permissions;
    }

    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @return list<int>
     */
    public function getGroupIds(): array
    {
        return $this->groupIds;
    }

    /**
     * The workspace of the user, 0 for live.
     */
    public function getWorkspaceId(): int
    {
        return $this->workspaceId;
    }
}
