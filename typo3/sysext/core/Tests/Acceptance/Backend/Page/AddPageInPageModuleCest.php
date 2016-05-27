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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;

/**
 * Page and page tree related tests.
 */
class AddPageInPageModuleCest
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
     * This test case is used to check if a page can be added with the page module.
     * It also tests to remove the new page with the page tree context menu.
     *
     * @param Admin $I
     */
    public function addAndDeletePage(Admin $I)
    {
        // Select page module
        $I->wantToTest('Add a page with page module');
        $I->click('Page');

        // New page from root page
        $typo3NavigationContainer = '#typo3-navigationContainer';
        $I->waitForElement($typo3NavigationContainer);
        $rootNode = 'a.x-tree-node-anchor > span';
        $rootNodeIcon = '#extdd-1 > span.t3js-icon.icon.icon-size-small.icon-state-default.icon-apps-pagetree-root';
        $contextMenuNew = '#typo3-pagetree-contextmenu > ul > li.x-menu-list-item:nth-of-type(2) > a > span.x-menu-item-text';
        $I->waitForElement($rootNode);
        $I->click($rootNodeIcon);
        $I->waitForElement($contextMenuNew);
        $I->click($contextMenuNew);

        // Switch to content frame
        $I->switchToIFrame('content');

        // New page select position wizard
        $I->click('i[title="Insert the new page here"]');

        // FormEngine new page record
        $saveButton = 'body > div > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > div > button:nth-child(1)';
        $I->waitForElement($saveButton);

        // Check empty
        $I->amGoingTo('check empty error');
        $I->click($saveButton);
        $I->wait(2);
        $editControllerDiv = '#EditDocumentController > div';
        $generalTab = $editControllerDiv . ' > div:nth-child(1) > ul > li';
        $classString = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use (
            $generalTab
        ) {
            return $webdriver->findElement(\WebDriverBy::cssSelector($generalTab))->getAttribute('class');
        });
        $I->assertContains('has-validation-error', $classString);

        // Add page
        $pageTitle = $editControllerDiv . ' > div:nth-child(1) > div > div.tab-pane:nth-child(1) > fieldset:nth-child(2) > div > div:nth-child(1) > div > div.form-control-wrap > div > input';
        $I->fillField($pageTitle, 'Testpage');
        $I->click($saveButton);
        $I->waitForElement($pageTitle);
        $I->assertEquals('Testpage', $I->grabValueFrom($pageTitle), 'Value in input field.');
        $I->switchToIFrame();

        // Check tree
        $I->waitForElement($typo3NavigationContainer);
        $pageInTree = '#typo3-pagetree-tree > div > div > ul > div > li > ul > li > div > a > span';
        $I->assertEquals('Testpage', $I->grabTextFrom($pageInTree), 'Value in tree.');

        // And delete page from tree
        $pageInTreeIcon = '#typo3-pagetree-tree > div > div > ul > div > li > ul > li > div > span.t3js-icon.icon.icon-size-small.icon-state-default.icon-apps-pagetree-page-default';
        $pageActions = '#typo3-pagetree-contextmenu > ul > li:nth-child(8) > a > span.x-menu-item-text';
        $delete = '#typo3-pagetree-contextmenu-sub1 > ul > li:nth-child(6) > a > span.x-menu-item-text';
        $I->click($pageInTreeIcon);
        $I->waitForElement('#typo3-pagetree-contextmenu');
        $I->waitForElement($pageActions);
        $I->moveMouseOver($pageActions);
        $I->waitForElement('#typo3-pagetree-contextmenu-sub1');
        $I->click($delete);
        $yesButtonPopup = '#main > div.x-window.x-window-plain.x-window-dlg > div.x-window-bwrap > div.x-window-bl > div > div > div > div.x-panel-fbar.x-small-editor.x-toolbar-layout-ct > table > tbody > tr > td.x-toolbar-left > table > tbody > tr > td:nth-child(2) > table > tbody > tr:nth-child(2) > td.x-btn-mc > em > button';
        $I->waitForElement($yesButtonPopup);
        $I->click($yesButtonPopup);
        $I->wait(2);
        $I->cantSee('Testpage');
    }
}
