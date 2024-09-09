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

namespace TYPO3\CMS\Backend\Backend;

enum ColorScheme: string
{
    case auto = 'auto';
    case light = 'light';
    case dark = 'dark';

    public function getLabel(): string
    {
        return match ($this) {
            self::auto => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:colorScheme.auto',
            self::light => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:colorScheme.light',
            self::dark => 'LLL:EXT:backend/Resources/Private/Language/locallang.xlf:colorScheme.dark',
        };
    }

    public function getIcon(): string
    {
        return match ($this) {
            self::auto => 'actions-circle-half',
            self::light => 'actions-brightness-high',
            self::dark => 'actions-moon',
        };
    }

    public static function getAvailableItemsForSelection(): array
    {
        return [
            self::auto->value => self::auto->getLabel(),
            self::light->value => self::light->getLabel(),
            self::dark->value => self::dark->getLabel(),
        ];
    }
}
