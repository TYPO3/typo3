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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Frontend;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class FrontendLoginCest
{
    private string $sidebarSelector = '.sidebar.list-group';
    private string $usernameSelector = '.frame-type-felogin_login input[name="user"]';
    private string $passwordSelector = '.frame-type-felogin_login input[type="password"]';
    private string $submitSelector = '.frame-type-felogin_login input[type=submit]';
    private string $frameSelector = '.frame-type-felogin_login';

    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->waitForElementVisible('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->click('.module-docheader-bar-buttons .btn-group button.dropdown-toggle');
        $I->waitForElementVisible('.module-docheader-bar-buttons .dropdown-menu');
        $I->click('Layout', '.module-docheader-bar-buttons .dropdown-menu');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForElementVisible('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->wait(1);
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->wait(1);
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });
        $I->wait(1);
        $I->see('TYPO3 Styleguide Frontend', '.content');
        $I->scrollTo('//a[contains(., "felogin_login")]');
        $I->click('felogin_login', $this->sidebarSelector);
    }

    public function _after(ApplicationTester $I): void
    {
        // Close FE tab again and switch to BE to avoid side effects
        $I->executeInSelenium(static function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            // Avoid closing the main backend tab (holds the webdriver session) if the test failed to open the frontend tab
            // (All subsequent tests would fail with "[Facebook\WebDriver\Exception\InvalidSessionIdException] invalid session id"
            if (count($handles) > 1) {
                $webdriver->close();
                $firstWindow = current($handles);
                $webdriver->switchTo()->window($firstWindow);
            }
        });
    }

    public function seeLoginFailed(ApplicationTester $I): void
    {
        $I->fillField($this->usernameSelector, 'username');
        $I->fillField($this->passwordSelector, 'wrong password');
        $I->click($this->submitSelector);
        $I->see('Login failure', $this->frameSelector . ' > h3');
    }

    public function seeLoginSuccessAndLogout(ApplicationTester $I): void
    {
        $I->fillField($this->usernameSelector, 'styleguide-frontend-demo');
        $I->fillField($this->passwordSelector, 'password');
        $I->click($this->submitSelector);
        $I->see('You are now logged in as \'styleguide-frontend-demo\'', $this->frameSelector);

        $I->amGoingTo('reload the page to see the logout button');
        $I->scrollTo('//a[contains(., "felogin_login")]');
        $I->click('felogin_login', $this->sidebarSelector);

        $I->see('Username styleguide-frontend-demo', $this->frameSelector);
        $I->amGoingTo('log out');
        $I->click($this->submitSelector);
    }
}
