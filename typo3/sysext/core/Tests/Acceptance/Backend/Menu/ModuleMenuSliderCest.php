<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Menu;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Kasper;

/**
 * Acceptance test
 */
class ModuleMenuSliderCest
{
    public function _before(Kasper $I)
    {
        $I->loginAsAdmin();
    }

    public function _after(Kasper $I)
    {
        $I->logout();
    }

    // tests
    public function tryToTest(Kasper $I)
    {
        $ids = ['#web', '#tools', '#system'];
        $sees = ['Page', 'Extensions'];
        $typo3Menu = '#typo3-menu';

        $I->wantTo('check the slider in the module menu');

        foreach ($ids as $id) {
            $I->waitForElement($id);

            // we close all
            $classString = $I->executeInSelenium(function (\Facebook\WebDriver\Remote\RemoteWebDriver $webdriver) use ($id) {
                return $webdriver->findElement(\WebDriverBy::cssSelector($id))->getAttribute('class');
            });

            if (strpos($classString, 'expanded') !== false) {
                $I->click($id . ' > div');
                $I->wait(2); // the animation is so fast
            }
        }

        foreach ($sees as $see) {
            $I->cantSee($see);
        }

        // we open all
        foreach ($ids as $id) {
            $I->click($id . ' > div');
            $I->wait(2);
        }

        foreach ($sees as $see) {
            $I->see($see);
        }
    }
}