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
 * DropDownDivider
 *
 * This dropdown item type renders the divider element.
 *
 * $item = GeneralUtility::makeInstance(DropDownDivider::class);
 * $dropDownButton->addItem($item);
 */
class DropDownDivider implements DropDownItemInterface, \Stringable
{
    public function getType(): string
    {
        return static::class;
    }

    public function isValid(): bool
    {
        return true;
    }

    public function render(): string
    {
        return '<hr class="dropdown-divider">';
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
