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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;

/**
 * Tests for the "Install list view" of the extension manager
 */
class InstalledExtensionsCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();

        // clear the localstorage to fix problems in phantomJs where the search
        // sometimes is preserved over multiple sessions
        $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webDriver) {
            $webDriver->executeScript('localStorage.clear();');
        });

        $I->click('Extensions', '#typo3-module-menu');
        $I->switchToIFrame('content');
        $I->waitForElementVisible('#typo3-extension-list');
    }

    /**
     * @param Admin $I
     */
    public function checkSearchFiltersList(Admin $I)
    {
        $I->canSeeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', [10, 100]);

        // Fill extension search field
        $I->fillField('Tx_Extensionmanager_extensionkey', 'cshmanual');

        // see 2 rows. 1 for the header and one for the result
        $I->canSeeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', 1);

        // Look for extension key
        $I->canSee('cshmanual', '#typo3-extension-list tbody tr[role="row"] td');

        // unset the filter
        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 1);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');

        $I->canSeeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', [10, 100]);
    }

    /**
     * @param Admin $I
     */
    public function checkIfUploadFormAppears(Admin $I)
    {
        $I->cantSeeElement('.module-body .uploadForm');
        $I->click('a[title="Upload Extension .t3x/.zip"]', '.module-docheader');
        $I->seeElement('.module-body .uploadForm');
    }

    /**
     * @param Admin $I
     * @return Admin
     */
    public function checkIfInstallingAnExtensionWithBackendModuleAddsTheModuleToTheModuleMenu(Admin $I)
    {
        $I->switchToIFrame();
        $I->canSeeElement('.typo3-module-menu-item');
        $I->cantSeeElement('#system_BeuserTxBeuser');

        $I->switchToIFrame('content');
        $I->fillField('Tx_Extensionmanager_extensionkey', 'beuser');
        $I->waitForElementVisible('//*[@id="typo3-extension-list"]/tbody/tr[@id="beuser"]');
        $I->click('a[data-original-title="Activate"]', '//*[@id="typo3-extension-list"]/tbody/tr[@id="beuser"]');

        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 1);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');

        $I->switchToIFrame();
        $I->canSeeElement('#system_BeuserTxBeuser');

        return $I;
    }

    /**
     * @depends checkIfInstallingAnExtensionWithBackendModuleAddsTheModuleToTheModuleMenu
     * @param Admin $I
     */
    public function checkIfUninstallingAnExtensionWithBackendModuleRemovesTheModuleFromTheModuleMenu(Admin $I)
    {
        $I->switchToIFrame();
        $I->canSeeElement('#system_BeuserTxBeuser');

        $I->switchToIFrame('content');
        $I->fillField('Tx_Extensionmanager_extensionkey', 'beuser');
        $I->waitForElementVisible('//*[@id="typo3-extension-list"]/tbody/tr[@id="beuser"]');
        $I->click('a[data-original-title="Deactivate"]', '//*[@id="typo3-extension-list"]/tbody/tr[@id="beuser"]');

        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 1);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');

        $I->switchToIFrame();
        $I->cantSeeElement('#system_BeuserTxBeuser');
    }
}
