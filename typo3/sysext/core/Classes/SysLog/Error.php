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

namespace TYPO3\CMS\Core\SysLog;

/**
 * A class defining possible error types
 */
class Error
{
    public const MESSAGE = 0;
    public const USER_ERROR = 1;
    public const SYSTEM_ERROR = 2;
    public const SECURITY_NOTICE = 3;
    public const WARNING = 4;
}
