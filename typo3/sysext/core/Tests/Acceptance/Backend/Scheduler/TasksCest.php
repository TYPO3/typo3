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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

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

        $I->see('Scheduler', '#system_txschedulerM1');
        $I->click('Scheduler', '#system_txschedulerM1');

        // switch to content iframe
        $I->switchToIFrame('content');
    }

    /**
     * @param Admin $I
     * @return Admin
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

        return $I;
    }

    /**
     * @depends createASchedulerTask
     * @param Admin $I
     * @return Admin
     */
    public function canRunTask(Admin $I)
    {
        // run the task
        $I->click('//a[contains(@title, "Run task")]');
        $I->waitForText('Executed: System Status Update');

        return $I;
    }

    /**
     * @depends createASchedulerTask
     * @param Admin $I
     * @param ModalDialog $modalDialog
     */
    public function canDeleteTask(Admin $I, ModalDialog $modalDialog)
    {
        $I->wantTo('See a delete button for a task');
        $I->seeElement('//a[contains(@title, "Delete")]');

        $I->click('//a[contains(@title, "Delete")]');
        $I->wantTo('Cancel the delete dialog');
        $modalDialog->clickButtonInDialog('Cancel');
        $I->switchToIFrame('content');

        $I->wantTo('Still see and can click the Delete button as the deletion has been canceled');
        $I->click('//a[contains(@title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');

        $I->switchToIFrame('content');
        $I->see('The task was successfully deleted.');
        $I->see('No tasks defined yet');
    }
}
