<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Scheduler;

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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use TYPO3\TestingFramework\Core\Acceptance\Support\Helper\ModalDialog;

/**
 * Scheduler task tests
 */
class TasksCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('list_frame');
        $I->waitForText('Web Content Management System');
        $I->switchToIFrame();
        $I->see('Scheduler', '#system_txschedulerM1');
        $I->click('Scheduler', '#system_txschedulerM1');
        // switch to content iframe
        $I->switchToIFrame('list_frame');
    }

    /**
     * @param Admin $I
     */
    public function createASchedulerTask(Admin $I)
    {
        $I->see('No tasks defined yet');
        $I->click('//a[contains(@title, "Add task")]', '.module-docheader');
        $I->cantSeeElement('#task_SystemStatusUpdateNotificationEmail');
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
     * @param Admin $I
     */
    public function canRunTask(Admin $I)
    {
        // run the task
        $I->click('a[data-original-title="Run task"]');
        $I->waitForText('Executed: System Status Update');
        $I->seeElement('.tx_scheduler_mod1 .disabled');
        $I->see('disabled');
    }

    /**
     * @depends createASchedulerTask
     * @param Admin $I
     */
    public function canEditTask(Admin $I)
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
     * @param Admin $I
     */
    public function canEnableAndDisableTask(Admin $I)
    {
        $I->wantTo('See a enable button for a task');
        $I->click('//a[contains(@data-original-title, "Enable")]', '#tx_scheduler_form');
        $I->dontSeeElement('.tx_scheduler_mod1 .disabled');
        $I->dontSee('disabled');
        $I->wantTo('See a disable button for a task');
        $I->click('//a[contains(@data-original-title, "Disable")]');
        $I->seeElement('.tx_scheduler_mod1 .disabled');
        $I->see('disabled');
    }

    /**
     * @depends createASchedulerTask
     * @param Admin $I
     * @param ModalDialog $modalDialog
     */
    public function canDeleteTask(Admin $I, ModalDialog $modalDialog)
    {
        $I->wantTo('See a delete button for a task');
        $I->seeElement('//a[contains(@data-original-title, "Delete")]');
        $I->click('//a[contains(@data-original-title, "Delete")]');
        $I->wantTo('Cancel the delete dialog');
        $modalDialog->clickButtonInDialog('Cancel');
        $I->switchToIFrame('list_frame');
        $I->wantTo('Still see and can click the Delete button as the deletion has been canceled');
        $I->click('//a[contains(@data-original-title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');
        $I->switchToIFrame('list_frame');
        $I->see('The task was successfully deleted.');
        $I->see('No tasks defined yet');
    }

    /**
     * @param Admin $I
     */
    public function canSwitchToSetupCheck(Admin $I)
    {
        $I->selectOption('select[name=SchedulerJumpMenu]', 'Setup check');
        $I->see('Setup check');
        $I->see('This screen checks if the requisites for running the Scheduler as a cron job are fulfilled');
    }

    /**
     * @param Admin $I
     */
    public function canSwitchToInformation(Admin $I)
    {
        $I->selectOption('select[name=SchedulerJumpMenu]', 'Information');
        $I->see('Information');
        $I->canSeeNumberOfElements('.tx_scheduler_mod1 table tbody tr', [1, 10000]);
    }
}
