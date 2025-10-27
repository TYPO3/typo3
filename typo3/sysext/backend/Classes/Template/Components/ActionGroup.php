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

namespace TYPO3\CMS\Backend\Template\Components;

/**
 * Defines groups for record list / file list actions.
 * Currently, there are only two of them, which can be used to move Buttons between the Primary Group and the Secondary Group via Events.
 */
enum ActionGroup
{
    case primary;
    case secondary;
}
