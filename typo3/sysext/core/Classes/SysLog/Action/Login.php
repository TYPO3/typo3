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
 * A class defining possible Login actions
 */
class Login
{
    public const LOGIN = 1;
    public const LOGOUT = 2;
    public const ATTEMPT = 3;
    public const SEND_FAILURE_WARNING_EMAIL = 4;
    public const PASSWORD_RESET_REQUEST = 5;
    public const PASSWORD_RESET_ACCOMPLISHED = 6;
}
