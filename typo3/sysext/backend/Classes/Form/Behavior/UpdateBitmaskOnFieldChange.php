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

namespace TYPO3\CMS\Backend\Form\Behavior;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Updates bitmask values for multi-checkboxes.
 */
class UpdateBitmaskOnFieldChange implements OnFieldChangeInterface
{
    protected int $position;
    protected int $total;
    protected bool $invert;
    protected string $elementName;

    public function __construct(int $position, int $total, bool $invert, string $elementName)
    {
        $this->position = $position;
        $this->total = $total;
        $this->invert = $invert;
        $this->elementName = $elementName;
    }

    public function __toString(): string
    {
        return $this->generateInlineJavaScript();
    }

    public function toArray(): array
    {
        return [
            'name' => 'typo3-backend-form-update-bitmask',
            'data' => [
                'position' => $this->position,
                'total' => $this->total,
                'invert' => $this->invert,
                'elementName' => $this->elementName,
            ],
        ];
    }

    protected function generateInlineJavaScript(): string
    {
        $mask = 2 ** $this->position;
        $unmask = (2 ** $this->total) - $mask - 1;
        $elementRef = 'document.editform[' . GeneralUtility::quoteJSvalue($this->elementName) . ']';
        return sprintf(
            '%s.value = %sthis.checked ? (%s.value|%d) : (%s.value&%d);'
                . " %s.dispatchEvent(new Event('change', {bubbles: true, cancelable: true}));",
            $elementRef,
            $this->invert ? '!' : '',
            $elementRef,
            $mask,
            $elementRef,
            $unmask,
            $elementRef
        );
    }
}
