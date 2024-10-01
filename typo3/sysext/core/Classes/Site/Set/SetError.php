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

namespace TYPO3\CMS\Core\Site\Set;

enum SetError: string
{
    case notFound = 'not-found';
    case missingDependency = 'missing-dependency';
    case invalidSettingsDefinitions = 'invalid-settings-definitions';
    case invalidSettings = 'invalid-settings';

    public function getLabel(): string
    {
        return match ($this) {
            self::notFound => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.siteSet.notFound',
            self::missingDependency => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.siteSet.missingDependency',
            self::invalidSettingsDefinitions => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.siteSet.invalidSettingsDefinitions',
            self::invalidSettings => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:error.siteSet.invalidSettings',
        };
    }
}
