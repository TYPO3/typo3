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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Extensionmanager;

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Tests for the "Get Extensions view" of the extension manager
 */
class GetExtensionsCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->click('Extensions', '#modulemenu');
        $I->switchToContentFrame();
        $I->waitForElementVisible('#typo3-extension-list');

        $I->selectOption('[name="ExtensionManagerModuleMenu"]', 'Get Extensions');
        $I->waitForElementVisible('#terTable');

        // We expect exact two extensions created from the Fixtures
        $I->seeNumberOfElements('#terTable tbody tr', 2);
    }

    /**
     * @param BackendTester $I
     */
    public function checkRetrievedExtensionsFromTerAreDisplayed(BackendTester $I)
    {
        $I->see('superext');
        $I->see('neededext');
    }

    /**
     * @param BackendTester $I
     */
    public function checkPageBrowserDisplaysTwoRecords(BackendTester $I)
    {
        $I->seeElement('.pagination-wrap');
        $I->see('Records 1 - 2');
    }

    /**
     * @param BackendTester $I
     */
    public function checkSearchFilterListFindsExtensionKey(BackendTester $I)
    {
        $I->fillField('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', 'superext');
        $I->click('Go');
        // @todo do something about the double loading of the table, it is rendered twice (not double, but once, then retrieve extension list loader, then second time)
        $I->waitForElementVisible('#terSearchTable');
        $I->wait(3);
        $I->waitForElementNotVisible('#nprogess');
        $I->seeNumberOfElements('#terSearchTable tbody tr', 1);
        $I->see('Super Extension');

        $I->amGoingTo('search extension neededext and submit with enter');

        $I->fillField('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', 'neededext');
        $I->pressKey('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', WebDriverKeys::ENTER);
        $I->waitForElementVisible('#terSearchTable');
        $I->wait(3);
        $I->waitForElementNotVisible('div#nprogess');
        $I->seeNumberOfElements('#terSearchTable tbody tr', 1);
        $I->see('Needed Extension');
    }

    /**
     * @param BackendTester $I
     */
    public function checkSearchFilterListFindsPartOfExtensionKey(BackendTester $I)
    {
        $I->fillField('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', 'ext');
        $I->click('Go');
        $I->waitForElementVisible('#terSearchTable');
        $I->seeNumberOfElements('#terSearchTable tbody tr', 2);
        $I->wait(3);
        $I->waitForElementNotVisible('div#nprogess');
        $I->see('Super Extension');
        $I->see('Needed Extension');
    }
}
