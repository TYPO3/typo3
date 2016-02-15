<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend;

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

/**
 * Acceptance test
 */
class LoginEditorCest
{

    /**
     * Login a non-admin user and logout again
     *
     * @param \AcceptanceTester $I
     */
    public function tryToTest(\AcceptanceTester $I)
    {
        $I->wantTo('login with admin');
        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username');
        $I->fillField('#t3-username', 'editor');
        $I->fillField('#t3-password', 'password');
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('.nav');
        $I->seeCookie('be_lastLoginProvider');
        $I->seeCookie('be_typo_user');
        // user is redirected to 'about modules' after login, but must not see the 'admin tools' section
        $I->cantSee('Admin tools');
        $I->click('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem > a');
        $I->click('Logout');
        $I->waitForElement('#t3-username');
    }
}
