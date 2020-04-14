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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Template;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Template tests
 */
class TemplateCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->see('Template');
        $I->click('Template');

        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->see('Template tools');
    }

    /**
     * @param BackendTester $I
     */
    public function pagesWithNoTemplateShouldShowButtonsToCreateTemplates(BackendTester $I)
    {
        $I->wantTo('show templates overview on root page (uid = 0)');
        $I->switchToMainFrame();
        // click on root page
        $I->click('.node.identifier-0_0');
        $I->switchToContentFrame();
        $I->waitForElementVisible('#ts-overview');
        $I->see('This is an overview of the pages in the database containing one or more template records. Click a page title to go to the page.');

        $I->wantTo('show templates overview on website root page (uid = 1 and pid = 0)');
        $I->switchToMainFrame();
        // click on website root page
        $I->click('.node.identifier-0_1');
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

    /**
     * @param BackendTester $I
     */
    public function addANewSiteTemplate(BackendTester $I)
    {
        $I->wantTo('create a new site template');
        $I->switchToMainFrame();
        $I->click('.node.identifier-0_1');
        $I->switchToContentFrame();
        $I->waitForText('Create new website');
        $I->click("//input[@name='newWebsite']");
        $I->waitForText('Edit constants for template');

        $I->wantTo('change to Info/Modify and see the template overview table');
        $I->selectOption('.t3-js-jumpMenuBox', 'Info/Modify');
        $I->waitForElement('.table-fit');
        $I->see('Title');
        $I->see('Sitetitle');
        $I->see('Description');
        $I->see('Constants');
        $I->see('Setup');
        $I->see('Edit the whole template record');
        $I->click('Edit the whole template record');

        $I->wantTo('change the title and save the template');
        $I->waitForElement('#EditDocumentController');
        // fill title input field
        $I->fillField('//input[@data-formengine-input-name="data[sys_template][1][title]"]', 'Acceptance Test Site');
        $I->click("//button[@name='_savedok']");
        $I->waitForElementNotVisible('#t3js-ui-block', 30);
        $I->waitForElement('#EditDocumentController');
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->wantTo('change the setup, save the template and close the form');
        // grab and fill setup textarea
        $config = $I->grabTextFrom('//textarea[@data-formengine-input-name="data[sys_template][1][config]"]');
        $config = str_replace('HELLO WORLD!', 'Hello Acceptance Test!', $config);
        $I->fillField('//textarea[@data-formengine-input-name="data[sys_template][1][config]"]', $config);

        $I->click('//*/button[@name="_savedok"][1]');
        $I->waitForElement('a.t3js-editform-close');
        $I->click('a.t3js-editform-close');

        $I->wantTo('see the changed title');
        $I->waitForElement('.table-fit');
        $I->see('Acceptance Test Site');

        $I->wantTo('change the template within the TypoScript Object Browser');
        $I->selectOption('.t3-js-jumpMenuBox', 'TypoScript Object Browser');
        $I->waitForText('Current template');
        $I->see('CONSTANTS ROOT');
        $I->selectOption('//select[@name="SET[ts_browser_type]"]', 'Setup');
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
}
