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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Extensionmanager;

use Codeception\Attribute\Env;
use Codeception\Exception\MalformedLocatorException;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests for the "Install list view" of the extension manager
 */
final class InstalledExtensionsCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');

        $I->click('Extensions', '#modulemenu');

        $I->switchToMainFrame();
        try {
            $needsStepUp = count($I->grabMultiple('.modal-sudo-mode-verification')) > 0;
        } catch (MalformedLocatorException) {
            $needsStepUp = false;
        }
        if ($needsStepUp) {
            $I->see('Verify with user password');
            $I->fillField('//input[@name="password"]', 'password');
            $I->click('//button[@name="verify"]');
        }
        $I->switchToContentFrame();

        $I->waitForElementVisible('#typo3-extension-list');
    }

    public function checkSearchFiltersList(ApplicationTester $I): void
    {
        $I->seeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', [10, 100]);

        // Fill extension search field
        $I->fillField('Tx_Extensionmanager_extensionkey', 'backend');
        $I->waitForElementNotVisible('tr#core');

        // see 2 rows. 1 for the header and one for the result
        $I->seeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', 3);

        // Look for extension key
        $I->see('backend', '#typo3-extension-list tbody tr[role="row"] td');

        // unset the filter
        $I->waitForElementVisible('#Tx_Extensionmanager_extensionkey ~button.close', 10);
        $I->click('#Tx_Extensionmanager_extensionkey ~button.close');
        $I->wait(1);
        $I->seeNumberOfElements('#typo3-extension-list tbody tr[role="row"]', [10, 100]);
    }

    #[Env('classic')]
    public function checkIfUploadFormAppears(ApplicationTester $I): void
    {
        $I->cantSeeElement('.module-body .extension-upload-form');
        $I->click('a[title="Upload Extension"]', '.module-docheader');
        $I->seeElement('.module-body .extension-upload-form');
    }

    #[Env('classic')]
    public function checkUninstallingAndInstallingAnExtension(ApplicationTester $I): void
    {
        $I->wantTo('Check if uninstalling and installing an extension with backend module removes and adds the module from the module menu.');
        $I->amGoingTo('uninstall extension belog');
        $I->switchToMainFrame();
        $I->seeElement('[data-modulemenu-identifier="system_BelogLog"]');

        $I->switchToContentFrame();
        $I->waitForElementVisible('//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');
        $I->click('button[title="Deactivate"]', '//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');

        $I->switchToMainFrame();
        $I->waitForElementNotVisible('[data-modulemenu-identifier="system_BelogLog"]');
        $I->cantSeeElement('[data-modulemenu-identifier="system_BelogLog"]');

        $I->amGoingTo('install extension belog');
        $I->switchToContentFrame();
        $I->waitForElementVisible('//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');
        $I->click('button[title="Activate"]', '//*[@id="typo3-extension-list"]/tbody/tr[@id="belog"]');

        $I->switchToMainFrame();
        $I->waitForElementVisible('[data-modulemenu-identifier="system_BelogLog"]');
        $I->seeElement('[data-modulemenu-identifier="system_BelogLog"]');
    }
}
