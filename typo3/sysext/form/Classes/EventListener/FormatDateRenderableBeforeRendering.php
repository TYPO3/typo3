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
use TYPO3\CMS\Form\Domain\Model\FormElements\FormElementInterface;
use TYPO3\CMS\Form\Event\BeforeRenderableIsRenderedEvent;
use TYPO3\CMS\Form\Utility\DateRangeValidatorPatterns;

class FormatDateRenderableBeforeRendering
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
        } elseif (is_string($date) && $date !== '' && !$this->isAbsoluteDate($date)) {
            // Resolve relative date expressions used as defaultValue (e.g. "today", "+1 day")
            $resolved = $this->parseRelativeDate($date);
            if ($resolved !== null) {
                $event->formRuntime[$renderable->getIdentifier()] = $resolved->format('Y-m-d');
            }
        }

        if ($renderable instanceof FormElementInterface) {
            $this->resolveRelativeDateAttributes($renderable);
        }
    }

    /**
     * Resolve relative date expressions in the HTML min/max attributes to absolute Y-m-d format.
     *
     * The HTML5 <input type="date"> element requires min/max values in RFC 3339 full-date
     * format (Y-m-d). When a form definition uses relative expressions like "today" or
     * "-18 years", these must be resolved to absolute dates before rendering.
     */
    private function resolveRelativeDateAttributes(FormElementInterface $renderable): void
    {
        $properties = $renderable->getProperties();
        $fluidAttributes = $properties['fluidAdditionalAttributes'] ?? [];

        $resolved = false;
        foreach (['min', 'max'] as $attribute) {
            $value = $fluidAttributes[$attribute] ?? '';
            if ($value === '' || $this->isAbsoluteDate($value)) {
                continue;
            }

            $date = $this->parseRelativeDate($value);
            if ($date !== null) {
                $fluidAttributes[$attribute] = $date->format('Y-m-d');
                $resolved = true;
            }
        }

        if ($resolved) {
            $renderable->setProperty('fluidAdditionalAttributes', $fluidAttributes);
        }
    }

    private function isAbsoluteDate(string $value): bool
    {
        return (bool)preg_match(DateRangeValidatorPatterns::RFC3339_FULL_DATE_PCRE, $value);
    }

    private function parseRelativeDate(string $value): ?\DateTime
    {
        return DateRangeValidatorPatterns::parseRelativeDateExpression($value);
    }
}
