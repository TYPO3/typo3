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
enum ExtensionCategory: int
{
    case Backend = 0;
    case Module = 1;
    case Frontend = 2;
    case Plugin = 3;
    case Miscellaneous = 4;
    case Services = 5;
    case Templates = 6;
    case Documentation = 8;
    case Example = 9;
    case Distribution = 10;

    /**
     * This method returns the enum for the value either based on the value or the name and returning the
     * ExtensionCategory::Miscellaneous as default for invalid values. Note that this means that nor error
     * or exception is thrown.
     *
     * @todo Either the int or string based value determination should be dropped and streamlined towards one
     *       value type and the default tryFrom method used in calling places doing the fallback there directly.
     */
    public static function fromValue(int|string $value): self
    {
        if (MathUtility::canBeInterpretedAsInteger($value)) {
            return self::tryFrom((int)$value) ?? self::Miscellaneous;
        }
        return match ($value) {
            'be' => self::Backend,
            'module' => self::Module,
            'fe' => self::Frontend,
            'plugin' => self::Plugin,
            'misc' => self::Miscellaneous,
            'services' => self::Services,
            'templates' => self::Templates,
            'doc' => self::Documentation,
            'example' => self::Example,
            'distribution' => self::Distribution,
            default => self::Miscellaneous,
        };
    }

    public function getStringValue(): string
    {
        return match ($this) {
            self::Backend => 'be',
            self::Module => 'module',
            self::Frontend => 'fe',
            self::Plugin => 'plugin',
            self::Miscellaneous => 'misc',
            self::Services => 'services',
            self::Templates => 'templates',
            self::Documentation => 'doc',
            self::Example => 'example',
            self::Distribution => 'distribution',
        };
    }
}
