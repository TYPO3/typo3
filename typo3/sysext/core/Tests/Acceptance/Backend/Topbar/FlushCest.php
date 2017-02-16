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

use TYPO3\TestingFramework\Core\Acceptance\Step\Backend\Admin;
use TYPO3\TestingFramework\Core\Acceptance\Support\Helper\Topbar;

/**
 * Tests for the flush module in the topbar
 */
class FlushCest
{
    /**
     * Selector for the module container in the topbar
     *
     * @var string
     */
    protected static $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-clearcachetoolbaritem';

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
    }

    /**
     * @param Admin $I
     * @return Admin
     */
    public function canSeeModuleInTopbar(Admin $I)
    {
        $I->canSeeElement(self::$topBarModuleSelector);

        return $I;
    }

    /**
     * @depends canSeeModuleInTopbar
     * @param Admin $I
     */
    public function seeFlushCachesLinksInClearCacheModule(Admin $I)
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        // Ensure existence of link for flush frontend caches
        $I->canSee('Flush frontend caches', 'a');
        // Ensure existence of link for flush all caches
        $I->canSee('Flush all caches', 'a');
    }
}
