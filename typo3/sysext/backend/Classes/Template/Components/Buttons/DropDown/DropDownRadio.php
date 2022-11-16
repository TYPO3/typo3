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
 * DropDownRadio
 *
 * This dropdown item type renders an element with an active state.
 * Use this element to display a radio-like selection of a state.
 * When set to active, it will show a dot in front of the icon and
 * text to indicate that this is the current selection.
 *
 * At least 2 of these items need to exist within a dropdown button,
 * so a user has a choice of a state to select.
 *
 * Example: Viewmode -> List / Tiles
 *
 * $item = GeneralUtility::makeInstance(DropDownRadio::class)
 *     ->setHref('#')
 *     ->setActive(true)
 *     ->setLabel('List')
 *     ->setTitle('List')
 *     ->setIcon($this->iconFactory->getIcon('actions-viewmode-list'))
 *     ->setAttributes(['data-type' => 'list']);
 * $dropDownButton->addItem($item);
 *
 * $item = GeneralUtility::makeInstance(DropDownRadio::class)
 *     ->setHref('#')
 *     ->setActive(false)
 *     ->setLabel('Tiles')
 *     ->setTitle('Tiles')
 *     ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles'))
 *     ->setAttributes(['data-type' => 'tiles']);
 * $dropDownButton->addItem($item);
 */
class DropDownRadio extends AbstractDropDownItem implements DropDownItemInterface
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
            $statusIcon = '<span class="text-primary">' . $iconFactory->getIcon('actions-dot', Icon::SIZE_SMALL)->render() . '</span>';
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
