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

use TYPO3\TestingFramework\Core\Acceptance\Support\Helper\Topbar;

/**
 * Various backend login related tests
 */
class BackendLoginCest
{
    /**
     * Call backend login page and verify login button changes color on mouse over,
     * verifies page is available and CSS is properly loaded.
     *
     * @param \AcceptanceTester $I
     */
    public function loginButtonMouseOver(\AcceptanceTester $I)
    {
        $I->wantTo('check login functions');
        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username', 10);
        $I->wantTo('mouse over css change login button');

        // Make sure mouse is not over submit button from a previous test
        $I->moveMouseOver('#t3-username');
        $bs = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\WebDriverBy::cssSelector('#t3-login-submit'))->getCSSValue('box-shadow');
        });

        $I->moveMouseOver('#t3-login-submit');
        $I->wait(1);
        $bsmo = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\WebDriverBy::cssSelector('#t3-login-submit'))->getCSSValue('box-shadow');
        });
        $I->assertFalse($bs === $bsmo);
    }

    /**
     * Call backend login page and submit invalid login data.
     * Verifies login is not accepted and an error message is rendered.
     *
     * @param \AcceptanceTester $I
     */
    public function loginDeniedWithInvalidCredentials(\AcceptanceTester $I)
    {
        $I->wantTo('check login functions');
        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username');

        $I->wantTo('check empty credentials');
        $required = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\WebDriverBy::cssSelector('#t3-username'))->getAttribute('required');
        });
        $I->assertEquals('true', $required, '#t3-username');

        $required = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) {
            return $webdriver->findElement(\WebDriverBy::cssSelector('#t3-password'))->getAttribute('required');
        });
        $I->assertEquals('true', $required, '#t3-password');

        $I->wantTo('use bad credentials');
        $I->fillField('#t3-username', 'testify');
        $I->fillField('#t3-password', '123456');
        $I->click('#t3-login-submit-section > button');
        $I->waitForElement('#t3-login-error', 30);
        $I->see('Your login attempt did not succeed');
    }

    /**
     * Login a admin user and logout again
     *
     * @param \AcceptanceTester $I
     */
    public function loginWorksAsAdminUser(\AcceptanceTester $I)
    {
        $I->wantTo('login with admin');
        $this->login($I, 'admin', 'password');

        // user is redirected to 'about modules' after login, and must see the 'admin tools' section
        $I->see('Admin tools');

        $this->logout($I);
        $I->waitForElement('#t3-username');
    }

    /**
     * Login as a non-admin user, check visible modules and logout again
     *
     * @param \AcceptanceTester $I
     */
    public function loginWorksAsEditorUser(\AcceptanceTester $I)
    {
        $this->login($I, 'editor', 'password');

        // user is redirected to 'about modules' after login, but must not see the 'admin tools' section
        $I->cantSee('Admin tools', '#menu');

        $topBarItemSelector = Topbar::$containerSelector . ' ' . Topbar::$dropdownToggleSelector . ' *';

        // can see bookmarks
        $I->seeElement($topBarItemSelector, ['title' => 'Bookmarks']);

        // can't see clear cache
        $I->cantSeeElement($topBarItemSelector, ['title' => 'Clear cache']);

        $this->logout($I);
        $I->waitForElement('#t3-username');
    }

    /**
     * Helper method for user login on backend login screen
     *
     * @param \AcceptanceTester $I
     * @param string $username
     * @param string $password
     */
    protected function login(\AcceptanceTester $I, string $username, string $password)
    {
        $I->amGoingTo('Step\Backend\Login username: ' . $username);
        $I->amOnPage('/typo3/index.php');
        $I->waitForElement('#t3-username');
        $I->fillField('#t3-username', $username);
        $I->fillField('#t3-password', $password);
        $I->click('#t3-login-submit-section > button');
        // wait for the next to element to indicate if the backend was loaded successful
        $I->waitForElement('.nav', 30);
        $I->waitForElement('.scaffold-content iframe', 30);
        $I->seeCookie('be_lastLoginProvider');
        $I->seeCookie('be_typo_user');
    }

    /**
     * Logout user by clicking logout button in toolbar
     *
     * @param \AcceptanceTester $I
     */
    protected function logout(\AcceptanceTester $I)
    {
        $I->amGoingTo('step backend login');
        $I->amGoingTo('logout');
        // ensure that we are on the main frame
        $I->switchToIFrame();
        $I->click('#typo3-cms-backend-backend-toolbaritems-usertoolbaritem > a');
        $I->click('Logout');
        $I->waitForElement('#t3-username');
    }
}
