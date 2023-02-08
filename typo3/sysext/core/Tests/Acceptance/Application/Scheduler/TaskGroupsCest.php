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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Scheduler;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Scheduler task group tests
 */
final class TaskGroupsCest
{
    protected string $groupName = 'My task group';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->scrollTo('[data-modulemenu-identifier="scheduler"]');
        $I->see('Scheduler', '[data-modulemenu-identifier="scheduler"]');
        $I->click('[data-modulemenu-identifier="scheduler"]');
        $I->switchToContentFrame();
    }

    public function createASchedulerGroup(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('.t3js-create-group', '.module-docheader');
        $modalDialog->canSeeDialog();
        $I->fillField('input[name="action[createGroup]"]', $this->groupName);
        $modalDialog->clickButtonInDialog('Create group');
        $I->switchToContentFrame();
        $I->seeElement('//table//td[contains(., "' . $this->groupName . '")]');
    }

    public function addTaskToEmptyGroup(ApplicationTester $I): void
    {
        $I->click('//table//td[contains(., "' . $this->groupName . '")]/following-sibling::td/*//a[contains(@title, "New task")]');
        $I->seeOptionIsSelected('#task_group', $this->groupName);
        $I->fillField('#task_frequency', '0 */2 * * *');
        $I->click('button[title="Save"]', '.module-docheader');
        $I->waitForText('The task was added successfully.');
        $I->click('a[title="Close"]', '.module-docheader');
        $I->seeElement('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]');
    }

    public function hideTaskGroup(ApplicationTester $I): void
    {
        $I->click('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]/*//button[contains(@title, "Disable")]');

        $I->amGoingTo('see group disabled');
        $I->seeElement('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]/*[contains(@class, "badge-info")]');

        $I->amGoingTo('see task disabled by group');
        $I->seeElement('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]/following::div//*[contains(@class, "badge-info")]');
    }

    public function removeGroup(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->amGoingTo('remove tasks from group');
        $I->click('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]//parent::div//table//button[contains(@title, "Delete")]');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('OK');

        $I->amGoingTo('remove the empty group');
        $I->switchToContentFrame();
        $I->seeElement('//table//td[contains(., "' . $this->groupName . '")]');
        $I->click('//table//td[contains(., "' . $this->groupName . '")]/following-sibling::td/*//button[contains(@title, "Delete")]');
        $modalDialog->canSeeDialog();
        $modalDialog->clickButtonInDialog('OK');
        $I->dontSeeElement('//table//td[contains(., "' . $this->groupName . '")]');
    }
}
