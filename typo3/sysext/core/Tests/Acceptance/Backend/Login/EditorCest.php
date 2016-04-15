<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Login;

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
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\Topbar;

/**
 * Editor login tests
 */
class EditorCest
{
    /**
     * Login as a non-admin user, check visible modules and logout again
     *
     * @param Kasper $I
     */
    public function loginAsEditor(Kasper $I)
    {
        $I->loginAsEditor();

        // user is redirected to 'about modules' after login, but must not see the 'admin tools' section
        $I->cantSee('Admin tools', '#typo3-menu');

        $topBarItemSelector = Topbar::$containerSelector . ' ' . Topbar::$dropdownToggleSelector . ' *';

        // can see bookmarks
        $I->seeElement($topBarItemSelector, ['title' => 'Bookmarks']);

        // cant see clear cache
        $I->cantSeeElement($topBarItemSelector, ['title' => 'Clear cache']);

        $I->logout();
        $I->waitForElement('#t3-username');
    }
}
