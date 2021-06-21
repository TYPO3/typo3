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

namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Info;

use TYPO3\CMS\Core\Tests\Acceptance\Support\BackendTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

/**
 * Tests concerning Reports Module
 */
class InfoModuleCest
{
    /**
     * @param BackendTester $I
     * @param PageTree $pageTree
     */
    public function _before(BackendTester $I, PageTree $pageTree)
    {
        $I->useExistingSession('admin');
        $I->click('#web_info');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node', 5);
        $pageTree->openPath(['styleguide TCA demo']);
        $I->switchToContentFrame();
    }

    /**
     * @param BackendTester $I
     */
    public function seePageTreeOverview(BackendTester $I)
    {
        $I->amGoingTo('select Pagetree Overview in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Pagetree Overview');
        $I->see('Pagetree overview', 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function seeLocalizationOverview(BackendTester $I)
    {
        $I->amGoingTo('select Localization Overview in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Localization Overview');
        $I->see('Localization overview', 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function seePageTsConfig(BackendTester $I)
    {
        $I->amGoingTo('select Page TSconfig in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Page TSconfig');
        $I->see('Page TSconfig', 'h1');
    }

    /**
     * @param BackendTester $I
     */
    public function seeLog(BackendTester $I)
    {
        $I->amGoingTo('select Log in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', 'Log');
        $I->see('Administration log', 'h1');
    }
}
