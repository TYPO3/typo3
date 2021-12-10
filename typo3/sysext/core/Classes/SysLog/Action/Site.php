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

namespace TYPO3\CMS\Core\SysLog\Action;

/**
 * A class defining possible Site actions
 */
class Site
{
    public const CREATE = 1;
    public const UPDATE = 2;
    public const RENAME = 3;
    public const DELETE = 4;
}
