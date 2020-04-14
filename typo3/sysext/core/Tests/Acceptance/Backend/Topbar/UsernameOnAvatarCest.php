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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Topbar;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Topbar username and avatar module tests.
 */
class UsernameOnAvatarCest
{
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * This test case is used to check if username is visible in the toolbar.
     *
     * @param BackendTester $I
     */
    public function usernameIsShown(BackendTester $I)
    {
        $I->see('admin', '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem');
    }
}
