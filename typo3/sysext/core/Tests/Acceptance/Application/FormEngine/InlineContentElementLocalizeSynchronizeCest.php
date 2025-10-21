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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FormEngine;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class InlineContentElementLocalizeSynchronizeCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('Page');
        $pageTree->openPath(['styleguide TCA demo', 'staticdata']);
        $I->switchToContentFrame();
        $I->waitForText('staticdata', 20);
    }

    public function addingResourceToDefaultLangPageAddResourceToLocalizedPage(ApplicationTester $I): void
    {
        // Add a content element type images and localize it
        $I->click('.module-body td[data-language-uid="0"] span[data-identifier="actions-plus"]');
        $I->switchToMainFrame();
        $I->waitForElement('.t3js-modal[open]');
        $I->wait(3);
        $I->waitForElementNotVisible('div#nprogess');
        $I->executeJS("document.querySelector('typo3-backend-new-record-wizard').shadowRoot.querySelector('button[data-identifier=\"default\"]').click()");
        $I->executeJS("document.querySelector('typo3-backend-new-record-wizard').shadowRoot.querySelector('button[data-identifier=\"default_image\"]').click()");
        $I->switchToContentFrame();
        $I->waitForText('Create new Page Content on page "staticdata"', 3, 'h1');
        $I->click('Images');
        // Inline add record in Resources tab
        $I->click('Images');
        $I->click('span[data-identifier="actions-insert-record"]', 'div.active');
        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');
        // Find page 'styleguide' in page tree of modal and click it
        $I->click('//div[contains(@class, "element-browser-main-sidebar")]//*[text()="styleguide"]/..');
        $I->waitForElementVisible('[data-filelist-name="telephone_box.jpg"] [data-filelist-action="primary"]');
        $I->click('[data-filelist-name="telephone_box.jpg"] [data-filelist-action="primary"]');
        // Save, go back to page
        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->waitForElementVisible('.module-docheader a[title="Close"]');
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);
        // Switch to "All languages" view and localize content element
        $I->switchToContentFrame();
        $I->waitForElementVisible('select[name=actionMenu]');
        $I->selectOption('select[name=actionMenu]', 'Language Comparison');
        $I->waitForElementVisible('button[title="Language"]');
        $I->click('button[title="Language"]');
        $I->click('a[title="All languages"]');
        $I->waitForText('Translate');
        $I->click('Translate');
        $I->switchToWindow('typo3-backend');
        $I->waitForText('Localize page "staticdata - language 1" into styleguide demo language danish');
        $I->click('label[data-action="localize"]');
        $I->click('Next');
        $I->waitForText('Normal');
        $I->click('Next');
        $I->switchToContentFrame();
        $I->waitForText('(copy 1)');
        // Edit default language content element again and add another image
        $I->click('.module-body div.t3-page-ce[data-language-uid="0"] span[data-identifier="actions-open"]');
        $I->waitForText('Edit Page Content on page "staticdata"', 3, 'h1');
        $I->click('Images');
        $I->click('span[data-identifier="actions-insert-record"]', 'div.active');
        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');
        // Find page 'styleguide' in page tree of modal and click it
        $I->click('//div[contains(@class, "element-browser-main-sidebar")]//*[text()="styleguide"]/..');
        $I->waitForElementVisible('[data-filelist-name="underground.jpg"] [data-filelist-action="primary"]');
        $I->click('[data-filelist-name="underground.jpg"] [data-filelist-action="primary"]');
        // Save, go back to page
        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);
        $I->waitForElementNotVisible('.t3js-modal');
        // Open the localized element and see that the second image can be synchronized
        $I->switchToContentFrame();
        $I->waitForText('(copy 1)');
        $I->click('.module-body div.t3-page-ce[data-language-uid="1"] span[data-identifier="actions-open"]');
        $I->waitForText('Edit Page Content " (copy 1)" on page "staticdata"', 3, 'h1');
        $I->click('Images');
        $I->waitForText('underground.jpg');
        $I->click('span[data-identifier="actions-document-localize"]');
        // Synchronized image has been opened
        $I->waitForText('Image Metadata');
    }
}
