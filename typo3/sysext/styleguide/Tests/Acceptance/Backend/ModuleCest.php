<?php

declare(strict_types=1);
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

use TYPO3\CMS\Styleguide\Tests\Acceptance\Support\BackendTester;
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
     * @param BackendTester $I
     */
    public function _before(BackendTester $I)
    {
        $I->useExistingSession('admin');
        $I->click(Topbar::$dropdownToggleSelector, self::$topBarModuleSelector);
        $I->canSee('Styleguide', self::$topBarModuleSelector);
        $I->click('Styleguide', self::$topBarModuleSelector);
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function styleguideInTopbarHelpCanBeCalled(BackendTester $I): void
    {
        $I->see('TYPO3 CMS Backend Styleguide', 'h1');
    }

    /**
     * @depends styleguideInTopbarHelpCanBeCalled
     * @param BackendTester $I
     */
    public function creatingTcaDemoDataWorks(BackendTester $I): void
    {
        $I->click('TCA / Records / Frontend');
        $I->waitForText('TCA test records');
        $I->click('Create styleguide page tree with data');
        $this->seeResponse($I, 'A page tree with styleguide TCA test records was created.');
    }

    /**
     * @depends creatingTcaDemoDataWorks
     * @param BackendTester $I
     */
    public function deletingTcaDemoDataWorks(BackendTester $I): void
    {
        $I->click('TCA / Records / Frontend');
        $I->waitForText('TCA test records');
        $I->click('Delete styleguide page tree and all styleguide data records');
        $this->seeResponse($I, 'The styleguide page tree and all styleguide records were deleted.');
    }

    /**
     * @depends styleguideInTopbarHelpCanBeCalled
     * @param BackendTester $I
     */
    public function creatingFrontendDemoDataWorks(BackendTester $I): void
    {
        $I->click('TCA / Records / Frontend');
        $I->waitForText('TCA test records');
        $I->click('Create styleguide frontend');
        $this->seeResponse($I, 'A page tree with styleguide frontend test records was created.');
    }

    /**
     * @depends creatingTcaDemoDataWorks
     * @param BackendTester $I
     */
    public function deletingFrontendDemoDataWorks(BackendTester $I): void
    {
        $I->click('TCA / Records / Frontend');
        $I->waitForText('TCA test records');
        $I->click('Delete styleguide frontend');
        $this->seeResponse($I, 'The styleguide frontend page tree and all styleguide frontend records were deleted.');
    }

    private function seeResponse(BackendTester $I, string $message): void
    {
        $I->seeElement('.t3js-generator-action .icon-spinner-circle-dark');
        $I->switchToMainFrame();
        $I->waitForText($message, 60, '.alert-message');
        $I->switchToContentFrame();
        $I->dontSeeElement('.t3js-generator-action .icon-spinner-circle-dark');
    }
}
