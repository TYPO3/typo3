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

final class InlinePagesLocalizeResourceCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');

        $I->click('List');
        $I->waitForElement('svg .nodes .node');
        $pageTree->openPath(['styleguide TCA demo', 'staticdata']);
        $I->switchToContentFrame();
        $I->waitForText('staticdata', 20);
    }

    public function addingResourceToDefaultLangPageAddResourceToLocalizedPage(ApplicationTester $I): void
    {
        // Add an image to media field of default lang page
        $I->click('.module-docheader a[title="Edit page properties"]');
        $I->waitForText('Edit Page "staticdata"', 3, 'h1');
        // Inline add record in Resources tab
        $I->click('Resources');
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
        // Save, go back to list
        $I->switchToWindow('typo3-backend');
        $I->switchToContentFrame();
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->waitForText('Save and close');
        $I->click('Save and close');
        // Edit the page translation and see if that resource has been added.
        $I->switchToContentFrame();
        $I->waitForText('staticdata - language 1');
        $I->click('staticdata - language 1');
        $I->waitForText('Edit Page "staticdata - language 1"', 3, 'h1');
        $I->click('Resources');
        $I->see('telephone_box.jpg');
    }
}
