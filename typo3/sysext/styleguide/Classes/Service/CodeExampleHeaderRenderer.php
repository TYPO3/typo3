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

namespace TYPO3\CMS\Styleguide\Service;

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Renders the header above a styleguide code example, holding the button
 * to copy the code to the clipboard.
 *
 * @internal
 */
final readonly class CodeExampleHeaderRenderer
{
    public function __construct(
        private PageRenderer $pageRenderer,
        private IconFactory $iconFactory,
    ) {}

    public function render(string $code): string
    {
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/copy-to-clipboard.js');
        $languageService = $this->getLanguageService();
        $buttonAttributes = [
            'class' => 'btn btn-sm btn-default styleguide-example-code-copy',
            'title' => $languageService->sL('styleguide.messages:example.code.copyToClipboard'),
            'text' => $code,
        ];
        $markup = [];
        $markup[] = '<div class="styleguide-example-code-header">';
        $markup[] =     '<typo3-copy-to-clipboard ' . GeneralUtility::implodeAttributes($buttonAttributes, true) . '>';
        $markup[] =         $this->iconFactory->getIcon('actions-clipboard', IconSize::SMALL)->render();
        $markup[] =         ' ' . htmlspecialchars($languageService->sL('styleguide.messages:example.code.copy'));
        $markup[] =     '</typo3-copy-to-clipboard>';
        $markup[] = '</div>';
        return implode('', $markup);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
