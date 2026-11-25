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

/**
 * The pseudo groups of frontend access: they are no records of the table
 * fe_groups, but are stored in the "fe_group" field of records and are part
 * of the group ids of the "frontend.user" aspect, see UserAspect.
 */
enum FrontendUserGroup: int
{
    /**
     * Every visitor, part of the group ids of each request. In the "fe_group"
     * field, 0 or an empty value means that a record is not restricted.
     */
    case Everyone = 0;

    /**
     * Visitors without login, labeled "Hide at login" in the "fe_group" field
     */
    case NotLoggedIn = -1;

    /**
     * Visitors with any login, labeled "Show at any login" in the "fe_group"
     * field
     */
    case AnyLogin = -2;

    /**
     * The group ids of a visitor without login
     *
     * @return list<int>
     */
    public static function anonymousGroupIds(): array
    {
        return [self::Everyone->value, self::NotLoggedIn->value];
    }
}
