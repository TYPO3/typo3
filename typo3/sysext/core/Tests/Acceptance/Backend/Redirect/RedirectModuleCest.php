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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Redirect;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Tests concerning Redirects Module
 */
class RedirectModuleCest
{

    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->click('Redirects');
        $I->switchToContentFrame();
        $I->canSee('Redirect Management', 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function createNewRecordIfNoneExist(BackendTester $I)
    {
        $I->amGoingTo('create a new redirects record while none are in the system, yet');
        $I->canSee('No redirects found!');
        $I->click('Create new redirect');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Create new Redirect on root level');

        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_redirect]") and contains(@data-formengine-input-name, "[source_path]")]', '/my-path/');
        $I->fillField('//input[contains(@data-formengine-input-name, "data[sys_redirect]") and contains(@data-formengine-input-name, "[target]")]', 1);

        $saveButtonLink = '//*/button[@name="_savedok"][1]';
        $I->waitForElement($saveButtonLink, 30);
        $I->click($saveButtonLink);
        $I->waitForElementNotVisible('#t3js-ui-block');

        $I->click('div.module-docheader .btn.t3js-editform-close');

        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Redirect Management', 'h1');
        $I->canSeeNumberOfElements('table.table-striped > tbody > tr', 1);
    }

    /**
     * @param BackendTester $I
     */
    public function canEditRecordFromListView(BackendTester $I)
    {
        $sourceHost = $I->grabTextFrom('table.table-striped > tbody > tr > td:nth-child(1)');
        $sourcePath = $I->grabTextFrom('table.table-striped > tbody > tr > td:nth-child(2) > a');

        $I->amGoingTo('test edit on source path');
        $I->click('table.table-striped > tbody > tr > td:nth-child(2) > a');
        $this->openAndCloseTheEditForm($I, $sourceHost . ', ' . $sourcePath);

        $I->amGoingTo('test edit on edit button');
        $I->click('table.table-striped > tbody > tr > td:nth-child(6) > div > a:nth-child(1)');
        $this->openAndCloseTheEditForm($I, $sourceHost . ', ' . $sourcePath);
    }

    /**
     * @param BackendTester $I
     * @param string $name
     */
    private function openAndCloseTheEditForm(BackendTester $I, string $name): void
    {
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Edit Redirect "' . $name . '" on root level');

        $I->click('div.module-docheader .btn.t3js-editform-close');
        $I->waitForElementVisible('table.table-striped');
        $I->canSee('Redirect Management', 'h1');
    }
}
