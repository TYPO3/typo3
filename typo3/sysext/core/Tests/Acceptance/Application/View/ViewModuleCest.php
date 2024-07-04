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
        $pageTree->openPath(['styleguide frontend demo']);
        $I->switchToContentFrame();
    }

    public function CheckPagePreviewInBackend(ApplicationTester $I): void
    {
        $I->wait(1);
        $I->waitForElementVisible('#tx_viewpage_iframe');
        $I->wait(1);
        $I->switchToIFrame('#tx_viewpage_iframe');
        $I->wait(1);
        $I->waitForText('TYPO3 Styleguide Frontend', 20);
        $I->see('TYPO3 Styleguide Frontend');
        $I->see('This is the generated frontend for the Styleguide Extension.');
    }

    public function CheckChangingPreviewWindowSize(ApplicationTester $I): void
    {
        $I->waitForElementVisible('#viewpage-topbar-preset-button');
        $I->waitForElementNotVisible('#nprogress', 120);
        $I->click('#viewpage-topbar-preset-button');
        $I->waitForText('Tablet portrait');
        $I->click('Tablet portrait');
        $width = $I->grabValueFrom('input[name="width"]');
        $height = $I->grabValueFrom('input[name="height"]');
        $I->assertEquals($width, 768);
        $I->assertEquals($height, 1024);

        $I->waitForElementVisible('#viewpage-topbar-preset-button');
        $I->click('#viewpage-topbar-preset-button');
        $I->waitForText('Tablet landscape');
        $I->click('Tablet landscape');
        $width = $I->grabValueFrom('input[name="width"]');
        $height = $I->grabValueFrom('input[name="height"]');
        $I->assertEquals($width, 1024);
        $I->assertEquals($height, 768);
    }
}
