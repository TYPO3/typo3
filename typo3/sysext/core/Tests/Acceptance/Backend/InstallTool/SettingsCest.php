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

class SettingsCest extends AbstractCest
{
    public static string $alertContainerSelector = '#alert-container';

    public function _before(BackendTester $I)
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Settings');
        $I->see('Settings', 'h1');
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeExtensionConfiguration(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Configure extensions');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the backend panel');
        $I->click('backend', '.panel-heading');
        $I->waitForElement('#em-backend-loginLogoAlt');

        $I->amGoingTo('fill in an alt text for the logo');
        $I->fillField('#em-backend-loginLogoAlt', 'TYPO3 logo alt text');
        $I->click('Save "backend" configuration', ModalDialog::$openedModalSelector);
        $I->waitForText('Configuration saved', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeChangeInstallToolPassword(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Change Install Tool Password');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('change the install tool password');
        $I->fillField('#t3-install-tool-password', 'password');
        $I->fillField('#t3-install-tool-password-repeat', 'password');

        $I->click('Set new password', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Install tool password changed', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeManageSystemMaintainers(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Manage System Maintainers');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('add a system maintainer to the list');
        $I->click('.chosen-search-input', ModalDialog::$openedModalSelector);
        $I->click('.active-result[data-option-array-index="0"]', '.chosen-results');

        $I->click('Save system maintainer list', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Updated system maintainers', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeConfigurationPresets(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Choose Preset');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the cache settings panel');
        $I->click('Cache settings', '.panel-heading');
        $I->waitForElement('#t3-install-tool-configuration-cache-file');

        $I->amGoingTo('change cache configuration and save configuration');
        $I->click('#t3-install-tool-configuration-cache-file');
        $I->click('Activate preset', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Configuration written', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    public function seeFeatureToggles(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Configure Features');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('change a feature toggle and save it');
        $I->click('#t3-install-tool-features-redirects.hitCount');
        $I->click('Save', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Features updated', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    /**
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     * @throws \Exception
     */
    public function seeConfigureInstallationWideOptions(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->click('Configure options');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('open the backend panel');
        $I->click('Backend', '.panel-heading');
        $I->waitForElement('#BE_languageDebug');

        $I->amGoingTo('tick of checkbox the [BE][languageDebug] option');
        $I->click('#BE_languageDebug');
        $I->click('Write configuration', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('BE/languageDebug', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    /**
     * @param BackendTester $I
     */
    private function closeModalAndHideFlashMessage(BackendTester $I)
    {
        // We need to close the flash message here to be able to close the modal
        $I->click('.close', self::$alertContainerSelector);
        $I->click('.t3js-modal-close');
    }
}
