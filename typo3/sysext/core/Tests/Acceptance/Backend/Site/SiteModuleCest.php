<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Redirect;

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
 * Tests concerning Sites Module
 */
class SiteModuleCest
{

    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->click('Sites');
        $I->switchToContentFrame();
        $I->canSee('Site Configuration', 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function createNewRecordIfNoneExist(BackendTester $I)
    {
        $I->amGoingTo('create a new site configuration when none are in the system, yet');
        $I->click('Add new site configuration for this site');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Create new Site configuration');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site]") and contains(@data-formengine-input-name, "[identifier]")]', 'testIdentifier');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site]") and contains(@data-formengine-input-name, "[base]")]', '/');
        $I->click('Languages');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[title]")]', 'English');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[base]")]', 'en/');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[site_language]") and contains(@data-formengine-input-name, "[locale]")]', 'en_US.UTF-8');

        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->click('div.module-docheader .btn.t3js-editform-close');

        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Site Configuration', 'h1');
        $I->canSee('testIdentifier');
    }
}
