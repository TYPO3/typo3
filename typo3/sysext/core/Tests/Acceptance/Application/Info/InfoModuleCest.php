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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Info;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests concerning Reports Module
 */
class InfoModuleCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->click('#web_info');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
    }

    public function seePageTreeOverview(ApplicationTester $I): void
    {
        $I->amGoingTo('select Pagetree Overview in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Pagetree Overview');
        $I->see('Pagetree overview', 'h1');
    }

    public function seeLocalizationOverview(ApplicationTester $I): void
    {
        $I->amGoingTo('select Localization Overview in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Localization Overview');
        $I->see('Localization overview', 'h1');
    }

    public function seePageTsConfig(ApplicationTester $I): void
    {
        $I->amGoingTo('select Page TSconfig in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Page TSconfig');
        $I->see('Page TSconfig', 'h1');
    }

    public function seeLog(ApplicationTester $I): void
    {
        $I->amGoingTo('select Log in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Log');
        $I->see('Administration log', 'h1');
    }
}
