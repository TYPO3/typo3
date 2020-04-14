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
use TYPO3\TestingFramework\Core\Acceptance\Helper\Topbar;

/**
 * Tests for the help module in the topbar
 */
class HelpCest
{
    /**
     * Selector for the module container in the topbar
     *
     * @var string
     */
    public static $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-helptoolbaritem';

    /**
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param BackendTester $I
     * @return BackendTester
     */
    public function canSeeModuleInTopbar(BackendTester $I)
    {
        $I->canSeeElement(self::$topBarModuleSelector);
        return $I;
    }

    /**
     * @depends canSeeModuleInTopbar
     * @param BackendTester $I
     */
    public function seeStyleguideInHelpModule(BackendTester $I)
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Styleguide', self::$topBarModuleSelector);
        $I->click('Styleguide', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->see('TYPO3 CMS Backend Styleguide', 'h1');
    }
}
