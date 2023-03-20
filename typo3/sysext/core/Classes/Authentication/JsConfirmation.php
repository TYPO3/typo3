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

use TYPO3\CMS\Core\Type\BitSet;

/**
 * Bitset for bitwise operations on javascript confirmation popups
 *
 * @see https://docs.typo3.org/m/typo3/reference-tsconfig/main/en-us/UserTsconfig/Options.html#alertpopups
 */
final class JsConfirmation extends BitSet
{
    public const TYPE_CHANGE = 0b00000001;
    public const COPY_MOVE_PASTE = 0b00000010;
    public const DELETE = 0b00000100;
    public const FE_EDIT = 0b00001000;
    private const UNUSED_16 = 0b00010000;
    private const UNUSED_32 = 0b00100000;
    private const UNUSED_64 = 0b01000000;
    public const OTHER = 0b10000000;
    public const ALL = self::TYPE_CHANGE | self::COPY_MOVE_PASTE | self::DELETE | self::FE_EDIT | self::UNUSED_16 | self::UNUSED_32 | self::UNUSED_64 | self::OTHER;
}
