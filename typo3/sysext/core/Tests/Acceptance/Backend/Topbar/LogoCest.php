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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Topbar;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;

/**
 * Acceptance test for the TYPO3 logo in the topbar
 */
class LogoCest
{
    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     */
    public function checkIfTypo3LogoIsLinked(BackendTester $I)
    {
        $I->seeElement('//div[@class="topbar-header-site"]/a[@href="./"]');
    }
}
