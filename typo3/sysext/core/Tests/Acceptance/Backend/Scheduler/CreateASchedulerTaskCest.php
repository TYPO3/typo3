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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Kasper;

/**
 * Acceptance test
 */
class CreateASchedulerTaskCest
{
    /**
     * @param Kasper $I
     */
    public function _before(Kasper $I)
    {
        $I->loginAsAdmin();
    }

    /**
     * @param Kasper $I
     */
    public function _after(Kasper $I)
    {
        $I->logout();
    }

    /**
     * @param Kasper $I
     */
    public function tryToTest(Kasper $I)
    {
        $I->wantTo("To create a scheduler task");
        $I->see('Scheduler', '#system_txschedulerM1');
        $I->click('Scheduler', '#system_txschedulerM1');

        // switch to content iframe
        $I->switchToIFrame('content');

        // create a new task
        $I->see('No tasks defined yet');
        $I->click("//a[contains(@title, 'Add task')]");
        $I->selectOption("form select[id=task_class]", 'System Status Update');
        $I->selectOption("form select[id=task_type]", 'Single');
        $I->fillField('#task_SystemStatusUpdateNotificationEmail', 'test@local.typo3.org');
        $I->click("div.module button.dropdown-toggle");
        $I->click("//a[contains(@data-value,'saveclose')]");
        $I->waitForText('The task was added successfully.');

        // run the task
        $I->click("//a[contains(@title, 'Run task')]");
        $I->waitForText('Executed: System Status Update');

        // leave the iframe
        $I->switchToIFrame();
    }
}
