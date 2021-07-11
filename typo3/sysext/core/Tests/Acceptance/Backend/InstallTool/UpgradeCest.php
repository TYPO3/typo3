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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\InstallTool;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

class UpgradeCest extends AbstractCest
{
    public static string $alertContainerSelector = '#alert-container';

    public function _before(BackendTester $I)
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Upgrade');
        $I->see('Upgrade', 'h1');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeUpgradeCore(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Update Core');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the core updater');
        $I->see('TYPO3 CMS core to its latest minor release');
        $I->click('.t3js-modal-close');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeUpgradeWizard(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Run Upgrade Wizard');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the upgrade wizard and set charset to utf8');
        $I->see('Upgrade Wizard', ModalDialog::$openedModalSelector);
        $I->click('Set default charset to utf8', ModalDialog::$openedModalSelector);
        $I->waitForText('Default connection database has been set to utf8', 5, ModalDialog::$openedModalSelector);
        $I->see('No wizards are marked as done', ModalDialog::$openedModalSelector);

        $I->click('.t3js-modal-close');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeViewUpgradeDocumentation(BackendTester $I, ModalDialog $modalDialog)
    {
        $versionPanel = '#version-1 .t3js-changelog-list > div:first-child';

        $I->click('View Upgrade Documentation');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the view upgrade documentation');
        $I->see('View Upgrade Documentation', ModalDialog::$openedModalSelector);

        $I->amGoingTo('mark an item as read');
        // pick first named version, master might be empty
        $I->click('#heading-1 > h2:nth-child(1) > a:nth-child(1) > strong:nth-child(2)');
        $I->waitForElement('#version-1', 5, ModalDialog::$openedModalSelector);

        $textCurrentFirstPanelHeading = $I->grabTextFrom($versionPanel . ' .panel-heading');

        $I->click($versionPanel . ' a[data-bs-toggle="collapse"]');
        $I->click($versionPanel . ' .t3js-upgradeDocs-markRead');

        $textNewFirstPanelHeading = $I->grabTextFrom($versionPanel . ' .panel-heading');
        $I->assertNotEquals($textCurrentFirstPanelHeading, $textNewFirstPanelHeading);

        $I->amGoingTo('mark an item as unread');
        $I->click('#heading-read');
        $I->waitForElement('#collapseRead', 5, ModalDialog::$openedModalSelector);
        $I->executeJS('document.querySelector("#collapseRead").scrollIntoView();');
        $I->see($textCurrentFirstPanelHeading, '#collapseRead');
        $I->click('#collapseRead .t3js-changelog-list > div:first-child .t3js-upgradeDocs-unmarkRead');
        $I->see($textCurrentFirstPanelHeading, '#version-1');

        $I->click('.t3js-modal-close');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     */
    public function seeCheckTca(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Check TCA');
        $modalDialog->canSeeDialog();
        $I->see('No TCA changes in ext_tables.php files.', ModalDialog::$openedModalSelector);

        $I->click('.t3js-modal-close');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     */
    public function seeCheckForBrokenExtensions(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Check Extension Compatibility');
        $modalDialog->canSeeDialog();
        $I->see('ext_localconf.php of all loaded extensions successfully loaded', ModalDialog::$openedModalSelector);
        $I->see('ext_tables.php of all loaded extensions successfully loaded', ModalDialog::$openedModalSelector);

        $I->amGoingTo('trigger "check extensions"');
        $I->click('Check extensions', ModalDialog::$openedModalButtonContainerSelector);
        $I->see('ext_localconf.php of all loaded extensions successfully loaded', ModalDialog::$openedModalSelector);
        $I->see('ext_tables.php of all loaded extensions successfully loaded', ModalDialog::$openedModalSelector);

        $I->click('.t3js-modal-close');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     */
    public function seeCheckTcaMigrations(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Check TCA Migrations');
        $modalDialog->canSeeDialog();
        $I->see('Checks whether the current TCA needs migrations and displays the new migration paths which need to be adjusted manually', ModalDialog::$openedModalSelector);

        $I->click('.t3js-modal-close');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     */
    public function seeScanExtensionFiles(BackendTester $I, ModalDialog $modalDialog)
    {
        $buttonText = 'Rescan';

        $I->click('Scan Extension Files');
        $modalDialog->canSeeDialog();
        $I->click('Extension: styleguide', ModalDialog::$openedModalSelector);
        $I->waitForText($buttonText, 30, ModalDialog::$openedModalSelector);

        // Trigger scan for single extension
        $I->click($buttonText);
        $I->waitForText($buttonText, 30, ModalDialog::$openedModalSelector);

        // Scan all available extensions
        $I->click('Scan all');
        $I->waitForElement('.panel-success', 20, ModalDialog::$openedModalSelector);

        // Wait for all flash messages to disappear
        $I->waitForText('Marked not affected files', 10, self::$alertContainerSelector);
        $I->wait(5);

        $I->click('.t3js-modal-close');
    }
}
