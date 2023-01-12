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

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Template tests
 */
class TemplateCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');

        $I->switchToMainFrame();
        $I->see('TypoScript');
        $I->click('TypoScript');
        $I->waitForElement('svg .nodes .node');
        $I->switchToContentFrame();
    }

    public function pagesWithNoTemplateShouldShowButtonsToCreateTemplates(ApplicationTester $I): void
    {
        $I->wantTo('show TypoScript records overview on root page (uid = 0)');
        // Select the root page
        $I->switchToMainFrame();
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_0 text.node-name');

        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->see('Global overview of all pages in the database containing one or more TypoScript records.');

        $I->wantTo('show TypoScript records overview on website root page (uid = 1 and pid = 0)');
        $I->switchToMainFrame();
        // click on website root page
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->selectOption('div.module-docheader select.t3-js-jumpMenuBox', 'Override TypoScript');
        $I->waitForText('No TypoScript record');
        $I->see('There is no TypoScript record on this page!');
        $I->see('You need to create a TypoScript record in order to edit your configuration.');
    }

    public function addANewSiteTemplate(ApplicationTester $I): void
    {
        $I->waitForText('TypoScript records');
        $I->wantTo('create a new root TypoScript record');
        $I->switchToMainFrame();
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->selectOption('.t3-js-jumpMenuBox', 'Constant Editor');
        $I->waitForText('Root TypoScript record');
        $I->click("//input[@name='newWebsite']");

        $I->wantTo('change to Override TypoScript and see the TypoScript record overview table');
        $I->selectOption('.t3-js-jumpMenuBox', 'Override TypoScript');
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

        $I->wantTo('change the setup, save the TypoScript record and close the form');
        // grab and fill setup textarea
        $config = $I->grabTextFrom('//textarea[contains(@data-formengine-input-name, "data[sys_template]") and contains(@data-formengine-input-name, "[config]")]');
        $config = str_replace('HELLO WORLD!', 'Hello Acceptance Test!', $config);
        $I->fillField('//textarea[contains(@data-formengine-input-name, "data[sys_template]") and contains(@data-formengine-input-name, "[config]")]', $config);

        $I->click('//*/button[@name="_savedok"][1]');
        $I->waitForElement('a.t3js-editform-close');
        $I->click('a.t3js-editform-close');

        $I->wantTo('see the changed title');
        $I->waitForElement('.table-striped');
        $I->see('Acceptance Test Site');

        $I->wantTo('change the TypoScript record within the TypoScript Object Browser');
        $I->selectOption('.t3-js-jumpMenuBox', 'TypoScript Tree');
        $I->waitForText('Setup');
        $I->click('Setup');
        // find and open [page] in tree
        $I->waitForText('[page] = PAGE');
        $I->click('//span[@class="list-tree-label"]/a[text()=\'page\']/../../a');
        // find and open [page][10] in tree
        $I->waitForText('[10] = TEXT');
        $I->click('//span[@class="list-tree-label"]/a[text()=\'page\']/../../../ul//span[@class="list-tree-label"]/a[text()=\'10\']/../../a');
        // find and edit [page][10][value] in tree
        $I->waitForText('[value] = Hello Acceptance Test!');
        $I->click('//span[@class="list-tree-label"]/a[text()=\'10\']/../../../ul//span[@class="list-tree-label"]/a[text()=\'value\']');
        $I->waitForText('page.10.value =');
        $I->fillField('//input[@name="value"]', 'HELLO WORLD!');
        $I->click('//input[@name="updateValue"]');
        $I->wait(2);
        $I->waitForText('Line added to current TypoScript record');
        $I->see('page.10.value = HELLO WORLD!');
        $I->see('[value] = HELLO WORLD!');
    }

    public function checkClosestTemplateButton(ApplicationTester $I): void
    {
        $I->wantTo('click on the button to go to the closest page with a TypoScript record');
        $I->switchToMainFrame();
        // Open the pagetree
        $I->clickWithLeftButton('(//*[contains(concat(" ", normalize-space(@class), " "), " node-toggle ")])[4]');
        $I->clickWithLeftButton('//*[text()=\'menu_sitemap_pages\']');
        $I->switchToContentFrame();
        $I->selectOption('.t3-js-jumpMenuBox', 'Constant Editor');
        $I->waitForText('No TypoScript record');
        $I->see('There is no TypoScript record on this page!');
        $I->see('You need to create a TypoScript record in order to edit your configuration.');
        $I->seeLink('Click here to go.');
        $I->clickWithLeftButton('//a[text()[normalize-space(.) = "Click here to go."]]');

        $I->wantTo('see that the page has a TypoScript record');
        $I->selectOption('.t3-js-jumpMenuBox', 'Override TypoScript');
        $I->waitForElement('.table-striped');
        $I->see('Title');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole TypoScript record');
        $I->click('Edit the whole TypoScript record');
    }

    public function createExtensionTemplate(ApplicationTester $I): void
    {
        $I->wantTo('see the button to create an additional TypoScript record');
        $I->switchToMainFrame();
        //Open the pagetree
        $I->clickWithLeftButton('(//*[contains(concat(" ", normalize-space(@class), " "), " node-toggle ")])[4]');
        $I->clickWithLeftButton('//*[text()=\'menu_sitemap_pages\']');
        $I->switchToContentFrame();
        $I->selectOption('.t3-js-jumpMenuBox', 'Constant Editor');
        $I->waitForText('No TypoScript record');
        $I->see('There is no TypoScript record on this page!');
        $I->see('You need to create a TypoScript record in order to edit your configuration.');
        $I->clickWithLeftButton('//input[@name=\'createExtension\']');
        $I->wantTo('see that the page has a TypoScript record');
        $I->selectOption('.t3-js-jumpMenuBox', 'Override TypoScript');
        $I->waitForElement('.table-striped');
        $I->see('Title');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole TypoScript record');
        $I->click('Edit the whole TypoScript record');
    }

    /**
     * @depends addANewSiteTemplate
     */
    public function searchInTypoScriptObjectBrowser(ApplicationTester $I): void
    {
        $I->wantTo('Open the TypoScript Object Browser and search a keyword.');
        $I->switchToMainFrame();
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->selectOption('.t3-js-jumpMenuBox', 'TypoScript Tree');
        $I->waitForText('Tree for TypoScript record');
        $I->amGoingTo('type "styles" into the search field and submit.');
        $I->fillField('#searchValue', 'styles');
        $I->click('button[type="submit"]');
        $I->waitForText('Setup Tree');
        $I->seeInSource('<strong class="text-danger">styles</strong>');
    }
}
