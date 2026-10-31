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

namespace TYPO3\CMS\Core\Domain\Record;

use TYPO3\CMS\Core\Type\Bitmask\Permission;

/**
 * Contains the permission settings stored in a page record: the owning backend
 * user and group, and what the owner, the group and everybody else may do.
 *
 * These settings are the same for every backend user. Whether a specific backend
 * user is granted a permission also depends on the user being an administrator
 * and on the groups the user is member of.
 */
final readonly class PagePermissions
{
    private const array FIELDS = ['perms_userid', 'perms_groupid', 'perms_user', 'perms_group', 'perms_everybody'];

    public function __construct(
        private int $ownerUserId,
        private int $ownerGroupId,
        private int $ownerPermissions,
        private int $groupPermissions,
        private int $everybodyPermissions,
    ) {}

    /**
     * Returns null if the record does not contain all permission fields.
     */
    public static function fromRecord(array $record): ?self
    {
        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $record)) {
                return null;
            }
        }
        return new self(
            (int)$record['perms_userid'],
            (int)$record['perms_groupid'],
            (int)$record['perms_user'],
            (int)$record['perms_group'],
            (int)$record['perms_everybody'],
        );
    }

    public function getOwnerUserId(): int
    {
        return $this->ownerUserId;
    }

    public function getOwnerGroupId(): int
    {
        return $this->ownerGroupId;
    }

    public function getOwnerPermissions(): Permission
    {
        return new Permission($this->ownerPermissions);
    }

    public function getGroupPermissions(): Permission
    {
        return new Permission($this->groupPermissions);
    }

    public function getEverybodyPermissions(): Permission
    {
        return new Permission($this->everybodyPermissions);
    }
}
