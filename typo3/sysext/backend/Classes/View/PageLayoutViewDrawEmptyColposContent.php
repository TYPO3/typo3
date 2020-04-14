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

/**
 * An example how to enrich the column with no colPos given.
 */
class PageLayoutViewDrawEmptyColposContent
{
    public function __invoke(AfterSectionMarkupGeneratedEvent $event): void
    {
        if (
            !isset($event->getColumnConfig()['colPos'])
            || trim((string)$event->getColumnConfig()['colPos']) === ''
        ) {
            $content = $event->getContent();
            $content .= <<<EOD
                <div data-colpos="1" data-language-uid="0" class="t3-page-ce-wrapper">
                    <div class="t3-page-ce">
                        <div class="t3-page-ce-header">Empty Colpos</div>
                        <div class="t3-page-ce-body">
                            <div class="t3-page-ce-body-inner">
                                <div class="row">
                                    <div class="col-xs-12">
                                        This column has no "colPos". This is only for display Purposes.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
EOD;

            $event->setContent($content);
        }
    }
}
