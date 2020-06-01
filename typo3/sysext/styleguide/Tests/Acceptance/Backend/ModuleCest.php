<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Styleguide\Tests\Acceptance\Backend;

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


use TYPO3\CMS\Styleguide\Tests\Acceptance\Support\AcceptanceTester;
use TYPO3\TestingFramework\Core\Acceptance\Helper\Topbar;

/**
 * Tests the styleguide backend module can be loaded
 */
class ModuleCest
{
    /**
     * Selector for the module container in the topbar
     *
     * @var string
     */
    public static $topBarModuleSelector = '#typo3-cms-backend-backend-toolbaritems-helptoolbaritem';

    /**
     * @param AcceptanceTester $I
     */
    public function _before(AcceptanceTester $I)
    {
        $I->useExistingSession('admin');
    }

    /**
     * @param AcceptanceTester $I
     */
    public function styleguideInTopbarHelpCanBeCalled(AcceptanceTester $I)
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Styleguide', self::$topBarModuleSelector);
        $I->click('Styleguide', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->see('TYPO3 CMS Backend Styleguide', 'h1');
    }

    /**
     * @depends styleguideInTopbarHelpCanBeCalled
     * @param AcceptanceTester $I
     */
    public function creatingAndDeletingDemoDataWorks(AcceptanceTester $I)
    {
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Styleguide', self::$topBarModuleSelector);
        $I->click('Styleguide', self::$topBarModuleSelector);
        $I->switchToContentFrame();
        $I->see('TYPO3 CMS Backend Styleguide', 'h1');
        $I->click('TCA / Records');
        $I->waitForText('TCA test records');
        $I->click('Create styleguide page tree with data');
        $I->waitForText('A page tree with styleguide TCA test records was created.', 300);

        // delete again
        $I->click('Delete styleguide page tree and all styleguide data records');
        $I->waitForText('The styleguide page tree and all styleguide records were deleted.', 300);
    }
}
