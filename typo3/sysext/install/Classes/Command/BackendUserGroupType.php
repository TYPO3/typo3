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

namespace TYPO3\CMS\Install\Command;

/**
 * Enumeration object for backend user groups
 * @internal only and subject to change or be removed in TYPO3 v13. Usable only within `EXT:install`.
 */
enum BackendUserGroupType: string
{
    case EDITOR = 'Editor';
    case ADVANCED_EDITOR = 'Advanced Editor';
    case ALL = 'Both';
    case NONE = 'None';

    /**
     * @return string[]
     */
    public function getAllUserGroupTypes(): array
    {
        $groups = [];
        foreach (self::cases() as $group) {
            $groups[] = $group->value;
        }

        return $groups;
    }

    /**
     * Returns all but the "ALL|NONE" special type
     *
     * @return array<non-empty-string, non-empty-string>
     */
    public function getActualUserGroupTypes(): array
    {
        $allGroups = self::cases();
        $specificUserGroups = [];
        foreach ($allGroups as $specificGroup) {
            if (in_array($specificGroup->name, ['ALL', 'NONE'], true)) {
                continue;
            }

            $specificUserGroups[$specificGroup->name] = $specificGroup->value;
        }

        return $specificUserGroups;
    }
}
