<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Page;

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
 * This testcase is used to check if a page can be added with the page module.
 */
class AddPageInPageModuleCest
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
     * @env firefox
     * @env chrome
     * @param Kasper $I
     */
    public function tryToTest(Kasper $I)
    {
        $I->wantToTest('Add a page with page module');
        $I->click('Page');
        $I->waitForElement('#typo3-navigationContainer');
        $rootNode = '.x-tree-node:nth-child(1) > div > a';
        $contextMenuNew = '#typo3-pagetree-contextmenu > ul > li:nth-child(2) > a > span.x-menu-item-text';
        $I->waitForElement($rootNode);
        $I->clickWithRightButton($rootNode);
        $I->waitForElement($contextMenuNew);
        $I->click($contextMenuNew);
        $I->switchToIFrame('content');
        $saveButton = 'body > div > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > div > button:nth-child(1)';
        $I->waitForElement($saveButton);

        // Check empty
        $I->amGoingTo('check empty error');
        $I->click($saveButton);
        $I->wait(2);
        $generalTab = '#EditDocumentController > div > div:nth-child(1) > ul > li';
        $classString = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use ($generalTab) {
            return $webdriver->findElement(\WebDriverBy::cssSelector($generalTab))->getAttribute('class');
        });
        $I->assertNotEquals(false, strpos($classString, 'has-validation-error'));
        $I->switchToIFrame();
    }

}