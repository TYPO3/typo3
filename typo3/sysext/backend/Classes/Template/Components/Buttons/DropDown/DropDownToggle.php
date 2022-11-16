<?php

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

namespace TYPO3\CMS\Backend\Template\Components\Buttons\DropDown;

use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DropDownToggle
 *
 * This dropdown item type renders an element with an active state.
 * When set to active, it will show a checkmark in front of the icon
 * and text to indicate the current state.
 *
 * $item = GeneralUtility::makeInstance(DropDownToggle::class)
 *     ->setHref('#')
 *     ->setActive(true)
 *     ->setLabel('Label')
 *     ->setTitle('Title')
 *     ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *     ->setAttributes(['data-value' => '123']);
 * $dropDownButton->addItem($item);
 */
class DropDownToggle extends AbstractDropDownItem implements DropDownItemInterface
{
    protected bool $active = false;

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): self
    {
        $this->active = $active;
        return $this;
    }

    public function render(): string
    {
        // Status Icon
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        if ($this->isActive()) {
            $statusIcon = '<span class="text-primary">' . $iconFactory->getIcon('actions-check', Icon::SIZE_SMALL)->render() . '</span>';
        } else {
            $statusIcon = $iconFactory->getIcon('empty-empty', Icon::SIZE_SMALL)->render();
        }

        return '<' . $this->getTag() . ' ' . $this->getAttributesString() . '>'
            . $statusIcon
            . $this->getRenderedIcon()
            . htmlspecialchars($this->getLabel())
            . '</' . $this->getTag() . '>';
    }
}
