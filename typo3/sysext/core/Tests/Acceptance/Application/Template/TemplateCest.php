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
        $I->see('Template');
        $I->click('Template');
        $I->waitForElement('svg .nodes .node');
        $I->switchToContentFrame();
        $I->waitForText('Template tools');
    }

    public function pagesWithNoTemplateShouldShowButtonsToCreateTemplates(ApplicationTester $I): void
    {
        $I->wantTo('show templates overview on root page (uid = 0)');
        // Select the root page
        $I->switchToMainFrame();
        // click on PID=0
        $I->clickWithLeftButton('#identifier-0_0 text.node-name');

        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->see('This is an overview of the pages in the database containing one or more template records. Click a page title to go to the page.');

        $I->wantTo('show templates overview on website root page (uid = 1 and pid = 0)');
        $I->switchToMainFrame();
        // click on website root page
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->waitForText('No template');
        $I->see('There was no template on this page!');
        $I->see('You need to create a template record below in order to edit your configuration.');

        // @todo These input fields should be changed to buttons. Should be changed to proper HTML.
        $I->seeInFormFields(
            '#TypoScriptTemplateModuleController',
            [
                'newWebsite' => 'Create template for a new site',
                'createExtension' => 'Click here to create an extension template.',
            ]
        );
    }

    public function addANewSiteTemplate(ApplicationTester $I): void
    {
        $I->wantTo('create a new site template');
        $I->switchToMainFrame();
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();
        $I->waitForText('Create new website');
        $I->click("//input[@name='newWebsite']");
        $I->waitForText('Edit constants for template');

        $I->wantTo('change to Info/Modify and see the template overview table');
        $I->selectOption('.t3-js-jumpMenuBox', 'Info/Modify');
        $I->waitForElement('.table-striped');
        $I->see('Title');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole template record');
        $I->click('Edit the whole template record');

        $I->wantTo('change the title and save the template');
        $I->waitForElement('#EditDocumentController');
        // fill title input field
        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_template]") and contains(@data-formengine-input-name, "[title]")]', 'Acceptance Test Site');
        $I->click("//button[@name='_savedok']");
        $I->waitForElementNotVisible('#t3js-ui-block', 30);
        $I->waitForElement('#EditDocumentController');
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->wantTo('change the setup, save the template and close the form');
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

        $I->wantTo('change the template within the TypoScript Object Browser');
        $I->selectOption('.t3-js-jumpMenuBox', 'TypoScript Object Browser');
        $I->waitForText('CONSTANTS ROOT');
        $I->selectOption('//select[@name="ts_browser_type"]', 'Setup');
        $I->waitForText('SETUP ROOT');
        // find and open [page] in tree
        $I->see('[page] = PAGE');
        $I->click('//span[@class="list-tree-label"]/a[text()=\'page\']/../../a');
        // find and open [page][10] in tree
        $I->waitForText('[10] = TEXT');
        $I->click('//span[@class="list-tree-label"]/a[text()=\'page\']/../../../ul//span[@class="list-tree-label"]/a[text()=\'10\']/../../a');
        // find and edit [page][10][value] in tree
        $I->waitForText('[value] = Hello Acceptance Test!');
        $I->click('//span[@class="list-tree-label"]/a[text()=\'10\']/../../../ul//span[@class="list-tree-label"]/a[text()=\'value\']');
        $I->waitForText('page.10.value =');
        $I->fillField('//input[@name="data[page.10.value][value]"]', 'HELLO WORLD!');
        $I->click('//input[@name="update_value"]');
        $I->wait(2);
        $I->waitForText('Value updated');
        $I->see('page.10.value = HELLO WORLD!');
        $I->see('[value] = HELLO WORLD!');
    }

    public function checkClosestTemplateButton(ApplicationTester $I): void
    {
        $I->wantTo('click on the button to go to the closest page with a template');
        $I->switchToMainFrame();
        // Open the pagetree
        $I->clickWithLeftButton('(//*[contains(concat(" ", normalize-space(@class), " "), " node-toggle ")])[4]');
        $I->clickWithLeftButton('//*[text()=\'menu_sitemap_pages\']');
        $I->switchToContentFrame();
        $I->waitForText('No template');
        $I->see('There was no template on this page!');
        $I->see('You need to create a template record below in order to edit your configuration.');
        $I->seeLink('Click here to go.');
        $I->clickWithLeftButton('//a[text()[normalize-space(.) = "Click here to go."]]');

        $I->wantTo('see that the page has a template');
        $I->selectOption('.t3-js-jumpMenuBox', 'Info/Modify');
        $I->waitForElement('.table-striped');
        $I->see('Title');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole template record');
        $I->click('Edit the whole template record');
    }

    public function createExtensionTemplate(ApplicationTester $I): void
    {
        $I->wantTo('see the button to create a extension template');
        $I->switchToMainFrame();
        //Open the pagetree
        $I->clickWithLeftButton('(//*[contains(concat(" ", normalize-space(@class), " "), " node-toggle ")])[4]');
        $I->clickWithLeftButton('//*[text()=\'menu_sitemap_pages\']');
        $I->switchToContentFrame();
        $I->waitForText('No template');
        $I->see('There was no template on this page!');
        $I->see('You need to create a template record below in order to edit your configuration.');

        $I->seeInFormFields(
            '#TypoScriptTemplateModuleController',
            [
                'createExtension' => 'Click here to create an extension template.',
            ]
        );

        $I->clickWithLeftButton('//input[@name=\'createExtension\']');

        $I->wantTo('see that the page has a template');
        $I->selectOption('.t3-js-jumpMenuBox', 'Info/Modify');
        $I->waitForElement('.table-striped');
        $I->see('Title');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole template record');
        $I->click('Edit the whole template record');
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
        $I->selectOption('.t3-js-jumpMenuBox', 'TypoScript Object Browser');
        $I->waitForText('CONSTANTS ROOT');

        $I->amGoingTo('type "styles" into the search field and submit.');
        $I->fillField('#search_field', 'styles');
        $I->click("//input[@name='search']");
        $I->waitForText('CONSTANTS ROOT');
        $I->seeInSource('<strong class="text-danger">styles</strong>');
    }

    /**
     * @depends addANewSiteTemplate
     */
    public function useObjectListInObjectBrowser(ApplicationTester $I): void
    {
        $I->wantTo('Open the TypoScript Object Browser and use the object list.');
        $I->switchToMainFrame();
        $I->clickWithLeftButton('//*[text()=\'styleguide TCA demo\']');
        $I->switchToContentFrame();

        $I->amGoingTo('Switch to object browser.');
        $I->selectOption('.t3-js-jumpMenuBox', 'TypoScript Object Browser');
        $I->waitForText('CONSTANTS ROOT');
        $I->selectOption('select[name="ts_browser_type"]', 'Setup');
        $I->waitForText('SETUP ROOT');
        $I->click('tt_content');
        $I->waitForText('Edit object/property value');

        $I->amGoingTo('add tt_content to object list');
        $I->click('Add key "tt_content" to Object List');
        $I->see('Remove key from OL');
        $I->see('key');

        $I->amGoingTo('verify "all" can still be selected and shows full setup.');
        $I->selectOption('select[name="ts_browser_toplevel_setup"]', 'all');
        $I->seeInSource('<i class="text-muted"># Content element rendering</i>');
    }
}
