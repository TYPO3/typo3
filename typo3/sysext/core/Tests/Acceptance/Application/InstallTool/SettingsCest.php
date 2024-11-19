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

use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

final class SettingsCest extends AbstractCest
{
    private static string $alertContainerSelector = '#alert-container';

    public function _before(ApplicationTester $I): void
    {
        parent::_before($I);
        $this->logIntoInstallTool($I);
        $I->click('Settings');
        $I->see('Settings', 'h1');
    }

    public function seeExtensionConfiguration(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $logoAltText = 'TYPO3 logo alt text';
        $inputAltText = '#em-backend-loginLogoAlt';
        $button = 'Configure extensions';
        $modalSave = 'Save "backend" configuration';
        $panel = 'backend';

        // Open modal, change alt text and save
        $I->click($button);
        $modalDialog->canSeeDialog();
        $I->amGoingTo('open the backend panel');
        $I->click($panel, '.panel-heading');
        $I->waitForElement($inputAltText);
        $previousLogoAltText = $I->grabValueFrom($inputAltText);
        $I->amGoingTo('fill in an alt text for the logo');
        $I->fillField($inputAltText, $logoAltText);
        $I->click($modalSave, ModalDialog::$openedModalSelector);
        $this->closeModalAndHideFlashMessage($I);

        // Open modal, reset alt text and save
        $I->amGoingTo('see saved alt text and reset the alt text for the logo');
        $I->click($button);
        $modalDialog->canSeeDialog();
        $I->click($panel, '.panel-heading');
        $I->waitForElement($inputAltText);
        $value = $I->grabValueFrom($inputAltText);
        $I->assertEquals($logoAltText, $value);
        $I->fillField($inputAltText, $previousLogoAltText);
        $I->click($modalSave, ModalDialog::$openedModalSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    public function seeChangeInstallToolPassword(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $expectedInitialPasswordValue = '';

        $I->click('Change Install Tool Password');
        $modalDialog->canSeeDialog();

        $I->amGoingTo('check if password fields are initially empty');
        $passwordValue = $I->grabValueFrom('#t3-install-tool-password');
        $I->assertEquals($expectedInitialPasswordValue, $passwordValue);
        $passwordRepeatValue = $I->grabValueFrom('#t3-install-tool-password-repeat');
        $I->assertEquals($expectedInitialPasswordValue, $passwordRepeatValue);

        $I->amGoingTo('change the install tool password');
        $I->fillField('#t3-install-tool-password', self::INSTALL_TOOL_PASSWORD);
        $I->fillField('#t3-install-tool-password-repeat', self::INSTALL_TOOL_PASSWORD);

        $I->click('Set new password', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Install tool password changed', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    public function seeManageSystemMaintainers(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $button = 'Manage System Maintainers';
        $modalSave = 'Save system maintainer list';

        $I->amGoingTo('add a system maintainer to the list');
        $I->click($button);
        $modalDialog->canSeeDialog();
        $I->click('.chosen-search-input', ModalDialog::$openedModalSelector);

        // Select first user in list - "admin"
        $I->amGoingTo('select first user in list');
        $I->click('.active-result[data-option-array-index="0"]', '.chosen-results');
        $I->click($modalSave, ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Updated system maintainers', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);

        $I->amGoingTo('remove the maintainer from the list');
        $I->click($button);
        $modalDialog->canSeeDialog();
        // Wait for current list of maintainers to appear
        $I->waitForElementVisible('.search-choice-close', 5);
        $I->click('.search-choice-close', ModalDialog::$openedModalSelector);
        $I->waitForElementNotVisible('.search-choice', 5);
        $I->click($modalSave, ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Cleared system maintainer list', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    public function seeConfigurationPresets(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $button = 'Choose Preset';
        $modalButton = 'Activate preset';
        $expectedFlashMessageText = 'Configuration written';

        $I->click($button);
        $modalDialog->canSeeDialog();

        $I->click('Cache settings', '.panel-heading');
        $I->waitForElement('#t3-install-tool-configuration-cache-file');

        $I->amGoingTo('change cache configuration and save configuration');
        $I->click('#t3-install-tool-configuration-cache-file');
        $I->click($modalButton, ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText($expectedFlashMessageText, 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);

        $I->click($button);
        $modalDialog->canSeeDialog();

        // Check if value was saved as expected
        $fileCacheValue = $I->grabValueFrom('input[type="radio"][name="install[values][Cache][enable]"]:checked');
        $I->assertEquals('File', $fileCacheValue);

        // Reset cache to custom configuration
        $I->click('Cache settings', '.panel-heading');
        $I->waitForElement('#t3-install-tool-configuration-cache-database');
        $I->amGoingTo('change and save the cache configuration');
        $I->click('#t3-install-tool-configuration-cache-custom');
        $I->click($modalButton, ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText($expectedFlashMessageText, 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    public function seeFeatureToggles(ApplicationTester $I, ModalDialog $modalDialog, Scenario $scenario): void
    {
        $button = 'Configure Features';
        $modalButton = 'Save';
        $featureToggle = '#t3-install-tool-features-redirects.hitCount';

        // Switch hit count feature toggle
        $I->click($button);
        $modalDialog->canSeeDialog();
        $I->amGoingTo('change hit count feature toggle and save it');
        $I->click($featureToggle);
        $I->click($modalButton, ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText('Features updated', 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);

        // Switch back hit count feature toggle
        $I->click($button);
        $modalDialog->canSeeDialog();
        if (str_contains($scenario->current('env'), 'classic')) {
            // ['features']['redirects.hitCount'] is enabled by default in classic mode (set by TF BackendEnvironment setup)
            $I->cantSeeCheckboxIsChecked($featureToggle);
        } else {
            $I->canSeeCheckboxIsChecked($featureToggle);
        }
        $I->amGoingTo('reset hit count feature toggle and save it');
        $I->click($featureToggle);
        $I->click($modalButton, ModalDialog::$openedModalButtonContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    public function seeConfigureInstallationWideOptions(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $button = 'Configure options';
        $panel = 'Backend';
        $checkbox = '#BE_languageDebug';
        $modalButton = 'Write configuration';
        $expectedFlashMessageText = 'BE/languageDebug';

        // Activate [BE][languageDebug]
        $I->click($button);
        $modalDialog->canSeeDialog();
        $I->amGoingTo('open the backend panel');
        $I->click($panel, '.panel-heading');
        $I->waitForElement($checkbox);
        $I->amGoingTo('tick the checkbox [BE][languageDebug] option');
        $I->click($checkbox);
        $I->click($modalButton, ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForText($expectedFlashMessageText, 5, self::$alertContainerSelector);
        $this->closeModalAndHideFlashMessage($I);

        // Reset [BE][languageDebug]
        $I->click($button);
        $modalDialog->canSeeDialog();
        $I->click($panel, '.panel-heading');
        $I->waitForElement($checkbox);
        $I->seeCheckboxIsChecked($checkbox);
        $I->amGoingTo('reset [BE][languageDebug] checkbox');
        $I->click($checkbox);
        $I->click($modalButton, ModalDialog::$openedModalButtonContainerSelector);
        $this->closeModalAndHideFlashMessage($I);
    }

    private function closeModalAndHideFlashMessage(ApplicationTester $I): void
    {
        // We need to close the flash message here to be able to close the modal
        $I->click('.close', self::$alertContainerSelector);
        $I->click('.t3js-modal-close');
    }
}
