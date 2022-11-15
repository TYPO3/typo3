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

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

class InlineContentElementLocalizeSynchronizeCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('Page');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'staticdata']);
        $I->switchToContentFrame();
        $I->waitForText('staticdata', 20);
    }

    public function addingResourceToDefaultLangPageAddResourceToLocalizedPage(ApplicationTester $I): void
    {
        // Add a content element type images and localize it
        $I->click('.module-body td[data-language-uid="0"] span[data-identifier="actions-add"]');
        $I->switchToWindow('typo3-backend');
        $I->waitForText('Images Only');
        $I->click('Images Only');
        $I->switchToContentFrame();
        $I->waitForText('Create new Page Content on page "staticdata"', 3, 'h1');
        $I->click('Images');
        // Inline add record in Resources tab
        $I->click('Images');
        $I->click('span[data-identifier="actions-insert-record"]', 'div.active');
        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');
        // Find page 'styleguide' in page tree of modal and click it
        $context = $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            $context = $webdriver->findElement(WebDriverBy::cssSelector('div.element-browser-main-sidebar'));
            return $context->findElement(WebDriverBy::xpath('//*[text()=\'styleguide\']/..'));
        });
        // Add an image, closes modal again
        $context->findElement(WebDriverBy::cssSelector('text.node-name'))->click();
        $I->waitForElementVisible('#typo3-filelist a[data-file-name="telephone_box.jpg"]');
        $I->click('#typo3-filelist a[data-file-name="telephone_box.jpg"]');
        // Save, go back to page
        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->waitForText('Save and close');
        $I->click('Save and close');
        // Switch to "All languages" view and localize content element
        $I->switchToContentFrame();
        $I->waitForElementVisible('select[name=actionMenu]');
        $I->selectOption('select[name=actionMenu]', 'Languages');
        $I->waitForElementVisible('select[name=languageMenu]');
        $I->selectOption('select[name=languageMenu]', 'All languages');
        $I->waitForText('Translate');
        $I->click('Translate');
        $I->switchToWindow('typo3-backend');
        $I->waitForText('Localize page "staticdata - language 1" into styleguide demo language danish');
        $I->click('span[data-identifier="actions-localize"]');
        $I->click('Next');
        $I->waitForText('Normal');
        $I->click('Next');
        $I->switchToContentFrame();
        $I->waitForText('(copy 1)');
        // Edit default language content element again and add another image
        $I->click('.module-body td[data-language-uid="0"] span[data-identifier="actions-open"]');
        $I->waitForText('Edit Page Content on page "staticdata"', 3, 'h1');
        $I->click('Images');
        $I->click('span[data-identifier="actions-insert-record"]', 'div.active');
        $I->switchToWindow('typo3-backend');
        $I->switchToIFrame('modal_frame');
        // Find page 'styleguide' in page tree of modal and click it
        $context = $I->executeInSelenium(function (RemoteWebDriver $webdriver) {
            $context = $webdriver->findElement(WebDriverBy::cssSelector('div.element-browser-main-sidebar'));
            return $context->findElement(WebDriverBy::xpath('//*[text()=\'styleguide\']/..'));
        });
        // Add an image, closes modal again
        $context->findElement(WebDriverBy::cssSelector('text.node-name'))->click();
        $I->waitForElementVisible('#typo3-filelist a[data-file-name="underground.jpg"]');
        $I->click('#typo3-filelist a[data-file-name="underground.jpg"]');
        // Save, go back to page
        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->waitForText('Save and close');
        $I->click('Save and close');
        // Open the localized element and see that the second image can be synchronized
        $I->switchToContentFrame();
        $I->waitForText('(copy 1)');
        $I->click('.module-body td[data-language-uid="1"] span[data-identifier="actions-open"]');
        $I->waitForText('Edit Page Content " (copy 1)" on page "staticdata"', 3, 'h1');
        $I->click('Images');
        $I->waitForText('underground.jpg');
        $I->click('span[data-identifier="actions-document-localize"]');
        // Synchronized image has been opened
        $I->waitForText('Image Metadata');
    }
}
