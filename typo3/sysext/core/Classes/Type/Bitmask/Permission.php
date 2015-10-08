<?php
namespace TYPO3\CMS\Core\Type\Bitmask;

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

/**
 * A class providing constants for bitwise operations on page access check
 */
class Permission extends \TYPO3\CMS\Core\Type\Enumeration
{
    /**
     * @var int
     */
    const NOTHING = 0;

    /**
     * @var int
     */
    const PAGE_SHOW = 1;

    /**
     * @var int
     */
    const PAGE_EDIT = 2;

    /**
     * @var int
     */
    const PAGE_DELETE = 4;

    /**
     * @var int
     */
    const PAGE_NEW = 8;

    /**
     * @var int
     */
    const CONTENT_EDIT = 16;

    /**
     * @var int
     */
    const ALL = 31;
}
