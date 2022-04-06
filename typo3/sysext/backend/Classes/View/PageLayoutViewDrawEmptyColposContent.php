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

use TYPO3\CMS\Backend\View\Event\AfterSectionMarkupGeneratedEvent;
use TYPO3\CMS\Core\Localization\LanguageService;

/**
 * Enrich columns with no colPos given (unassigned columns).
 */
class PageLayoutViewDrawEmptyColposContent
{
    public function __invoke(AfterSectionMarkupGeneratedEvent $event): void
    {
        if (($event->getColumnConfig()['name'] ?? '') === 'unused'
            || (isset($event->getColumnConfig()['colPos']) &&
                trim((string)$event->getColumnConfig()['colPos']) !== '')
        ) {
            // Early return for the special "unused" column or
            // in case the current column has a colPos set.
            return;
        }

        $lang = $this->getLanguageService();
        $content = $event->getContent();
        $content .= '
                <div data-colpos="1" data-language-uid="0" class="t3-page-ce-wrapper">
                    <div class="t3-page-ce">
                        <div class="t3-page-ce-header">' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:emptyColPos')) . '</div>
                        <div class="t3-page-ce-body">
                            <div class="t3-page-ce-body-inner">
                                <div class="row">
                                    <div class="col-12">
                                        ' . htmlspecialchars($lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_layout.xlf:emptyColPos.message')) . '
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';

        $event->setContent($content);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
