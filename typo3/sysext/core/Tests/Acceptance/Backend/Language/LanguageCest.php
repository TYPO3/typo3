<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Language;

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

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Language tests
 */
class LanguageCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->see('Languages');
        $I->click('Languages');

        // Increase duration for notification viewing, avoids vanish before the message is tested
        $I->executeJS('TYPO3.Notification.duration = 100;');

        // switch to content iframe
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function showsHeadingAndListsInstalledLanguages(BackendTester $I)
    {
        $I->see('Installed Languages');

        $I->wantTo('See the table of languages');
        $I->waitForElementVisible('#typo3-language-list');
    }

    /**
     * @param BackendTester $I
     */
    public function filterInstalledLanguages(BackendTester $I)
    {
        $I->wantTo('Filter the list of translations with a valid language');
        $I->fillField('#typo3-language-searchfield', 'Danish');
        $I->canSeeNumberOfElements('#typo3-language-list tbody tr', 1);
        $I->seeElement('#language-da');

        $I->fillField('#typo3-language-searchfield', '');

        $I->wantTo('Filter the list of translations with an valid locale');
        $I->fillField('#typo3-language-searchfield', 'pt_BR');
        $I->canSeeNumberOfElements('#typo3-language-list tbody tr', 1);
        $I->seeElement('#language-pt_BR');
        $I->see('Brazilian Portuguese');
    }

    /**
     * @param BackendTester $I
     */
    public function activateAndDeactivateALanguage(BackendTester $I)
    {
        $I->wantTo('Activate a language');
        $I->seeElement('#language-pt_BR.disabled');

        $I->click('#language-pt_BR td a.activateLanguageLink');
        $this->seeAlert($I, 'Success', 'Language was successfully activated.');

        $I->wantTo('Deactivate a language');
        $I->seeElement('#language-pt_BR.enabled');

        $I->click('#language-pt_BR td a.deactivateLanguageLink');
        $this->seeAlert($I, 'Success', 'Language was successfully deactivated.');
    }

    /**
     * @param BackendTester $I
     */
    public function downloadALanguage(BackendTester $I)
    {
        $I->wantTo('Download a language with no selection and see error message');
        $I->seeElement('#language-pt_BR.disabled');

        $I->click('a[data-action="updateActiveLanguages"]');
        $this->seeAlert($I, 'Error', 'No language activated. Please activate at least one language.');

        // Download only a single translation for a specific extension for performance reasons
        $I->wantTo('Download a single translation for a selected language');

        $I->click('#language-pt_BR td a.activateLanguageLink');

        $I->selectOption('.t3-js-jumpMenuBox', 'Translation Overview');
        $I->waitForElementVisible('#typo3-translation-list');
        $I->waitForElementVisible('#extension-beuser td a.updateTranslationLink');
        $I->click('#extension-beuser td a.updateTranslationLink');
        $I->waitForElement('#extension-beuser td:nth-child(3).complete');
        $this->seeAlert($I, 'Success', 'The translation update has been successfully completed.');
    }

    /**
     * @param BackendTester $I
     */
    public function showsHeadingAndListsTranslationOverview(BackendTester $I)
    {
        $I->wantToTest('Select Translation Overview');
        $I->selectOption('.t3-js-jumpMenuBox', 'Translation Overview');
        $I->waitForElementVisible('#typo3-translation-list');
        $I->see('Translation Overview');
    }

    /**
     * @param BackendTester $I
     */
    public function filterTranslationOverview(BackendTester $I)
    {
        $I->wantToTest('Select Translation Overview and Filter');
        $I->selectOption('.t3-js-jumpMenuBox', 'Translation Overview');
        $I->waitForElementVisible('#typo3-translation-list');

        $I->wantTo('Filter the list of translations with a valid Extension');
        $I->fillField('#typo3-language-searchfield', 'TYPO3 Core');
        $I->canSeeNumberOfElements('#typo3-translation-list tbody tr', 1);

        $I->wantTo('Filter the list of translations with an invalid Extension');
        $I->fillField('#typo3-language-searchfield', 'TYPO3 FooBar');
        $I->canSeeNumberOfElements('#typo3-translation-list tbody tr', 1);
    }

    /**
     * @param BackendTester $I
     * @param string $alertTitle
     * @param string $alertMessage
     */
    protected function seeAlert(BackendTester $I, $alertTitle, $alertMessage)
    {
        // switch back to body
        $I->switchToIFrame();

        $I->wait(1);
        $I->waitForElement('//div[contains(@role, "alert")]', 10);
        $I->see($alertTitle);
        $I->see($alertMessage);

        $I->switchToContentFrame();
    }
}
