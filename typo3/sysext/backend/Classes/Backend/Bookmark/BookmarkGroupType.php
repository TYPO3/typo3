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

namespace TYPO3\CMS\Backend\Backend\Bookmark;

/**
 * Defines the different types/sources of bookmark groups.
 *
 * @internal
 */
enum BookmarkGroupType: string
{
    /**
     * System groups defined via UserTSconfig (options.bookmarkGroups.X = "Label").
     * These have positive integer IDs and include the default bookmark groups.
     */
    case SYSTEM = 'system';

    /**
     * Global groups (negative IDs) - bookmarks visible to all users but only admins can add to them.
     * These mirror the system groups but with negative IDs.
     */
    case GLOBAL = 'global';

    /**
     * User-created groups stored in sys_be_shortcuts_group table.
     * These have UUID identifiers and are specific to the user who created them.
     */
    case USER = 'user';

    /**
     * Returns the priority for this group type.
     * Lower values appear first: user → system → global
     */
    public function getPriority(): int
    {
        return match ($this) {
            self::USER => 0,
            self::SYSTEM => 1,
            self::GLOBAL => 2,
        };
    }
}
