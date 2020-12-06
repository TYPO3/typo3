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
 * Event fired after user groups have been resolved for a specific user
 */
final class AfterGroupsResolvedEvent
{
    private string $sourceDatabaseTable;
    private array $groups;
    private array $originalGroupIds;
    private array $userData;

    public function __construct(string $sourceDatabaseTable, array $groups, array $originalGroupIds, array $userData)
    {
        $this->sourceDatabaseTable = $sourceDatabaseTable;
        $this->groups = $groups;
        $this->originalGroupIds = $originalGroupIds;
        $this->userData = $userData;
    }

    /**
     * @return string 'be_groups' or 'fe_groups' depending on context.
     */
    public function getSourceDatabaseTable(): string
    {
        return $this->sourceDatabaseTable;
    }

    /**
     * List of group records including sub groups as resolved by core.
     *
     * Note order is important: A user with main groups "1,2", where 1 has sub group 3,
     * results in "3,1,2" as record list array - sub groups are listed before the group
     * that includes the sub group.
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * List of group records as manipulated by the event.
     */
    public function setGroups(array $groups): void
    {
        $this->groups = $groups;
    }

    /**
     * List of group uids directly attached to the user
     */
    public function getOriginalGroupIds(): array
    {
        return $this->originalGroupIds;
    }

    /**
     * Full user record with all fields
     */
    public function getUserData(): array
    {
        return $this->userData;
    }
}
