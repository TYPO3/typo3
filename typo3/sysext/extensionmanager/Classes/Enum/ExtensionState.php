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

namespace TYPO3\CMS\Extensionmanager\Enum;

use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * @internal
 */
enum ExtensionState: int
{
    case alpha = 0;
    case beta = 1;
    case stable = 2;
    case experimental = 3;
    case test = 4;
    case obsolete = 5;
    case excludeFromUpdates = 6;
    case deprecated = 7;
    case notAvailable = 999;

    public static function fromValue(int|string $state): self
    {
        if (MathUtility::canBeInterpretedAsInteger($state)) {
            return self::tryFrom((int)$state) ?? self::notAvailable;
        }
        return match ($state) {
            'alpha' => self::alpha,
            'beta' => self::beta,
            'stable' => self::stable,
            'experimental' => self::experimental,
            'test' => self::test,
            'obsolete' => self::obsolete,
            'excludeFromUpdates' => self::excludeFromUpdates,
            'deprecated' => self::deprecated,
            default => self::notAvailable,
        };
    }

    public function getStringValue(): string
    {
        return match ($this) {
            self::alpha => 'alpha',
            self::beta => 'beta',
            self::stable => 'stable',
            self::experimental => 'experimental',
            self::test => 'test',
            self::obsolete => 'obsolete',
            self::excludeFromUpdates => 'excludeFromUpdates',
            self::deprecated => 'deprecated',
            self::notAvailable => 'n/a',
        };
    }
}
