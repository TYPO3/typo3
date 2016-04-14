<?php
namespace TYPO3\CMS\Core\Tests\Acceptance\Backend\Extensionmanager;

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

use TYPO3\CMS\Core\Tests\Acceptance\Step\Backend\Admin;

/**
 * Extensionmanager list view
 */
class CheckExtensionmanagerListCest
{
    public function _before(Admin $I)
    {
        $I->useExistingSession();
    }

    /**
     * Check extension styleguide is there
     *
     * @param Admin $I
     */
    public function checkExtensionSyleguideIsListed(Admin $I)
    {
        $I->wantTo('check extension styleguide is there');
        $I->click('Extensions');

        // Load frame set extensionmanager
        $I->waitForElement('#typo3-contentContainerWrapper');

        $I->switchToIFrame('content');

        $extKey = 'Styleguide';
        // Fill extension search field
        $I->fillField('Tx_Extensionmanager_extensionkey', $extKey);

        // Wait for search result
        $I->waitForElement('#typo3-extension-list_wrapper');

        // Look for extension key
        $I->waitForText($extKey);
    }
}
