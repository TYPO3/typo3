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

namespace TYPO3\CMS\Form\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;

class BeforeRenderableIsRenderedEventListener
{
    #[AsEventListener('form-framework/format-date-before-rendered')]
    public function __invoke(BeforeRenderableIsRenderedEvent $event): void
    {
        $renderable = $event->renderable;
        if ($renderable->getType() !== 'Date') {
            return;
        }
        $date = $event->formRuntime[$renderable->getIdentifier()];
        if ($date instanceof \DateTime) {
            // @see https://www.w3.org/TR/2011/WD-html-markup-20110405/input.date.html#input.date.attrs.value
            // 'Y-m-d' = https://tools.ietf.org/html/rfc3339#section-5.6 -> full-date
            $event->formRuntime[$renderable->getIdentifier()] = $date->format('Y-m-d');
        }
    }
}
