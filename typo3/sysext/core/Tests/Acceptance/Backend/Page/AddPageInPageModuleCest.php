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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Page;

use PHPUnit\Framework\SkippedTestError;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Page and page tree related tests.
 */
class AddPageInPageModuleCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * This test case is used to check if a page can be added with the page module.
     * It also tests to remove the new page with the page tree context menu.
     *
     * @param BackendTester $I
     */
    public function addAndDeletePage(BackendTester $I)
    {
        // @todo: Fix in high load scenarios or throw away
        $this->skipUnstable();

        // Select page module
        $I->wantToTest('Add a page with page module');
        $I->click('Page');

        // New page from root page
        $typo3NavigationContainer = '.scaffold-content-navigation-component';
        $I->waitForElement($typo3NavigationContainer);
        $rootNode = 'a.x-tree-node-anchor > span';
        $rootNodeIcon = '.node.identifier-0_0 .node-icon';
        $rootNodeContextMenuMore = '#contentMenu0 a.list-group-item-submenu';
        //create new wizard
        $contextMenuNew = '#contentMenu1 .list-group-item[data-callback-action=newPageWizard]';

        $I->waitForElement($rootNode);
        $I->click($rootNodeIcon);
        $I->waitForElementVisible($rootNodeContextMenuMore);

        $I->wait(1);
        $I->click($rootNodeContextMenuMore);
        $I->waitForElementVisible($contextMenuNew, 30);
        $I->click($contextMenuNew);

        $I->switchToContentFrame();

        // New page select position wizard
        $I->click('i[title="Insert the new page here"]');

        // FormEngine new page record
        $saveButton = 'body > div > div.module-docheader.t3js-module-docheader > div.module-docheader-bar.module-docheader-bar-buttons.t3js-module-docheader-bar.t3js-module-docheader-bar-buttons > div.module-docheader-bar-column-left > div > div > button:nth-child(1)';
        $I->waitForElement($saveButton);

        // Check empty
        $I->amGoingTo('check empty error');
        $I->wait(2);
        $editControllerDiv = '#EditDocumentController > div';
        $generalTab = $editControllerDiv . ' > div:nth-child(1) > ul > li';
        $classString = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use (
            $generalTab
        ) {
            return $webdriver->findElement(\Facebook\WebDriver\WebDriverBy::cssSelector($generalTab))->getAttribute('class');
        });
        $I->assertContains('has-validation-error', $classString);

        // Add page
        $pageTitleFieldset = $editControllerDiv . ' > div:nth-of-type(1) > div > div.tab-pane:nth-child(1) > fieldset:nth-child(2)';
        $I->seeElement($pageTitleFieldset . ' > div > div.t3js-formengine-validation-marker.has-error');

        $pageTitleInput = $pageTitleFieldset . ' > div > div:nth-child(1) > div > div.form-control-wrap > div.form-wizards-wrap > div.form-wizards-element > div > input';
        $I->fillField($pageTitleInput, 'Testpage');
        $I->click($saveButton);
        $I->waitForElement($pageTitleInput);
        $I->assertEquals('Testpage', $I->grabValueFrom($pageTitleInput), 'Value in input field.');
        $I->dontSeeElement($pageTitleFieldset . ' > div > div.t3js-formengine-validation-marker.has-error');
        $I->switchToMainFrame();

        // Check tree
        $I->waitForElement($typo3NavigationContainer);
        $pageInTree = '#typo3-pagetree-tree > div > div > ul > div > li > ul > li > div > a > span';
        $I->assertEquals('Testpage', $I->grabTextFrom($pageInTree), 'Value in tree.');

        // And delete page from tree
        $pageInTreeIcon = '#typo3-pagetree-tree .icon-apps-pagetree-page-default';
        $delete = '#contentMenu0 .list-group-item[data-callback-action=deleteRecord]';
        $I->click($pageInTreeIcon);
        $I->waitForElement($delete);
        $I->click($delete);
        $yesButtonInPopup = '.modal-dialog button[name=delete]';
        $I->waitForElement($yesButtonInPopup);
        $I->click($yesButtonInPopup);
        $I->wait(2);
        $I->cantSee('Testpage');
    }

    /**
     * @throws SkippedTestError
     */
    protected function skipUnstable()
    {
        throw new SkippedTestError('Test unstable, skipped for now.');
    }
}
