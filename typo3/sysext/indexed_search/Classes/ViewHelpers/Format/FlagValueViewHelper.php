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

namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * @internal
 */
final class FlagValueViewHelper extends AbstractViewHelper
{
    protected $escapeOutput = false;

    public function initializeArguments(): void
    {
        $this->registerArgument('flags', 'int', '', true);
    }

    /**
     * Render additional flag information
     */
    public function render(): string
    {
        $flags = (int)($this->arguments['flags'] ?? 0);
        $languageService = self::getLanguageService();
        $content = [];
        if ($flags & 128) {
            $content[] = '<span class="badge badge-secondary">'
                . htmlspecialchars($languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.flag.128'))
                . '</span>';
        }
        if ($flags & 64) {
            $content[] = '<span class="badge badge-secondary">'
                . htmlspecialchars($languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.flag.64'))
                . '</span>';
        }
        if ($flags & 32) {
            $content[] = '<span class="badge badge-secondary">'
                . htmlspecialchars($languageService->sL('LLL:EXT:indexed_search/Resources/Private/Language/locallang.xlf:administration.flag.32'))
                . '</span>';
        }
        return implode(' ', $content);
    }

    protected static function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
