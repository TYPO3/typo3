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

    public function addTaskToEmptyGroup(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('//table//td[contains(., "' . $this->groupName . '")]/following-sibling::td/*//typo3-scheduler-new-task-wizard-button[contains(@subject, "New task")]');
        $modalDialog->canSeeDialog();
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler\"]').click()");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler_TYPO3_CMS_Scheduler_Task_RecyclerGarbageCollectionTask\"]').click()");
        $I->switchToContentFrame();
        // second item on first tab (see fieldset)
        $fieldset = 'div.typo3-TCEforms > div:nth-of-type(1) > div:nth-of-type(1) > div:nth-of-type(1) > fieldset:nth-of-type(2)';
        $formWizardsWrap = $fieldset . ' > div:nth-of-type(1) div.t3js-formengine-field-item > div.form-wizards-wrap';
        $select = $formWizardsWrap . ' > div:nth-of-type(1) > select';
        $I->seeOptionIsSelected($select, $this->groupName . ' [tx_scheduler_task_group_1]');
        $I->click('button[title="Save"]', '.module-docheader');
        // Show the "Edit record" screen (= it is saved)
        $I->waitForText('Edit Scheduler task');
        $I->click('a[title="Close"]', '.module-docheader');
        $I->seeElement('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]');
    }

    public function hideTaskGroup(ApplicationTester $I): void
    {
        $I->click('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]/*//button[contains(@title, "Disable")]');

        $I->amGoingTo('see group disabled');
        $I->seeElement('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]/*/*[contains(@class, "badge-secondary")]');

        $I->amGoingTo('see task disabled by group');
        $I->seeElement('//div[contains(@class, "panel-heading")][contains(., "' . $this->groupName . '")]/following::div//*[contains(@class, "badge-secondary")]');
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
