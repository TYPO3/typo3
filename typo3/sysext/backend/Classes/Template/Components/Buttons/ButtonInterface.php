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

namespace TYPO3\CMS\Backend\Template\Components\Buttons;

use TYPO3\CMS\Backend\Template\Components\ComponentInterface;

/**
 * Interface for buttons in the document header.
 *
 * All button types (LinkButton, InputButton, DropDownButton, etc.) must implement
 * this interface to be added to the ButtonBar.
 *
 * This interface extends ComponentInterface, which provides the common contract
 * for all renderable backend components.
 */
interface ButtonInterface extends ComponentInterface {}
