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
 * A class defining possible Database actions
 */
class Database
{
    public const INSERT = 1;
    public const UPDATE = 2;
    public const DELETE = 3;
    public const MOVE = 4;
    public const CHECK = 5;
    public const LOCALIZE = 6;
    public const VERSIONIZE = 7;
    public const PUBLISH = 8;
    public const DISCARD = 9;
}
