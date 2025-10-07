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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\DbCheck;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;
use TYPO3\CMS\Core\Tests\Acceptance\Support\Helper\ModalDialog;

/**
 * Tests concerning Reports Module
 */
final class DbCheckModuleCest
{
    public function _before(ApplicationTester $I): void
    {
        $I->useExistingSession('admin');
        $I->click('[data-modulemenu-identifier="system_dbint"]');
        $I->switchToContentFrame();
    }

    public function seeFullSearch(ApplicationTester $I, ModalDialog $modalDialog): void
    {
        $I->see('Search whole Database', 'h1');

        // Fill in search phrase and check results
        $I->fillField('input[name="SET[sword]"]', 'styleguide demo group 1');
        $I->click('Search All Records');
        $I->see('Result', 'h2');
        $I->see('styleguide demo group 1', 'td');
        $I->dontSee('styleguide demo group 2', 'td');

        // Open info modal and see text in card
        $I->click('a[data-dispatch-args-list]');
        $modalDialog->canSeeDialog();
        $I->switchToIFrame('.modal-iframe');
        $I->see('styleguide demo group 1', '.card-title');
    }
}
