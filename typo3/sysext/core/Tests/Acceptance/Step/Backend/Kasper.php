<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Step\Backend;

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

class Kasper extends \AcceptanceTester
{
    /**
     * Login as user "admin" with password "password".
     * This user was added as fixture during test bootstrap.
     *
     * @return void
     */
    public function loginAsAdmin()
    {
        $I = $this;
        $I->login('admin', 'password');
    }

    /**
     * Login as user "editor" with password "passowrd".
     * This user was added as fixture during test bootsrap.
     *
     * @return void
     */
    public function loginAsEditor()
    {
        $I = $this;
        $I->login('editor', 'password');
    }

    /**
     * Logout user by clicking logout button in toolbar
     *
     * @return void
     */
    public function logout()
    {
        $I = $this;
        $I->amGoingTo('step backend login');
        $I->amGoingTo('logout');
        $I->click('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem > a');
        $I->click('Logout');
        $I->waitForElement('#t3-username');
    }

    /**
     * Helper method for user login.
     *
     * @param string $username
     * @param string $password
     */
    protected function login(string $username, string $password)
    {
        $I = $this;
        $I->amGoingTo('Step\Backend\Login username: ' . $username);
        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username');
        $I->fillField('#t3-username', $username);
        $I->fillField('#t3-password', $password);
        $I->click('#t3-login-submit-section > button');
        // wait for the next to element to indicate if the backend was loaded successful
        $I->waitForElement('.nav', 30);
        $I->waitForElement('#typo3-contentContainer iframe', 30);
        $I->seeCookie('be_lastLoginProvider');
        $I->seeCookie('be_typo_user');
    }
}
