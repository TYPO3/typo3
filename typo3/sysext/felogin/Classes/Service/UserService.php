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

namespace TYPO3\CMS\FrontendLogin\Service;

use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;

/**
 * @internal this is a concrete TYPO3 implementation and solely used for EXT:felogin and not part of TYPO3's Core API.
 */
class UserService
{
    /**
     * @var FrontendUserAuthentication
     */
    protected $feUser;

    public function __construct()
    {
        $this->feUser = $GLOBALS['TSFE']->fe_user;
    }

    /**
     * Get user- and sessiondata from Frontend User
     *
     * @return array
     */
    public function getFeUserData(): array
    {
        return $this->feUser->user;
    }

    /**
     * Should return true if a cookie warning is needed to be displayed
     *
     * @return bool
     */
    public function cookieWarningRequired(): bool
    {
        return !$this->feUser->isCookieSet();
    }

    public function getFeUserGroupData(): array
    {
        return $this->feUser->userGroups;
    }

    public function getFeUserTable(): string
    {
        return $this->feUser->user_table;
    }

    public function getFeUserGroupTable(): string
    {
        return $this->feUser->usergroup_table;
    }

    public function getFeUserIdColumn(): string
    {
        return $this->feUser->userid_column;
    }
}
