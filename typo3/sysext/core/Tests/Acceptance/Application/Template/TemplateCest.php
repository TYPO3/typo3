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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Template;

use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class TemplateCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');

        $I->switchToMainFrame();
        $I->see('TypoScript');
        $I->click('TypoScript');
        $I->switchToContentFrame();
    }

    public function pagesWithNoTemplateShouldShowButtonsToCreateTemplates(ApplicationTester $I): void
    {
        $I->wantTo('show TypoScript records overview on root page (uid = 0)');
        // Select the root page
        $I->switchToMainFrame();
        // click on PID=0
        $I->clickWithLeftButton('#typo3-pagetree-treeContainer [role="treeitem"][data-id="0"] .node-contentlabel');

        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->see('Global overview of all pages in the database containing one or more TypoScript records.');

        $I->wantTo('show TypoScript records overview on website root page (uid = 1 and pid = 0)');
        $I->switchToMainFrame();
        // click on website root page
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->waitForElementVisible('div.module-docheader select.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('div.module-docheader select.t3-js-jumpMenuBox', 'Edit TypoScript Record');
        $I->waitForText('No TypoScript record');
        $I->see('No TypoScript record on the current page');
        $I->see('You need to create a TypoScript record in order to edit your configuration.');
    }

    public function addANewSiteTemplate(ApplicationTester $I): void
    {
        $I->waitForText('TypoScript records');
        $I->wantTo('create a new root TypoScript record');
        $I->switchToMainFrame();
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Constant Editor');
        $I->wait(3);
        $I->waitForText('Root TypoScript record');
        $I->click("//input[@name='newWebsite']");

        $I->wantTo('change to Override TypoScript and see the TypoScript record overview table');
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Edit TypoScript Record');
        $I->wait(3);
        $I->waitForElement('.table-striped');
        $I->see('Title');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole TypoScript record');
        $I->click('Edit the whole TypoScript record');

        $I->wantTo('change the title and save the TypoScsript record');
        $I->waitForElement('#EditDocumentController');
        // fill title input field
        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_template]") and contains(@data-formengine-input-name, "[title]")]', 'Acceptance Test Site');
        $I->click("//button[@name='_savedok']");
        $I->waitForElementNotVisible('#t3js-ui-block', 30);
        $I->waitForElement('#EditDocumentController');
        $I->waitForElementNotVisible('#t3js-ui-block');

        $codeMirrorSelector = 'typo3-t3editor-codemirror[name$="[config]"]';

        $I->wantTo('change the setup, save the TypoScript record and close the form');
        $I->waitForElementVisible($codeMirrorSelector);
        $I->executeJS("const codeMirror = document.querySelector('" . $codeMirrorSelector . "'); const config = codeMirror.getContent().replace('HELLO WORLD!', 'Hello Acceptance Test!'); codeMirror.setContent(config)");
        $I->switchToMainFrame();
        $I->waitForElementNotVisible('typo3-notification-message', 20);
        $I->switchToContentFrame();
        $I->click('//*/button[@name="_savedok"][1]');
        $I->wait(10);
        $I->waitForElement('a.t3js-editform-close');
        $I->click('a.t3js-editform-close');

        $I->wantTo('see the changed title');
        $I->waitForElement('.table-striped');
        $I->see('Acceptance Test Site');

        $I->wantTo('change the TypoScript record within the TypoScript Object Browser');
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Active TypoScript');
        $I->click('#panel-tree-heading-setup');
        $I->waitForText('Setup');
        // find and open page in tree
        $I->waitForText('page = PAGE');
        $I->click('//span[@class="treelist-label"]/a[text()=\'page\']/../../../typo3-backend-tree-node-toggle');
        // find and open page.10 in tree
        $I->waitForText('10 = TEXT');
        $I->click('//span[@class="treelist-label"]/a[text()=\'page\']/../../../div/ul//span[@class="treelist-label"]/a[text()=\'10\']/../../../typo3-backend-tree-node-toggle');
        // find and edit page.10.value in tree
        $I->waitForText('value = Hello Acceptance Test!');
        $I->click('//span[@class="treelist-label"]/a[text()=\'10\']/../../../div/ul//span[@class="treelist-label"]/a[text()=\'value\']');
        $I->waitForText('page.10.value =');
        $I->fillField('//input[@name="value"]', 'HELLO WORLD!');
        $I->click('//input[@name="updateValue"]');
        $I->wait(2);
        $I->waitForText('Line added to current TypoScript record');
        $I->see('page.10.value = HELLO WORLD!');
        $I->see('value = HELLO WORLD!');
    }

    public function checkClosestTemplateButton(ApplicationTester $I, PageTree $pageTree, Scenario $scenario): void
    {
        $I->wantTo('click on the button to go to the closest page with a TypoScript record');
        $I->switchToMainFrame();

        $usesSiteSets = str_contains($scenario->current('env'), 'sets');
        if ($usesSiteSets) {
            $pageTree->openPath(['styleguide frontend demo', 'template records', 'template record subsite']);
        } else {
            $pageTree->openPath(['styleguide frontend demo', 'menu_sitemap_pages']);
        }
        $I->switchToContentFrame();
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Constant Editor');
        $I->waitForText('No TypoScript record');
        $I->see('No TypoScript record on the current page');
        $I->see('You need to create a TypoScript record in order to edit your configuration.');
        $I->seeLink('Select this TypoScript record');
        $I->clickWithLeftButton('//a[text()[normalize-space(.) = "Select this TypoScript record"]]');

        $I->wantTo('see that the page has a TypoScript record');
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Edit TypoScript Record');
        $I->waitForElement('.table-striped');
        $I->waitForText('Title');
        $I->see('Title');
        $I->waitForText('Description');
        $I->see('Description');
        $I->waitForText('Constants');
        $I->see('Constants');
        $I->waitForText('Setup');
        $I->see('Setup');
        $I->waitForText('Edit the whole TypoScript record');
        $I->see('Edit the whole TypoScript record');
        $I->click('Edit the whole TypoScript record');
        // Avoid race condition:
        // SEVERE - http://web/typo3/sysext/backend/Resources/Public/JavaScript/code-editor/autocomplete/ts-ref.js?bust=[…]
        // 12:613 Uncaught TypeError: Cannot convert undefined or null to object
        $I->waitForElementNotVisible('#nprogress', 120);
    }

    public function createExtensionTemplate(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->wantTo('see the button to create an additional TypoScript record');
        $I->switchToMainFrame();
        $pageTree->openPath(['styleguide frontend demo', 'menu_sitemap_pages']);
        $I->clickWithLeftButton('//*[text()=\'menu_sitemap_pages\']');
        $I->switchToContentFrame();
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Constant Editor');
        $I->waitForText('No TypoScript record');
        $I->see('No TypoScript record on the current page');
        $I->see('You need to create a TypoScript record in order to edit your configuration.');
        $I->clickWithLeftButton('//input[@name=\'createExtension\']');
        $I->wantTo('see that the page has a TypoScript record');
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Edit TypoScript Record');
        $I->waitForElement('.table-striped');
        $I->waitForText('Title');
        $I->see('Title');
        $I->waitForText('Description');
        $I->see('Description');
        $I->waitForText('Constants');
        $I->see('Constants');
        $I->waitForText('Setup');
        $I->see('Setup');
        $I->waitForText('Edit the whole TypoScript record');
        $I->see('Edit the whole TypoScript record');
        $I->click('Edit the whole TypoScript record');
        $I->waitForText('Edit TypoScript record "+ext" on page "menu_sitemap_pages"');
        $I->wait(2); // wait for code mirror init
    }

    /**
     * @depends addANewSiteTemplate
     */
    public function searchInTypoScriptActive(ApplicationTester $I): void
    {
        $I->wantTo('Open the TypoScript Object Browser and search a keyword.');
        $I->switchToMainFrame();
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->waitForElementVisible('.t3-js-jumpMenuBox');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->selectOption('.t3-js-jumpMenuBox', 'Active TypoScript');
        $I->waitForText('Active TypoScript for record');
        $I->amGoingTo('type "styles" into the search field and submit.');
        $I->fillField('#searchValue', 'styles');
        $I->waitForText('Setup');
        $I->waitForText('1 search match(es)');
        $I->seeInSource('<strong data-markjs="true" class="text-danger">styles</strong>');
    }
}
