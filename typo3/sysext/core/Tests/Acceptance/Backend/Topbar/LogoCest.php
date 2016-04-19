<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Topbar;

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

/**
 * Acceptance test for the TYPO3 logo in the topbar
 */
class LogoCest
{
    /**
     * @param Admin $I
     */
    public function _before(Admin $I)
    {
        $I->useExistingSession();
        // Ensure main content frame is fully loaded, otherwise there are load-race-conditions
        $I->switchToIFrame('content');
        $I->waitForText('Web>Page module');
        $I->switchToIFrame();
    }

    /**
     * @param Admin $I
     */
    public function checkIfTypo3LogoIsLinked(Admin $I)
    {
        $I->seeElement('#typo3-topbar .typo3-topbar-site-logo', ['href' => 'https://typo3.org/']);
    }
}
