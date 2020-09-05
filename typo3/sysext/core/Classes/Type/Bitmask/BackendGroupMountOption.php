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

namespace TYPO3\CMS\Core\Type\Bitmask;

use TYPO3\CMS\Core\Type\BitSet;

/**
 * A class providing constants for bitwise operations on whether backend users
 * should add / inherit the DB mounts / File Mounts from
 */
final class BackendGroupMountOption extends BitSet
{
    private const INCLUDE_PAGE_MOUNTS = 1;
    private const INCLUDE_FILE_MOUNTS = 2;

    public function shouldUserIncludePageMountsFromAssociatedGroups(): bool
    {
        return $this->get(self::INCLUDE_PAGE_MOUNTS);
    }
    public function shouldUserIncludeFileMountsFromAssociatedGroups(): bool
    {
        return $this->get(self::INCLUDE_FILE_MOUNTS);
    }
}
