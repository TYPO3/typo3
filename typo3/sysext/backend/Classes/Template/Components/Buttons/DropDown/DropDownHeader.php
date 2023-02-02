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
 * DropDownHeader
 *
 * This dropdown item type renders a noninteractive text
 * element to group items and gives more meaning to a set
 * of options.
 *
 * $item = GeneralUtility::makeInstance(DropDownHeader::class)
 *     ->setLabel('Label');
 * $dropDownButton->addItem($item);
 */
class DropDownHeader implements DropDownItemInterface, \Stringable
{
    protected ?string $label = null;

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): self
    {
        $this->label = $label;
        return $this;
    }

    public function getType(): string
    {
        return static::class;
    }

    public function isValid(): bool
    {
        return $this->getLabel() !== null && trim($this->getLabel()) !== '';
    }

    public function render(): string
    {
        return '<h6 class="dropdown-header">' . htmlspecialchars(trim($this->getLabel())) . '</h6>';
    }

    public function __toString(): string
    {
        return $this->render();
    }
}
