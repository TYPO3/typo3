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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Frontend;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

class FrontendLoginCest
{
    protected string $sidebarSelector = '.sidebar.list-group';
    protected string $usernameSelector = '.frame-type-felogin_login input[name="user"]';
    protected string $passwordSelector = '.frame-type-felogin_login input[type="password"]';
    protected string $submitSelector = '.frame-type-felogin_login input[type=submit]';
    protected string $frameSelector = '.frame-type-felogin_login';

    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('Page');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
        $I->click('.t3js-module-docheader-bar a[title="View webpage"]');
        $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            $handles = $webdriver->getWindowHandles();
            $lastWindow = end($handles);
            $webdriver->switchTo()->window($lastWindow);
        });

        $I->scrollTo('//a[contains(., "felogin_login")]');
        $I->click('felogin_login', $this->sidebarSelector);
    }

    /**
     * @param BackendTester $I
     */
    public function seeLoginFailed(BackendTester $I): void
    {
        $I->fillField($this->usernameSelector, 'username');
        $I->fillField($this->passwordSelector, 'wrong password');
        $I->click($this->submitSelector);
        $I->see('Login failure', $this->frameSelector . ' > h3');
    }

    /**
     * @param BackendTester $I
     */
    public function seeLoginSuccessAndLogout(BackendTester $I): void
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
