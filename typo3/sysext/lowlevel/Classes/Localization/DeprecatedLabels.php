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

namespace TYPO3\CMS\Lowlevel\Localization;

/**
 * Defines how the LabelFinder treats deprecated labels.
 *
 * @internal only for development purposes
 */
enum DeprecatedLabels
{
    case Exclude;
    case Include;
    case Only;

    public function accepts(bool $deprecated): bool
    {
        return match ($this) {
            self::Exclude => !$deprecated,
            self::Include => true,
            self::Only => $deprecated,
        };
    }
}
