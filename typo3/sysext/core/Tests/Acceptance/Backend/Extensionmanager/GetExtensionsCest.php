<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Extensionmanager;

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

use Facebook\WebDriver\WebDriverKeys;
use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;

/**
 * Tests for the "Get Extensions view" of the extension manager
 */
class GetExtensionsCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();

        $I->click('Extensions', '#menu');
        $I->switchToIFrame('list_frame');
        $I->waitForElementVisible('#typo3-extension-list');

        $I->selectOption('[name="ExtensionManagerModuleMenu"]', 'Get Extensions');
        $I->waitForElementVisible('#terTable_wrapper');

        // We expect exact two extensions created from the Fixtures
        $I->canSeeNumberOfElements('#terTable tbody tr', 2);
    }

    /**
     * @param Admin $I
     */
    public function checkRetrievedExtensionsFromTerAreDisplayed(Admin $I)
    {
        $I->see('superext');
        $I->see('neededext');
    }

    /**
     * @param Admin $I
     */
    public function checkPageBrowserDisplaysTwoRecords(Admin $I)
    {
        $I->canSeeElement('.pagination-wrap');
        $I->canSee('Records 1 - 2');
    }

    /**
     * @param Admin $I
     */
    public function checkSearchFilterListFindsExtensionKey(Admin $I)
    {
        $I->fillField('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', 'superext');
        $I->click('Go');
        $I->waitForElementVisible('#terSearchTable');
        $I->canSeeNumberOfElements('#terSearchTable tbody tr', 1);
        $I->canSee('Super Extension');

        $I->amGoingTo('search extension neededext and submit with enter');

        $I->fillField('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', 'neededext');
        $I->pressKey('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', WebDriverKeys::ENTER);
        $I->waitForElementVisible('#terSearchTable');
        $I->canSeeNumberOfElements('#terSearchTable tbody tr', 1);
        $I->canSee('Needed Extension');
    }

    /**
     * @param Admin $I
     */
    public function checkSearchFilterListFindsPartOfExtensionKey(Admin $I)
    {
        $I->fillField('input[name="tx_extensionmanager_tools_extensionmanagerextensionmanager[search]"]', 'ext');
        $I->click('Go');
        $I->waitForElementVisible('#terSearchTable');
        $I->canSeeNumberOfElements('#terSearchTable tbody tr', 2);
        $I->canSee('Super Extension');
        $I->canSee('Needed Extension');
    }
}
