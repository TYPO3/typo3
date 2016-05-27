<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Topbar;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;

/**
 * Topbar username and avatar module tests.
 */
class UsernameOnAvatarCest
{
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('content');
        $I->waitForText('Web>Page module');
        $I->switchToIFrame();
    }

    /**
     * This test case is used to check if username is visible in the toolbar.
     *
     * @param Admin $I
     */
    public function usernameIsShown(Admin $I)
    {
        $I->see('admin', '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem');
    }
}
