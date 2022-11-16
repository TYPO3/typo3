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

/**
 * DropDownItem
 *
 * This dropdown item type renders a simple element.
 * Use this element if you need a link, button.
 *
 * $item = GeneralUtility::makeInstance(DropDownItem::class)
 *     ->setTag('a')
 *     ->setHref('#')
 *     ->setLabel('Label')
 *     ->setTitle('Title')
 *     ->setIcon($this->iconFactory->getIcon('actions-heart'))
 *     ->setAttributes(['data-value' => '123']);
 * $dropDownButton->addItem($item);
 */
class DropDownItem extends AbstractDropDownItem implements DropDownItemInterface
{
    protected string $tag = 'a';

    public function setTag(string $tag): self
    {
        $this->tag = htmlspecialchars(trim($tag));
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    public function render(): string
    {
        return '<' . $this->getTag() . ' ' . $this->getAttributesString() . '>'
            . ($this->getIcon() ? $this->getIcon()->render() : '')
            . htmlspecialchars($this->getLabel())
            . '</' . $this->getTag() . '>';
    }
}
