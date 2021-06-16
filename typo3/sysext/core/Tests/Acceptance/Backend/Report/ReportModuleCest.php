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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Report;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Tests concerning Reports Module
 */
class ReportModuleCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');

        $I->click('Reports');
        $I->switchToContentFrame();
        $I->canSee('Overview', 'h2');
    }

    /**
     * @param BackendTester $I
     */
    public function seeStatusReport(BackendTester $I)
    {
        $I->amGoingTo('select Reports in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Status Report');
        $I->see('TYPO3 System', 'h2');
    }

    /**
     * @param BackendTester $I
     */
    public function seeInstalledServices(BackendTester $I)
    {
        $I->amGoingTo('select Installed Services in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Installed Services');
        $I->see('Configured search paths for external programs', 'h3');
    }
}
