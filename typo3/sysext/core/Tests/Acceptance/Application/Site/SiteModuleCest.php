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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Site;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Tests concerning Sites Module
 */
final class SiteModuleCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function editExistingRecord(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->amGoingTo('Access the site module');
        $I->click('Sites');
        $I->switchToContentFrame();
        $I->see('Site Configuration', 'h1');

        $I->amGoingTo('edit an automatically created site configuration');
        $I->click('Edit site configuration');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->see('Edit Site Configuration', 'h1');

        $I->amGoingTo('Edit the default site language');
        $I->click('Languages');
        $I->see('English [0] (en_US.UTF-8)');
        $I->click('div[data-table-unique-original-value=site_language_0] > div:nth-child(1) > div:nth-child(1)');
        $I->waitForElementVisible('div[data-table-unique-original-value=site_language_0] > div.panel-collapse');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]', 'English Edit');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[base]")]', '/');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[locale]")]', 'en_US.UTF-8');

        $I->amGoingTo('Delete a site language');
        $I->see('styleguide demo language danish [1] (da_DK.UTF-8)');
        $I->click('div[data-table-unique-original-value=site_language_1] > div:nth-child(1) > div:nth-child(1) > div:nth-child(3) button');
        $modalDialog->canSeeDialog();
        $I->click('button[name="yes"]', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);
        $I->switchToContentFrame();
        // await 0.5s fade-out transition
        $I->wait(1);
        $I->dontSee('styleguide demo language danish [1] (da_DK.UTF-8)');
        $I->see('styleguide demo language danish [1]', 'option');

        $I->amGoingTo('Save the site configuration');
        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->amGoingTo('Verify default site language has changed and danish is deleted');
        $I->see('English Edit [0] (en_US.UTF-8)');
        $I->dontSee('styleguide demo language danish [1] (da_DK.UTF-8)');

        $I->amGoingTo('Create a completely new site language');
        $I->click('Create new language');
        $I->waitForElementVisible('div.inlineIsNewRecord');
        $I->scrollTo('div.inlineIsNewRecord');
        $I->see('[New language]');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]', 'New Language');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[base]")]', '/new-language/');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[locale]")]', 'hr_HR');

        $I->amGoingTo('Save the site configuration');
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->amGoingTo('Verify new site configuration has been added with the next available language ID)');
        $I->see('New Language [9] (hr_HR)');

        $I->amGoingTo('Close the site configuration form');
        $I->click('Close');
        $I->waitForElementVisible('table.table-striped');
        $I->see('Site Configuration', 'h1');
    }
}
