<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Login;

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
 * Acceptance test
 */
class EditorCest
{

    /**
     * Login a non-admin user and logout again
     *
     * @param Kasper $I
     */
    public function tryToTest(Kasper $I)
    {
        $I->wantTo('login with editor');
        $I->loginAsEditor();

        // user is redirected to 'about modules' after login, but must not see the 'admin tools' section
        $I->cantSee('Admin tools');

        $I->logout();
        $I->waitForElement('#t3-username');
    }
}
