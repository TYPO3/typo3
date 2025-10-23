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

use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This dropdown item type renders an element with an active state.
 * Use this element to display a radio-like selection of a state.
 * When set to active, it will show a dot in front of the icon and
 * text to indicate that this is the current selection.
 *
 * At least 2 of these items need to exist within a dropdown button,
 * so a user has a choice of a state to select.
 *
 * Example:
 *
 * ```
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
 * ```
 */
class DropDownRadio extends AbstractDropDownItem implements DropDownItemInterface
{
    public function render(): string
    {
        $iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $statusIcon = $this->isActive()
            ? '<span class="text-primary">' . $iconFactory->getIcon('actions-dot', IconSize::SMALL)->render() . '</span>'
            : $iconFactory->getIcon('empty-empty', IconSize::SMALL)->render();

        return sprintf(
            '<%1$s %2$s>%3$s%4$s%5$s</%1$s>',
            $this->getTag(),
            $this->getAttributesString(),
            $statusIcon,
            $this->getRenderedIcon(),
            htmlspecialchars($this->getLabel())
        );
    }
}
