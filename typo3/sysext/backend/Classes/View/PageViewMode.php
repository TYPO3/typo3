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

namespace TYPO3\CMS\Backend\View;

/**
 * @internal
 */
enum PageViewMode: int
{
    case LayoutView = 1;
    case LanguageComparisonView = 2;

    /**
     * Get the language label key for this view mode.
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::LayoutView => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.layout',
            self::LanguageComparisonView => 'LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.language_comparison',
        };
    }
}
