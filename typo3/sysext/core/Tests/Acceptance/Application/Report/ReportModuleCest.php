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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Report;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests concerning Reports Module
 */
final class ReportModuleCest
{
    public function seeStatusReport(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('Reports');
        $I->switchToContentFrame();
        $I->see('TYPO3 System', 'h2');
    }
}
