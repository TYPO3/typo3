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

namespace TYPO3\CMS\Styleguide\ViewHelpers;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * ViewHelper for rendering color schemes.
 *
 * ```
 *    <sg:colorScheme>your code</sg:colorScheme>
 * ```
 *
 * @internal
 */
final class ColorSchemeViewHelper extends AbstractViewHelper
{
    /**
     * @var bool
     */
    protected $escapeOutput = false;

    /**
     * @var bool
     */
    protected $escapeChildren = false;

    public function __construct(
        private readonly PageRenderer $pageRenderer,
    ) {}

    public function render(): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/styleguide/element/theme-switcher-element.js');
        $this->pageRenderer->addInlineLanguageLabelArray([
            'colorScheme.selector.label' => $this->getLanguageService()->sL('LLL:EXT:styleguide/Resources/Private/Language/locallang.xlf:colorScheme.selector.label'),
            'colorScheme.auto' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:colorScheme.auto'),
            'colorScheme.light' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:colorScheme.light'),
            'colorScheme.dark' => $this->getLanguageService()->sL('LLL:EXT:backend/Resources/Private/Language/locallang.xlf:colorScheme.dark'),
        ]);

        $content = $this->renderChildren();
        $defaultScheme = $GLOBALS['BE_USER']->uc['colorScheme'] ?? 'auto';
        $id = StringUtility::getUniqueId('styleguide-example-');

        $markup = [];
        $markup[] = '<div class="styleguide-example">';
        $markup[] =     '<div class="example t3js-styleguide-example" id="' . htmlspecialchars($id) . '" data-color-scheme="' . $defaultScheme . '">';
        $markup[] =         '<typo3-styleguide-theme-switcher activetheme="' . htmlspecialchars($defaultScheme) . '" example="#' . htmlspecialchars($id) . '"></typo3-styleguide-theme-switcher>';
        $markup[] =         str_replace('<UNIQUEID>', uniqid($defaultScheme), $content);
        $markup[] =     '</div>';
        $markup[] = '</div>';

        return implode('', $markup);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
