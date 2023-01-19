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

use Codeception\Example;
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

    /**
     * @dataProvider infoMenuDataProvider
     */
    public function seeInfoSubModules(ApplicationTester $I, Example $exampleData): void
    {
        $I->amGoingTo('select ' . $exampleData['option'] . ' in dropdown');
        $I->selectOption('.t3-js-jumpMenuBox', $exampleData['option']);
        $I->see($exampleData['expect'], 'h1');
    }

    /**
     * @return array[]
     */
    protected function infoMenuDataProvider(): array
    {
        return [
            ['option' => 'Pagetree Overview', 'expect' => 'Pagetree Overview'],
            ['option' => 'Localization Overview', 'expect' => 'Localization Overview'],
        ];
    }
}
