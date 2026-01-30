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
 * Dropdown item with relaxed validation - like DropDownItem but label is optional.
 *
 * Use for web components or custom elements that render their own content.
 */
class DropDownGeneric extends AbstractDropDownItem implements DropDownItemInterface
{
    public function isValid(): bool
    {
        return $this->tag !== '';
    }

    public function render(): string
    {
        return sprintf(
            '<%1$s %2$s>%3$s%4$s</%1$s>',
            $this->getTag(),
            $this->getAttributesString(),
            $this->getRenderedIcon(),
            htmlspecialchars($this->getLabel() ?? '')
        );
    }
}
