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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\FileList;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\FileTree;

/**
 * Cases concerning sys_file_metadata records
 */
class FileClipboardCest extends AbstractFileCest
{
    protected string $copyModeCopy = '#clipboard-copymode-copy';
    protected string $copyModeMove = '#clipboard-copymode-move';

    /**
     * @param ApplicationTester $I
     */
    public function _before(ApplicationTester $I, FileTree $tree): void
    {
        parent::_before($I, $tree);
        $I->click('#checkClipBoard');
        $I->waitForElement($this->copyModeMove);
        $I->waitForElementVisible($this->copyModeMove);
        $I->waitForElementVisible($this->copyModeCopy);
    }

    /**
     * @param ApplicationTester $I
     */
    public function _after(ApplicationTester $I): void
    {
        $I->click('#checkClipBoard');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeSwitchModes(ApplicationTester $I): void
    {
        $I->seeCheckboxIsChecked($this->copyModeMove);
        $I->dontSeeCheckboxIsChecked($this->copyModeCopy);
        $I->click('//*/label[@for="clipboard-copymode-copy"]');
        $I->waitForElementVisible($this->copyModeMove);
        $I->dontSeeCheckboxIsChecked($this->copyModeMove);
        $I->waitForElementVisible($this->copyModeCopy);
        $I->seeCheckboxIsChecked($this->copyModeCopy);
        $I->click('//*/label[@for="clipboard-copymode-move"]');
        $I->waitForElementVisible($this->copyModeMove);
        $I->seeCheckboxIsChecked($this->copyModeMove);
        $I->waitForElementVisible($this->copyModeCopy);
        $I->dontSeeCheckboxIsChecked($this->copyModeCopy);
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeAddRemoveSingleRecord(ApplicationTester $I): void
    {
        $I->seeCheckboxIsChecked($this->copyModeMove);
        $fileName = 'bus_lane.jpg';
        $I->switchToMainFrame();
        $I->click('//*[text()="styleguide"]');
        $I->click('.scaffold-content-navigation-switcher-close');
        $I->switchToContentFrame();
        $this->openActionDropdown($I, $fileName)->click();
        $I->click('Cut');
        $I->see($fileName, '.clipboard-panel a');
        $I->click('Remove item', '.clipboard-panel');
        $I->dontSee($fileName, '.clipboard-panel a');
    }

    /**
     * @param ApplicationTester $I
     */
    public function seeAddRemoveMultipleRecords(ApplicationTester $I): void
    {
        $expectedFiles = ['bus_lane.jpg', 'telephone_box.jpg', 'underground.jpg'];

        $I->switchToMainFrame();
        $I->click('//*[text()="styleguide"]');
        $I->click('.scaffold-content-navigation-switcher-close');
        $I->switchToContentFrame();

        $I->amGoingTo('add multiple elements to clipboard');
        $I->wait(1);
        $I->click('Clipboard #1 (multi-selection mode)');
        $I->click('.dropdown-toggle');
        $I->waitForElementClickable('button[data-multi-record-selection-check-action="check-all"]');
        $I->click('button[data-multi-record-selection-check-action="check-all"]');
        $I->waitForElementClickable('button[data-multi-record-selection-action="copyMarked"]');
        $I->click('button[data-multi-record-selection-action="copyMarked"]');

        foreach ($expectedFiles as $file) {
            $I->see($file, '.clipboard-panel');
        }

        $I->amGoingTo('remove all elements from clipboard');
        $I->click('Remove all', '.clipboard-panel');

        foreach ($expectedFiles as $file) {
            $I->dontSee($file, '.clipboard-panel');
        }
    }
}
