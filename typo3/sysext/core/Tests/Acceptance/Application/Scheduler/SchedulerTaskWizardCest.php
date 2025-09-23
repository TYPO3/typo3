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
 * Scheduler task wizard tests
 */
final class SchedulerTaskWizardCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->scrollTo('[data-modulemenu-identifier="scheduler"]');
        $I->see('Scheduler', '[data-modulemenu-identifier="scheduler"]');
        $I->click('[data-modulemenu-identifier="scheduler"]');
        $I->switchToContentFrame();
    }

    public function canOpenTaskWizardModal(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->see('No tasks found');
        $I->see('There are currently no configured tasks found. You can create a new one.');

        // Click the "New task" button which should open the wizard
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();
        $I->see('New task', '.modal-title');

        // Should contain the wizard web component
        $I->seeElement('typo3-backend-new-record-wizard');
    }

    public function wizardShowsTaskCategories(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();

        $I->see('Scheduler');

        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler\"]').click()");

        $I->see('Caching framework garbage collection');
        $I->see('File Abstraction Layer');
        $I->see('Table garbage collection');
    }

    public function selectingTaskFromWizardOpensFormWithCorrectTaskType(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();

        // Click on a specific task (Caching framework garbage collection)
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler\"]').click()");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler_TYPO3_CMS_Scheduler_Task_RecyclerGarbageCollectionTask\"]').click()");
        $I->switchToContentFrame();

        // Should be in FormEngine form for creating new task
        $I->seeInCurrentUrl('record/edit');
        $I->see('Create new Scheduler task on root level');

        // Should see task-specific field
        $I->see('Number of days until removing files');

        // Save the task
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);

        $I->switchToContentFrame();

        // Should return to scheduler list with new task
        $I->see('Fileadmin garbage collection');

        // Delete Task again
        $I->click('//button[contains(@title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');
    }

    public function wizardSearchFunctionality(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();

        // Use search functionality
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('input[type=\"search\"]').value = 'cache'");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('input[type=\"search\"]').dispatchEvent(new Event('input', { bubbles: true }))");

        // Should filter results to show only cache-related tasks
        $I->see('Caching framework garbage collection');
        $I->dontSee('Optimize MySQL database tables');

        // Clear search
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('input[type=\"search\"]').value = ''");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('input[type=\"search\"]').dispatchEvent(new Event('input', { bubbles: true }))");

        // Should show all tasks again
        $I->see('Caching framework garbage collection');
        $I->see('Optimize MySQL database tables');
    }

    public function wizardHandlesNoResults(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();

        // Search for something that doesn't exist
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('input[type=\"search\"]').value = 'nonexistentask'");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('input[type=\"search\"]').dispatchEvent(new Event('input', { bubbles: true }))");

        // Should show no results message
        $I->see('Unfortunately no scheduler task matches your query, please try a different one.');
    }

    public function createMultipleTasksFromWizard(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        // Create first task
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler\"]').click()");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler_TYPO3_CMS_Scheduler_Task_RecyclerGarbageCollectionTask\"]').click()");
        $I->switchToContentFrame();
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);
        $I->switchToContentFrame();

        // Should see first task in list
        $I->see('Fileadmin garbage collection');

        // Create second task
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler\"]').click()");
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"scheduler_TYPO3_CMS_Scheduler_Task_FileStorageIndexingTask\"]').click()");
        $I->switchToContentFrame();
        $I->click('.module-docheader a[title="Close"]');
        $I->switchToWindow('typo3-backend');
        $I->wait(1);
        $I->waitForText('Save and close');
        $I->click('Save and close');
        $I->wait(1);
        $I->switchToContentFrame();

        // Should see both tasks in list
        $I->see('Fileadmin garbage collection');
        $I->see('File Abstraction Layer: Update storage index');

        // Delete Tasks again
        $I->click('//button[contains(@title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');
        $I->wait(1);
        $I->switchToContentFrame();
        $I->dontSee('Fileadmin garbage collection');
        $I->see('File Abstraction Layer: Update storage index');
        $I->click('//button[contains(@title, "Delete")]');
        $modalDialog->clickButtonInDialog('OK');
        $I->wait(1);
        $I->switchToContentFrame();

        // Check recently used
        $I->click('//typo3-scheduler-new-task-wizard-button', '.module-docheader');
        $modalDialog->canSeeDialog();
        $I->executeJS("document.querySelector('" . ModalDialog::$openedModalSelector . " typo3-backend-new-record-wizard').shadowRoot.querySelector('[data-identifier=\"recently-used\"]').click()");
        $I->see('Fileadmin garbage collection');
        $I->see('File Abstraction Layer: Update storage index');
    }
}
