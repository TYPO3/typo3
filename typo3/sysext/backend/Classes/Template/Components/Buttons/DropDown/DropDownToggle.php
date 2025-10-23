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

namespace TYPO3\CMS\Backend\Template\Components\Buttons\DropDown;

/**
 * This dropdown item type renders an element with an active state.
 * When set to active, it will show a checkmark in front of the icon
 * and text to indicate the current state.
 *
 * Example:
 *
 * ```
 * $item = GeneralUtility::makeInstance(DropDownToggle::class)
 *     ->setHref('#')
 *     ->setActive(true)
 *     ->setLabel('Label')
 *     ->setTitle('Title')
 *     ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *     ->setAttributes(['data-value' => '123']);
 * $dropDownButton->addItem($item);
 * ```
 */
class DropDownToggle extends AbstractDropDownItem implements DropDownItemInterface
{
    public function render(): string
    {
        // Status Icon
        $this->setAttribute('data-dropdowntoggle-status', $this->isActive() ? 'active' : 'inactive');
        return sprintf(
            '<%1$s %2$s><span class="dropdown-item-status"></span>%3$s%4$s</%1$s>',
            $this->getTag(),
            $this->getAttributesString(),
            $this->getRenderedIcon(),
            htmlspecialchars($this->getLabel())
        );
    }
}
