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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Styleguide;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

final class NotificationCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->amOnPage('/typo3/module/system/styleguide/components?action=notifications');
    }

    public function seeClearAllButton(ApplicationTester $I): void
    {
        $I->amGoingTo('Open a notification');
        $I->switchToContentFrame();
        $I->click('.styleguide-content .styleguide-example button');
        $I->switchToMainFrame();
        $I->waitForElement('#alert-container');
        $I->dontSee('#alert-container typo3-notification-message');

        $I->amGoingTo('Open a second notification and expecting to see the "Clear all" button');
        $I->switchToContentFrame();
        $I->click('.styleguide-content .styleguide-example button');
        $I->switchToMainFrame();
        $I->waitForElement('#alert-container typo3-notification-message');
        $I->waitForElement('#alert-container typo3-notification-clear-all');
        $I->click('typo3-notification-clear-all');

        $I->dontSee('typo3-notification-clear-all');
        $I->dontSee('typo3-notification-message');
    }
}
