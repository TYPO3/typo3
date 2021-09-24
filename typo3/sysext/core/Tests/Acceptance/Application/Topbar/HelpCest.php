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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Topbar;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
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
    public static string $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-helptoolbaritem';

    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
    }

    public function canSeeModuleInTopbar(ApplicationTester $I): void
    {
        $I->canSeeElement(self::$topBarModuleSelector);
    }

    /**
     * @depends canSeeModuleInTopbar
     */
    public function seeStyleguideInHelpModule(ApplicationTester $I): void
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Styleguide', self::$topBarModuleSelector);
        $I->click('Styleguide', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->see('TYPO3 CMS Backend Styleguide', 'h1');
    }

    public function seeAboutInHelpModule(ApplicationTester $I): void
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('About TYPO3 CMS', self::$topBarModuleSelector);
        $I->click('About TYPO3 CMS', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->see('Web Content Management System', 'h1');
    }

    public function seeManualInHelpModule(ApplicationTester $I): void
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('TYPO3 Manual', self::$topBarModuleSelector);
        $I->click('TYPO3 Manual', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->see('TYPO3 Inline User Manual', 'h1');
        $I->click('TYPO3 Core', '.help-view');
        $I->see('TYPO3 Core', 'h2');
    }
}
