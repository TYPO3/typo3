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
 * This dropdown item type renders a simple element.
 * Use this element if you need a link, button.
 *
 * Example:
 *
 * ```
 * $item = GeneralUtility::makeInstance(DropDownItem::class)
 *     ->setTag('a')
 *     ->setHref('#')
 *     ->setLabel('Label')
 *     ->setTitle('Title')
 *     ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *     ->setAttributes(['data-value' => '123']);
 * $dropDownButton->addItem($item);
 * ```
 */
class DropDownItem extends AbstractDropDownItem implements DropDownItemInterface
{
    public function render(): string
    {
        return sprintf(
            '<%1$s %2$s>%3$s%4$s</%1$s>',
            $this->getTag(),
            $this->getAttributesString(),
            $this->getIcon()?->render() ?? '',
            htmlspecialchars($this->getLabel())
        );
    }
}
