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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Impexp;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Common test helper functions
 */
abstract class AbstractCest
{
    protected function selectInContextMenu(ApplicationTester $I, array $path): void
    {
        foreach ($path as $selector) {
            $I->waitForElementVisible('typo3-backend-context-menu ' . $selector, 5);
            $I->click($selector, 'typo3-backend-context-menu');
        }
    }

    protected function timeoutForAjaxRequest(ApplicationTester $I): void
    {
        $I->wait(0.5);
    }
}
