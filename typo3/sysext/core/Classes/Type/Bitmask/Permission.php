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
 * A class providing constants for bitwise operations on page access check
 */
final class Permission extends BitSet
{
    /**
     * @var int
     */
    public const NOTHING = 0;

    /**
     * @var int
     */
    public const PAGE_SHOW = 1;

    /**
     * @var int
     */
    public const PAGE_EDIT = 2;

    /**
     * @var int
     */
    public const PAGE_DELETE = 4;

    /**
     * @var int
     */
    public const PAGE_NEW = 8;

    /**
     * @var int
     */
    public const CONTENT_EDIT = 16;

    /**
     * @var int
     */
    public const ALL = 31;

    /**
     * Permission mapping
     * Used for instance in PageTS
     *
     * @return array
     * @internal
     */
    public static function getMap(): array
    {
        return [
            'show' => static::PAGE_SHOW,
            // 1st bit
            'edit' => static::PAGE_EDIT,
            // 2nd bit
            'delete' => static::PAGE_DELETE,
            // 3rd bit
            'new' => static::PAGE_NEW,
            // 4th bit
            'editcontent' => static::CONTENT_EDIT,
        ];
    }

    public function isGranted(int $permission): bool
    {
        return $this->get($permission);
    }

    public function nothingIsGranted(): bool
    {
        return $this->set === self::NOTHING;
    }

    public function showPagePermissionIsGranted(): bool
    {
        return $this->get(self::PAGE_SHOW);
    }

    public function editPagePermissionIsGranted(): bool
    {
        return $this->get(self::PAGE_EDIT);
    }

    public function createPagePermissionIsGranted(): bool
    {
        return $this->get(self::PAGE_NEW);
    }

    public function deletePagePermissionIsGranted(): bool
    {
        return $this->get(self::PAGE_DELETE);
    }

    public function editContentPermissionIsGranted(): bool
    {
        return $this->get(self::CONTENT_EDIT);
    }
}
