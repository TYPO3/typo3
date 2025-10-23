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

use TYPO3\CMS\Backend\Template\Components\ComponentInterface;

/**
 * Interface for dropdown items that can be added to a DropDownButton.
 *
 * Dropdown items include interactive elements (DropDownItem, DropDownRadio, DropDownToggle)
 * and structural elements (DropDownHeader, DropDownDivider).
 *
 * This interface extends ComponentInterface, which provides the common contract
 * for all renderable backend components.
 */
interface DropDownItemInterface extends ComponentInterface {}
