<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Profile;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Kasper;

/**
 * This testcase is used to check if username is visible in the toolbar.
 */
class UsernameOnAvatarCest
{
    public function _before(Kasper $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(Kasper $I)
    {
        $I->logout();
    }

    /**
     * @param Kasper $I
     */
    public function tryToTest(Kasper $I)
    {
        $I->see('admin', '#typo3-cms-backend-backend-toolbaritems-usertoolbaritem');
    }
}
