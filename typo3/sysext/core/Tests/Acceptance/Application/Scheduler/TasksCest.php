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
 * Scheduler task tests
 */
final class TasksCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->scrollTo('[data-modulemenu-identifier="scheduler"]');
        $I->see('Scheduler', '[data-modulemenu-identifier="scheduler"]');
        $I->click('[data-modulemenu-identifier="scheduler"]');
        $I->switchToContentFrame();
    }

    public function createASchedulerTask(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->see('No tasks defined yet');

        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();

        // Click on a specific task (Caching framework garbage collection)
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"reports\"]').click()");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"reports_TYPO3_CMS_Reports_Task_SystemStatusUpdateTask\"]').click()");
        $I->switchToContentFrame();

        $I->waitForElement('#task_SystemStatusUpdateNotificationEmail');
        $I->fillField('#task_SystemStatusUpdateNotificationEmail', 'test@local.typo3.org');
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);

        $I->switchToContentFrame();
    }

    public function canRunTask(ApplicationTester $I): void
    {
        // run the task
        $I->click('button[name="action[execute]"]');
        $I->waitForText('Task "System Status Update [reports]" with uid');
        $I->seeElement('[data-module-name="scheduler_manage"] tr[data-task-disabled="true"]');
        $I->see('disabled');
    }

    public function canEditTask(ApplicationTester $I): void
    {
        $I->click('//a[contains(@title, "Edit")]');
        $I->waitForText('Edit Scheduler task "System Status Update [reports]" on root level');
        $I->seeInField('#task_SystemStatusUpdateNotificationEmail', 'test@local.typo3.org');
        $I->fillField('#task_SystemStatusUpdateNotificationEmail', 'foo@local.typo3.org');
        $I->click('button[title="Save"]', '.module-docheader');
        $I->waitForText('Edit Scheduler task');
        $I->click('a[title="Close"]', '.module-docheader');
    }

    public function canEnableAndDisableTask(ApplicationTester $I): void
    {
        $I->click('//button[contains(@title, "Enable")]', '#tx_scheduler_form_0');
        $I->dontSeeElement('[data-module-name="scheduler_manage"] tr[data-task-disabled="true"]');
        $I->dontSee('disabled');
        // Give tooltips some time to fully init
        $I->wait(1);
        $I->moveMouseOver('//button[contains(@title, "Disable")]');
        $I->wait(1);
        $I->click('//button[contains(@title, "Disable")]');
        $I->waitForElementVisible('[data-module-name="scheduler_manage"]');
        $I->seeElement('[data-module-name="scheduler_manage"] tr[data-task-disabled="true"]');
        $I->see('disabled');
    }

    public function canDeleteTask(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->seeElement('//button[contains(@title, "Delete")]');
        $I->click('//button[contains(@title, "Delete")]');

        // Cancel the delete dialog
        // don't use $modalDialog->clickButtonInDialog due to too low timeout
        $modalDialog->canSeeDialog();
        $I->click('Cancel', ModalDialog::$openedModalButtonContainerSelector);
        $I->waitForElementNotVisible(ModalDialog::$openedModalSelector, 30);

        $I->switchToContentFrame();
        // Still see and can click the Delete button as the deletion has been canceled
        $I->click('//button[contains(@title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');
        $I->switchToContentFrame();
        $I->see('The task was successfully deleted.');
        $I->see('No tasks defined yet');
    }

    public function canSwitchToSetupCheck(ApplicationTester $I): void
    {
        $I->selectOption('select[name=moduleMenu]', 'Scheduler setup check');
        $I->waitForElementVisible('[data-module-name="scheduler_setupcheck"]');
        $I->see('Scheduler setup check');
        $I->see('This screen checks if the requisites for running the Scheduler as a cron job are fulfilled');
    }
}
