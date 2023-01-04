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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\RecordList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Cases concerning the "new record" module
 */
final class NewRecordTypeCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function specificRecordTypeCanBeCreated(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->amGoingTo('create a record');
        $I->click('List');
        $I->waitForElementNotVisible('#nprogress');
        $pageTree->openPath(['styleguide TCA demo']);
        $I->wait(0.2);
        $I->switchToContentFrame();

        $I->click('.module-docheader .btn[title="Create new record"]');
        $I->wait(0.2);
        $I->canSee('New record');

        $I->waitForElementVisible('button[data-bs-target="#inside-types"]');
        $I->click('button[data-bs-target="#inside-types"]');
        $I->waitForElementVisible('#inside-types');
        $I->click('//a[text()[normalize-space(.) = "Standard"]]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->waitForText('Create new Page', 3, 'h1');
        $I->see('Standard [1]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[pages]") and contains(@data-formengine-input-name, "[title]")]', 'new standard page');
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);

        $pageTree->openPath(['styleguide TCA demo', 'new standard page']);
        $I->wait(0.2);
        $I->switchToContentFrame();
        $I->canSeeElement('typo3-backend-editable-page-title[pagetitle="new standard page"]');
    }
}
