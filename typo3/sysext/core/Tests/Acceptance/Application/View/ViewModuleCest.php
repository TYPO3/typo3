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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\View;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\PageTree;

final class ViewModuleCest
{
    public function _before(ApplicationTester $I, PageTree $pageTree): void
    {
        $I->useExistingSession('admin');
        $I->switchToMainFrame();
        $I->click('View');
        $I->waitForElement('#typo3-pagetree-tree .nodes .node');
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
    }

    public function CheckPagePreviewInBackend(ApplicationTester $I): void
    {
        $I->switchToIFrame('#tx_viewpage_iframe');
        $I->canSee('TYPO3 Styleguide Frontend');
        $I->canSee('This is the generated frontend for the Styleguide Extension.');
    }

    public function CheckChangingPreviewWinowSize(ApplicationTester $I): void
    {
        $I->click('#viewpage-topbar-preset-button');
        $I->click('Nexus 7');
        $width = $I->grabValueFrom('input[name="width"]');
        $height = $I->grabValueFrom('input[name="height"]');
        $I->assertEquals($width, 600);
        $I->assertEquals($height, 960);

        $I->click('#viewpage-topbar-preset-button');
        $I->click('iPhone 4');
        $width = $I->grabValueFrom('input[name="width"]');
        $height = $I->grabValueFrom('input[name="height"]');
        $I->assertEquals($width, 320);
        $I->assertEquals($height, 480);
    }
}
