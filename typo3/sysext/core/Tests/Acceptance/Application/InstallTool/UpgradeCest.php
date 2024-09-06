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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\InstallTool;

use Codeception\Attribute\Env;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

final class UpgradeCest extends AbstractCest
{
    public function _before(ApplicationTester $I): void
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Upgrade');
        $I->see('Upgrade', 'h1');
    }

    #[Env('classic')]
    public function seeUpgradeCore(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('Update Core…');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the core updater');
        $I->see('TYPO3 CMS core to its latest minor release');
        $I->click('.t3js-modal-close');
    }

    public function seeViewUpgradeDocumentation(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $versionPanel = '#version-2 .t3js-changelog-list > div:first-child';

        $I->click('View Upgrade Documentation…');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the view upgrade documentation');
        $I->see('View Upgrade Documentation', ModalDialog::$openedModalSelector);

        $I->amGoingTo('mark an item as read');
        // pick 2nd named version (e.g. `12.4`), current dev versions might be empty (e.g. `13.0` and `12.4.x`)
        $I->click('#heading-2');
        $I->waitForElement('#version-2', 5, ModalDialog::$openedModalSelector);

        $textCurrentFirstPanelHeading = $I->grabTextFrom($versionPanel . ' .panel-heading');

        $I->click($versionPanel . ' button[data-bs-toggle="collapse"]');
        $I->click($versionPanel . ' .t3js-upgradeDocs-markRead');

        $I->dontSee($textCurrentFirstPanelHeading, '#version-2');

        $I->amGoingTo('mark an item as unread');
        $I->executeJS('document.querySelector(".t3js-modal-body").scrollTop = 100000;');
        $I->click('#heading-read');
        $I->waitForElement('#collapseRead', 5, ModalDialog::$openedModalSelector);
        $I->see($textCurrentFirstPanelHeading, '#collapseRead');
        $I->click('#collapseRead .t3js-changelog-list > div:first-child .t3js-upgradeDocs-unmarkRead');
        $I->see($textCurrentFirstPanelHeading, '#version-2');

        $I->click('.t3js-modal-close');
    }

    public function seeCheckTca(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('Check TCA…');
        $modalDialog->canSeeDialog();
        $I->see('No TCA changes in ext_tables.php files.', ModalDialog::$openedModalSelector);

        $I->click('.t3js-modal-close');
    }

    public function seeCheckForBrokenExtensions(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->wait(1);
        $I->click('Check Extension Compatibility…');
        $modalDialog->canSeeDialog();
        $I->see('ext_localconf.php of all loaded extensions successfully loaded', ModalDialog::$openedModalSelector);
        $I->see('ext_tables.php of all loaded extensions successfully loaded', ModalDialog::$openedModalSelector);

        $I->amGoingTo('trigger "check extensions"');
        $I->click('Check extensions', ModalDialog::$openedModalButtonContainerSelector);
        $I->wait(1);
        $I->waitForText('ext_localconf.php of all loaded extensions successfully loaded');
        $I->see('ext_localconf.php of all loaded extensions successfully loaded');
        $I->waitForText('ext_tables.php of all loaded extensions successfully loaded');
        $I->see('ext_tables.php of all loaded extensions successfully loaded');

        $I->click('.t3js-modal-close');
    }

    public function seeCheckTcaMigrations(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('Check TCA Migrations…');
        $modalDialog->canSeeDialog();
        $I->see('Checks whether the current TCA needs migrations and displays the new migration paths which need to be adjusted manually', ModalDialog::$openedModalSelector);

        $I->click('.t3js-modal-close');
    }
}
