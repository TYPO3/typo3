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

namespace TYPO3\CMS\Backend\Security\SudoMode\Access;

/**
 * Defines the lifetime of the sudo mode in a human-readable form.
 *
 * @internal
 */
enum AccessLifetime: string
{
    case veryShort = 'veryShort';
    case short = 'short';
    case medium = 'medium';
    case long = 'long';
    case veryLong = 'veryLong';

    public function inSeconds(): int
    {
        return self::lifetimes()[$this] * 60;
    }

    private static function lifetimes(): \WeakMap
    {
        $map = new \WeakMap();
        $map[self::veryShort] = 5;
        $map[self::short] = 10;
        $map[self::medium] = 15;
        $map[self::long] = 30;
        $map[self::veryLong] = 60;
        return $map;
    }
}
