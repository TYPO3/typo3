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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Scheduler;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Scheduler task tests
 */
class TasksCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
        $I->scrollTo('#system_txschedulerM1');
        $I->see('Scheduler', '#system_txschedulerM1');
        $I->click('#system_txschedulerM1');
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function createASchedulerTask(BackendTester $I)
    {
        $I->see('No tasks defined yet');
        $I->click('//a[contains(@title, "Add task")]', '.module-docheader');
        $I->waitForElementNotVisible('#task_SystemStatusUpdateNotificationEmail');
        $I->selectOption('#task_class', 'System Status Update');
        $I->seeElement('#task_SystemStatusUpdateNotificationEmail');
        $I->selectOption('#task_type', 'Single');
        $I->fillField('#task_SystemStatusUpdateNotificationEmail', 'test@local.typo3.org');
        $I->click('button.dropdown-toggle', '.module-docheader');
        $I->wantTo('Click "Save and close"');
        $I->click("//a[contains(@data-value,'saveclose')]");
        $I->waitForText('The task was added successfully.');
    }

    /**
     * @depends createASchedulerTask
     * @param BackendTester $I
     */
    public function canRunTask(BackendTester $I)
    {
        // run the task
        $I->click('a[data-original-title="Run task"]');
        $I->waitForText('Executed: System Status Update');
        $I->seeElement('.tx_scheduler_mod1 .disabled');
        $I->see('disabled');
    }

    /**
     * @depends createASchedulerTask
     * @param BackendTester $I
     */
    public function canEditTask(BackendTester $I)
    {
        $I->click('//a[contains(@data-original-title, "Edit")]');
        $I->waitForText('Edit task');
        $I->seeInField('#task_SystemStatusUpdateNotificationEmail', 'test@local.typo3.org');
        $I->fillField('#task_SystemStatusUpdateNotificationEmail', 'foo@local.typo3.org');
        $I->click('button.dropdown-toggle', '.module-docheader');
        $I->wantTo('Click "Save and close"');
        $I->click("//a[contains(@data-value,'saveclose')]");
        $I->waitForText('The task was updated successfully.');
    }

    /**
     * @depends canRunTask
     * @param BackendTester $I
     */
    public function canEnableAndDisableTask(BackendTester $I)
    {
        $I->wantTo('See a enable button for a task');
        $I->click('//a[contains(@data-original-title, "Enable")]', '#tx_scheduler_form');
        $I->dontSeeElement('.tx_scheduler_mod1 .disabled');
        $I->dontSee('disabled');
        $I->wantTo('See a disable button for a task');
        $I->click('//a[contains(@data-original-title, "Disable")]');
        $I->waitForElementVisible('div.tx_scheduler_mod1');
        $I->seeElement('.tx_scheduler_mod1 .disabled');
        $I->see('disabled');
    }

    /**
     * @depends createASchedulerTask
     * @param BackendTester $I
     * @param ModalDialog $modalDialog
     */
    public function canDeleteTask(BackendTester $I, ModalDialog $modalDialog)
    {
        $I->wantTo('See a delete button for a task');
        $I->seeElement('//a[contains(@data-original-title, "Delete")]');
        $I->click('//a[contains(@data-original-title, "Delete")]');
        $I->wantTo('Cancel the delete dialog');

        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('Cancel', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);

        $I->switchToContentFrame();
        $I->wantTo('Still see and can click the Delete button as the deletion has been canceled');
        $I->click('//a[contains(@data-original-title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');
        $I->switchToContentFrame();
        $I->see('The task was successfully deleted.');
        $I->see('No tasks defined yet');
    }

    /**
     * @param BackendTester $I
     */
    public function canSwitchToSetupCheck(BackendTester $I)
    {
        $I->selectOption('select[name=SchedulerJumpMenu]', 'Setup check');
        $I->waitForElementVisible('div.tx_scheduler_mod1');
        $I->see('Setup check');
        $I->see('This screen checks if the requisites for running the Scheduler as a cron job are fulfilled');
    }

    /**
     * @param BackendTester $I
     */
    public function canSwitchToInformation(BackendTester $I)
    {
        $I->selectOption('select[name=SchedulerJumpMenu]', 'Information');
        $I->waitForElementVisible('div.tx_scheduler_mod1');
        $I->see('Information');
        $I->canSeeNumberOfElements('.tx_scheduler_mod1 table tbody tr', [1, 10000]);
    }

    /**
     * @param BackendTester $I
     */
    public function canCreateNewTaskGroupFromEditForm(BackendTester $I)
    {
        $I->amGoingTo('create a task when none exists yet');
        $I->canSee('Scheduled tasks', 'h1');
        $this->createASchedulerTask($I);

        $I->amGoingTo('test the new task group button on task edit view');
        $I->click('.taskGroup-table > tbody > tr > td.nowrap > span > div:nth-child(1) > a:nth-child(1)');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Edit task', 'h2');
        $I->click('#task_group_row > div > div > div > div > a');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->canSee('Create new Scheduler task group on root level', 'h1');

        $I->fillField('//input[contains(@data-formengine-input-name, "data[tx_scheduler_task_group]") and contains(@data-formengine-input-name, "[groupName]")]', 'new task group');
        $I->click('button[name="_savedok"]');
        $I->wait(0.2);
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->click('a[title="Close"]');
        $I->waitForElementVisible('#tx_scheduler_form');

        $I->selectOption('select#task_class', 'new task group');
        $I->click('button[value="save"]');
        $I->waitForElementNotVisible('#t3js-ui-block');
        $I->click('a[title="Cancel"]');
        $I->waitForElementVisible('div.tx_scheduler_mod1');

        $I->canSee('new task group', '.panel-heading');
    }
}
