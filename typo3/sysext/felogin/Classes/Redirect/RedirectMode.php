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

namespace TYPO3\CMS\FrontendLogin\Redirect;

/**
 * Contains the different redirect modes types
 *
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
final class RedirectMode
{
    public const LOGIN = 'login';
    public const LOGOUT = 'logout';
    public const LOGIN_ERROR = 'loginError';
    public const GETPOST = 'getpost';
    public const USER_LOGIN = 'userLogin';
    public const GROUP_LOGIN = 'groupLogin';
    public const REFERER = 'referer';
    public const REFERER_DOMAINS = 'refererDomains';
}
